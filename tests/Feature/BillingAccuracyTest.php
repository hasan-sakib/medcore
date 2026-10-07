<?php

use App\Models\ChargeItem;
use App\Models\Encounter;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Illuminate\Support\Facades\Queue;

const BA_HOST = 'http://accuracyh.medcore.local';

beforeEach(function () {
    Queue::fake();

    $this->tenant = app(TenantProvisioningService::class)->provision(
        ['name' => 'Accuracy Hospital', 'slug' => 'accuracyh', 'plan' => 'professional'],
        ['name' => 'Acc Admin', 'email' => 'admin@accuracyh.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->cashier = User::factory()->forTenant($this->tenant)->create();
    $this->cashier->assignRole('cashier');
    $this->cashier = $this->cashier->fresh();

    $this->patient = Patient::factory()->forTenant($this->tenant)->create();
    $this->encounter = Encounter::factory()->create([
        'tenant_id' => $this->tenant->id,
        'patient_id' => $this->patient->id,
        'attending_doctor_id' => $this->cashier->id,
    ]);

    // Price list: a taxed consultation (picked up by createFromEncounter) and a lab test.
    $this->consultation = ChargeItem::create([
        'name' => 'General Consultation', 'code' => 'CONS-GEN', 'category' => 'consultation',
        'unit_price' => 50.00, 'tax_rate' => 5, 'is_active' => true,
    ]);
    $this->lab = ChargeItem::create([
        'name' => 'CBC Blood Test', 'code' => 'LAB-CBC', 'category' => 'lab',
        'unit_price' => 15.33, 'tax_rate' => 7.5, 'is_active' => true,
    ]);

    $this->actingAs($this->cashier);
});

/** Build an invoice from the encounter over HTTP, then add extra lines and an invoice-level discount. */
function baInvoiceFromEncounter(): Invoice
{
    $tenant = test()->tenant;
    app(TenantManager::class)->setCurrent($tenant);

    test()->post(BA_HOST.'/invoices', [
        'patient_id' => test()->patient->id,
        'encounter_id' => test()->encounter->id,
        'from_encounter' => true,
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($tenant);
    $invoice = Invoice::where('encounter_id', test()->encounter->id)->firstOrFail();

    test()->post(BA_HOST."/invoices/{$invoice->id}/lines", [
        'charge_item_id' => test()->lab->id,
        'description' => 'CBC Blood Test',
        'quantity' => 3,
        'unit_price' => 15.33,
        'tax_rate' => 7.5,
        'discount_amount' => 2.50,
    ])->assertSessionHasNoErrors();

    test()->post(BA_HOST."/invoices/{$invoice->id}/lines", [
        'description' => 'Dressing supplies',
        'quantity' => 1.5,
        'unit_price' => 9.99,
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($tenant);
    $invoice = $invoice->fresh();
    $invoice->update(['discount_amount' => 10]);
    app(InvoiceService::class)->recalculate($invoice->fresh());

    return $invoice->fresh();
}

it('builds an invoice from an encounter whose lines, tax and discount reconcile to the total', function () {
    $invoice = baInvoiceFromEncounter();
    $lines = $invoice->lines()->get();

    // Consultation line came from the encounter, plus the two lines added above.
    expect($lines)->toHaveCount(3);

    $subtotal = round($lines->sum(fn ($l) => $l->quantity * $l->unit_price - $l->discount_amount), 2);
    $taxTotal = round($lines->sum('tax_amount'), 2);
    $discount = round((float) $invoice->discount_amount, 2);

    expect(round((float) $invoice->subtotal, 2))->toBe($subtotal);
    expect(round((float) $invoice->tax_total, 2))->toBe($taxTotal);
    expect(round((float) $invoice->total_amount, 2))->toBe(round($subtotal + $taxTotal - $discount, 2));

    // Independent check from the stored per-line totals.
    expect(round((float) $invoice->total_amount, 2))
        ->toBe(round($lines->sum('line_total') - $discount, 2));

    // Hand-computed expectation:
    // consultation 50 + 2.50 tax = 52.50; labs 3 x 15.33 - 2.50 = 43.49 + 3.26 tax (3.261) = 46.75;
    // supplies 1.5 x 9.99 = 14.985 (no tax). Subtotal 50 + 43.49 + 14.985 = 108.475.
    expect(round((float) $invoice->tax_total, 2))->toBe(5.76);
    expect(round((float) $invoice->total_amount, 2))->toBe(round(108.475 + 5.76 - 10, 2));

    // Nothing paid yet: everything is due.
    expect(round((float) $invoice->amount_paid, 2))->toBe(0.0);
    expect(round((float) $invoice->amount_due, 2))->toBe(round((float) $invoice->total_amount, 2));
});

it('keeps amount_paid + amount_due equal to total_amount across partial payments', function () {
    $invoice = baInvoiceFromEncounter();
    $total = round((float) $invoice->total_amount, 2);

    $this->patch(BA_HOST."/invoices/{$invoice->id}/finalize")->assertSessionHasNoErrors();

    $first = 30.00;
    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => $first, 'payment_method' => 'cash',
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('partially_paid');
    expect(round((float) $invoice->amount_paid, 2))->toBe($first);
    expect(round((float) $invoice->amount_paid + (float) $invoice->amount_due, 2))->toBe($total);

    $second = 25.25;
    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => $second, 'payment_method' => 'card',
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('partially_paid');
    expect(round((float) $invoice->amount_paid, 2))->toBe(round($first + $second, 2));
    expect(round((float) $invoice->amount_paid + (float) $invoice->amount_due, 2))->toBe($total);
    expect(round((float) $invoice->payments()->sum('amount'), 2))->toBe((float) $invoice->amount_paid);

    // Pay the exact remainder: invoice is fully paid with nothing due.
    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => (float) $invoice->amount_due, 'payment_method' => 'cash',
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('paid');
    expect(round((float) $invoice->amount_due, 2))->toBe(0.0);
    expect(round((float) $invoice->amount_paid, 2))->toBe($total);
    expect($invoice->paid_at)->not->toBeNull();
});

it('rejects an overpayment and records nothing', function () {
    $invoice = baInvoiceFromEncounter();
    $due = round((float) $invoice->amount_due, 2);

    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => $due + 0.01, 'payment_method' => 'cash',
    ])->assertSessionHasErrors('amount');

    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => 0, 'payment_method' => 'cash',
    ])->assertSessionHasErrors('amount');

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    expect($invoice->payments()->count())->toBe(0);
    expect(round((float) $invoice->amount_paid, 2))->toBe(0.0);
    expect(round((float) $invoice->amount_due, 2))->toBe($due);
});

it('rejects an overpayment after a partial payment', function () {
    $invoice = baInvoiceFromEncounter();

    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => 50, 'payment_method' => 'cash',
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    $remaining = round((float) $invoice->amount_due, 2);

    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => $remaining + 1, 'payment_method' => 'cash',
    ])->assertSessionHasErrors('amount');

    app(TenantManager::class)->setCurrent($this->tenant);
    expect($invoice->fresh()->payments()->count())->toBe(1);
    expect(round((float) $invoice->fresh()->amount_due, 2))->toBe($remaining);
});

it('locks a paid invoice against further payments and line changes', function () {
    $invoice = baInvoiceFromEncounter();
    $total = round((float) $invoice->total_amount, 2);

    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => $total, 'payment_method' => 'bank_transfer',
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('paid');
    $lineCount = $invoice->lines()->count();

    $this->post(BA_HOST."/invoices/{$invoice->id}/payments", [
        'amount' => 1, 'payment_method' => 'cash',
    ])->assertForbidden();

    $this->post(BA_HOST."/invoices/{$invoice->id}/lines", [
        'description' => 'Late charge', 'quantity' => 1, 'unit_price' => 20,
    ])->assertForbidden();

    app(TenantManager::class)->setCurrent($this->tenant);
    $invoice = $invoice->fresh();
    expect($invoice->payments()->count())->toBe(1);
    expect($invoice->lines()->count())->toBe($lineCount);
    expect(round((float) $invoice->total_amount, 2))->toBe($total);
    expect(round((float) $invoice->amount_paid, 2))->toBe($total);
    expect(round((float) $invoice->amount_due, 2))->toBe(0.0);
});

it('invoice create page lists only the patient\'s un-invoiced encounters', function () {
    $this->actingAs($this->cashier);

    $page = $this->get(BA_HOST.'/invoices/create?patient_id='.$this->patient->id);
    $page->assertOk();
    expect(collect($page->original->getData()['page']['props']['encounters'])->pluck('id')->all())
        ->toBe([$this->encounter->id]);

    $this->post(BA_HOST.'/invoices', [
        'patient_id' => $this->patient->id,
        'encounter_id' => $this->encounter->id,
        'from_encounter' => true,
    ])->assertRedirect();

    $after = $this->get(BA_HOST.'/invoices/create?patient_id='.$this->patient->id);
    expect(collect($after->original->getData()['page']['props']['encounters'])->pluck('id')->all())->toBe([]);
});
