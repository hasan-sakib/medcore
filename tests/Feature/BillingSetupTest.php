<?php

use App\Models\ChargeItem;
use App\Models\Claim;
use App\Models\InsurancePolicy;
use App\Models\InvoiceLine;
use App\Models\Patient;
use App\Models\TaxConfig;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;

const BS_HOST = 'http://setupa.medcore.local';
const BS_OTHER_HOST = 'http://setupb.medcore.local';

beforeEach(function () {
    $this->withoutVite();

    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'Setup A Hospital', 'slug' => 'setupa', 'plan' => 'professional'],
        ['name' => 'A Admin', 'email' => 'admin@setupa.test', 'password' => 'pass']
    );
    $this->otherTenant = $provisioner->provision(
        ['name' => 'Setup B Hospital', 'slug' => 'setupb', 'plan' => 'professional'],
        ['name' => 'B Admin', 'email' => 'admin@setupb.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->admin = User::factory()->forTenant($this->tenant)->create();
    $this->admin->assignRole('tenant-admin');
    $this->admin = $this->admin->fresh();

    $this->cashier = User::factory()->forTenant($this->tenant)->create();
    $this->cashier->assignRole('cashier');
    $this->cashier = $this->cashier->fresh();

    $this->patient = Patient::factory()->forTenant($this->tenant)->create();
});

function bsAsAdmin(): void
{
    test()->actingAs(test()->admin);
    app(TenantManager::class)->setCurrent(test()->tenant);
}

function bsChargeItemPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dressing Change',
        'code' => 'proc-dress',
        'category' => 'procedure',
        'unit_price' => '25.50',
        'tax_rate' => '5',
        'is_active' => true,
    ], $overrides);
}

function bsPolicyPayload(int $patientId, array $overrides = []): array
{
    return array_merge([
        'patient_id' => $patientId,
        'provider_name' => 'Acme Health',
        'policy_number' => 'POL-1001',
        'group_number' => 'GRP-9',
        'coverage_type' => 'family',
        'coverage_limit' => '10000',
        'copay_amount' => '15',
        'deductible_amount' => '100',
        'valid_from' => '2026-01-01',
        'valid_until' => '2026-12-31',
        'is_active' => true,
    ], $overrides);
}

// ── Charge items ────────────────────────────────────────────────────────────

it('admin creates a charge item and the code is normalised', function () {
    bsAsAdmin();

    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload())
        ->assertRedirect('/billing/charge-items')
        ->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $item = ChargeItem::where('code', 'PROC-DRESS')->first();

    expect($item)->not->toBeNull();
    expect($item->tenant_id)->toBe($this->tenant->id);
    expect((float) $item->unit_price)->toBe(25.5);
    expect((float) $item->tax_rate)->toBe(5.0);
});

it('admin can load the charge item pages', function () {
    bsAsAdmin();
    $item = ChargeItem::create(bsChargeItemPayload(['code' => 'PAGE-1']));

    $this->get(BS_HOST.'/billing/charge-items')->assertOk();
    $this->get(BS_HOST.'/billing/charge-items?category=procedure&search=PAGE')->assertOk();
    $this->get(BS_HOST.'/billing/charge-items/create')->assertOk();
    $this->get(BS_HOST."/billing/charge-items/{$item->id}/edit")->assertOk();
});

it('validates charge item money, rates, category and unique code per tenant', function () {
    bsAsAdmin();
    ChargeItem::create(bsChargeItemPayload(['code' => 'DUP-1']));

    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'dup-1']))
        ->assertSessionHasErrors('code');
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'N-1', 'unit_price' => '-1']))
        ->assertSessionHasErrors('unit_price');
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'N-2', 'unit_price' => '1.999']))
        ->assertSessionHasErrors('unit_price');
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'N-3', 'tax_rate' => '100.01']))
        ->assertSessionHasErrors('tax_rate');
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'N-4', 'tax_rate' => '-0.5']))
        ->assertSessionHasErrors('tax_rate');
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'N-5', 'category' => 'bogus']))
        ->assertSessionHasErrors('category');
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'bad code!']))
        ->assertSessionHasErrors('code');

    // The same code is fine in another tenant.
    app(TenantManager::class)->setCurrent($this->otherTenant);
    ChargeItem::create(bsChargeItemPayload(['code' => 'SHARED-1']));
    app(TenantManager::class)->setCurrent($this->tenant);
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'shared-1']))
        ->assertSessionHasNoErrors();
});

it('admin updates a charge item keeping its own code', function () {
    bsAsAdmin();
    $item = ChargeItem::create(bsChargeItemPayload(['code' => 'UPD-1']));

    $this->put(BS_HOST."/billing/charge-items/{$item->id}", bsChargeItemPayload([
        'code' => 'UPD-1', 'unit_price' => '30', 'is_active' => false,
    ]))->assertRedirect('/billing/charge-items')->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $item->refresh();
    expect((float) $item->unit_price)->toBe(30.0);
    expect($item->is_active)->toBeFalse();
});

it('deletes an unused charge item', function () {
    bsAsAdmin();
    $item = ChargeItem::create(bsChargeItemPayload(['code' => 'UNUSED-1']));

    $this->delete(BS_HOST."/billing/charge-items/{$item->id}")->assertRedirect();

    app(TenantManager::class)->setCurrent($this->tenant);
    expect(ChargeItem::find($item->id))->toBeNull();
});

it('deactivates instead of deleting a charge item used on an invoice line', function () {
    bsAsAdmin();
    $item = ChargeItem::create(bsChargeItemPayload(['code' => 'USED-1']));

    $invoice = app(InvoiceService::class)->createDraft($this->patient, $this->admin->id);
    app(InvoiceService::class)->addLine($invoice, [
        'charge_item_id' => $item->id,
        'description' => $item->name,
        'quantity' => 1,
        'unit_price' => $item->unit_price,
        'tax_rate' => $item->tax_rate,
    ]);

    $this->delete(BS_HOST."/billing/charge-items/{$item->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    app(TenantManager::class)->setCurrent($this->tenant);
    $item = ChargeItem::find($item->id);
    expect($item)->not->toBeNull();
    expect($item->is_active)->toBeFalse();
    expect(InvoiceLine::where('charge_item_id', $item->id)->count())->toBe(1);
});

// ── Tax configs ─────────────────────────────────────────────────────────────

it('admin creates, updates and deactivates a tax config', function () {
    bsAsAdmin();

    $this->post(BS_HOST.'/billing/tax-configs', [
        'name' => 'Pharmacy VAT', 'rate' => '7.5', 'applies_to' => 'medicine', 'is_active' => true,
    ])->assertRedirect('/billing/tax-configs')->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $cfg = TaxConfig::where('name', 'Pharmacy VAT')->first();
    expect($cfg)->not->toBeNull();
    expect((float) $cfg->rate)->toBe(7.5);

    $this->get(BS_HOST.'/billing/tax-configs')->assertOk();
    $this->get(BS_HOST.'/billing/tax-configs/create')->assertOk();
    $this->get(BS_HOST."/billing/tax-configs/{$cfg->id}/edit")->assertOk();

    $this->put(BS_HOST."/billing/tax-configs/{$cfg->id}", [
        'name' => 'Pharmacy VAT', 'rate' => '10', 'applies_to' => 'medicine', 'is_active' => true,
    ])->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    expect((float) $cfg->fresh()->rate)->toBe(10.0);

    $this->delete(BS_HOST."/billing/tax-configs/{$cfg->id}")->assertRedirect();

    app(TenantManager::class)->setCurrent($this->tenant);
    $cfg = TaxConfig::find($cfg->id);
    expect($cfg)->not->toBeNull();
    expect($cfg->is_active)->toBeFalse();
});

it('validates tax config rate range and applies_to', function () {
    bsAsAdmin();

    $this->post(BS_HOST.'/billing/tax-configs', ['name' => 'X', 'rate' => '100.5', 'applies_to' => 'all'])
        ->assertSessionHasErrors('rate');
    $this->post(BS_HOST.'/billing/tax-configs', ['name' => 'X', 'rate' => '-1', 'applies_to' => 'all'])
        ->assertSessionHasErrors('rate');
    $this->post(BS_HOST.'/billing/tax-configs', ['name' => 'X', 'rate' => '5', 'applies_to' => 'nope'])
        ->assertSessionHasErrors('applies_to');
    $this->post(BS_HOST.'/billing/tax-configs', ['rate' => '5', 'applies_to' => 'all'])
        ->assertSessionHasErrors('name');
});

// ── Insurance policies ──────────────────────────────────────────────────────

it('admin adds an insurance policy for a patient', function () {
    bsAsAdmin();

    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id))
        ->assertRedirect('/billing/insurance-policies?patient_id='.$this->patient->id)
        ->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    $policy = InsurancePolicy::where('policy_number', 'POL-1001')->first();
    expect($policy)->not->toBeNull();
    expect($policy->tenant_id)->toBe($this->tenant->id);
    expect($policy->patient_id)->toBe($this->patient->id);

    $this->get(BS_HOST.'/billing/insurance-policies?patient_id='.$this->patient->id)->assertOk();
    $this->get(BS_HOST.'/billing/insurance-policies/create?patient_id='.$this->patient->id)->assertOk();
    $this->get(BS_HOST."/billing/insurance-policies/{$policy->id}/edit")->assertOk();
});

it('validates insurance policy dates, money and unique policy number per tenant', function () {
    bsAsAdmin();
    InsurancePolicy::create(bsPolicyPayload($this->patient->id));

    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id))
        ->assertSessionHasErrors('policy_number');
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id, [
        'policy_number' => 'POL-2', 'valid_from' => '2026-06-01', 'valid_until' => '2026-05-31',
    ]))->assertSessionHasErrors('valid_until');
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id, [
        'policy_number' => 'POL-3', 'copay_amount' => '-1',
    ]))->assertSessionHasErrors('copay_amount');
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id, [
        'policy_number' => 'POL-4', 'deductible_amount' => '-5',
    ]))->assertSessionHasErrors('deductible_amount');
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id, [
        'policy_number' => 'POL-5', 'coverage_limit' => '-100',
    ]))->assertSessionHasErrors('coverage_limit');

    // Same valid_from/valid_until is allowed; open-ended policy is allowed.
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id, [
        'policy_number' => 'POL-6', 'valid_until' => null, 'coverage_limit' => null,
    ]))->assertSessionHasNoErrors();
});

it('rejects an insurance policy for a patient from another tenant', function () {
    app(TenantManager::class)->setCurrent($this->otherTenant);
    $foreign = Patient::factory()->forTenant($this->otherTenant)->create();

    bsAsAdmin();
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($foreign->id))
        ->assertSessionHasErrors('patient_id');

    $this->get(BS_HOST.'/billing/insurance-policies?patient_id='.$foreign->id)->assertNotFound();
});

it('admin updates a policy and removing one with claims only deactivates it', function () {
    bsAsAdmin();
    $policy = InsurancePolicy::create(bsPolicyPayload($this->patient->id));

    $this->put(BS_HOST."/billing/insurance-policies/{$policy->id}", bsPolicyPayload($this->patient->id, [
        'copay_amount' => '40',
    ]))->assertSessionHasNoErrors();

    app(TenantManager::class)->setCurrent($this->tenant);
    expect((float) $policy->fresh()->copay_amount)->toBe(40.0);

    $invoice = app(InvoiceService::class)->createDraft($this->patient, $this->admin->id);
    Claim::create([
        'invoice_id' => $invoice->id,
        'insurance_policy_id' => $policy->id,
        'patient_id' => $this->patient->id,
        'claim_number' => 'CLM-T-1',
        'status' => 'draft',
        'amount_claimed' => 10,
        'submitted_by' => $this->admin->id,
    ]);

    $this->delete(BS_HOST."/billing/insurance-policies/{$policy->id}")->assertRedirect();

    app(TenantManager::class)->setCurrent($this->tenant);
    $policy = InsurancePolicy::find($policy->id);
    expect($policy)->not->toBeNull();
    expect($policy->is_active)->toBeFalse();
});

it('cashier can read a patient\'s policies but not manage them', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $policy = InsurancePolicy::create(bsPolicyPayload($this->patient->id));

    $this->actingAs($this->cashier);

    $this->get(BS_HOST.'/billing/insurance-policies?patient_id='.$this->patient->id)->assertOk();
    $this->get(BS_HOST.'/billing/insurance-policies/create')->assertForbidden();
    $this->post(BS_HOST.'/billing/insurance-policies', bsPolicyPayload($this->patient->id, ['policy_number' => 'X-1']))
        ->assertForbidden();
    $this->get(BS_HOST."/billing/insurance-policies/{$policy->id}/edit")->assertForbidden();
    $this->delete(BS_HOST."/billing/insurance-policies/{$policy->id}")->assertForbidden();
});

// ── Authorization and tenant isolation ──────────────────────────────────────

it('cashier without manage permissions gets 403 on charge items and tax configs', function () {
    app(TenantManager::class)->setCurrent($this->tenant);
    $item = ChargeItem::create(bsChargeItemPayload(['code' => 'AUTH-1']));
    $cfg = TaxConfig::create(['name' => 'VAT', 'rate' => 5, 'applies_to' => 'all', 'is_active' => true]);

    $this->actingAs($this->cashier);

    $this->get(BS_HOST.'/billing/charge-items')->assertForbidden();
    $this->get(BS_HOST.'/billing/charge-items/create')->assertForbidden();
    $this->post(BS_HOST.'/billing/charge-items', bsChargeItemPayload(['code' => 'NEW-1']))->assertForbidden();
    $this->put(BS_HOST."/billing/charge-items/{$item->id}", bsChargeItemPayload(['code' => 'AUTH-1']))->assertForbidden();
    $this->delete(BS_HOST."/billing/charge-items/{$item->id}")->assertForbidden();

    $this->get(BS_HOST.'/billing/tax-configs')->assertForbidden();
    $this->post(BS_HOST.'/billing/tax-configs', ['name' => 'X', 'rate' => 1, 'applies_to' => 'all'])->assertForbidden();
    $this->put(BS_HOST."/billing/tax-configs/{$cfg->id}", ['name' => 'X', 'rate' => 1, 'applies_to' => 'all'])->assertForbidden();
    $this->delete(BS_HOST."/billing/tax-configs/{$cfg->id}")->assertForbidden();

    app(TenantManager::class)->setCurrent($this->tenant);
    expect(ChargeItem::where('code', 'NEW-1')->exists())->toBeFalse();
    expect(ChargeItem::find($item->id))->not->toBeNull();
    expect($cfg->fresh()->is_active)->toBeTrue();
});

it('returns 404 for another tenant\'s charge items, tax configs and policies', function () {
    app(TenantManager::class)->setCurrent($this->otherTenant);
    $foreignItem = ChargeItem::create(bsChargeItemPayload(['code' => 'B-ITEM']));
    $foreignCfg = TaxConfig::create(['name' => 'B VAT', 'rate' => 5, 'applies_to' => 'all', 'is_active' => true]);
    $foreignPatient = Patient::factory()->forTenant($this->otherTenant)->create();
    $foreignPolicy = InsurancePolicy::create(bsPolicyPayload($foreignPatient->id, ['policy_number' => 'B-POL']));

    bsAsAdmin();

    $this->get(BS_HOST."/billing/charge-items/{$foreignItem->id}/edit")->assertNotFound();
    $this->put(BS_HOST."/billing/charge-items/{$foreignItem->id}", bsChargeItemPayload(['code' => 'B-ITEM']))->assertNotFound();
    $this->delete(BS_HOST."/billing/charge-items/{$foreignItem->id}")->assertNotFound();

    $this->get(BS_HOST."/billing/tax-configs/{$foreignCfg->id}/edit")->assertNotFound();
    $this->delete(BS_HOST."/billing/tax-configs/{$foreignCfg->id}")->assertNotFound();

    $this->get(BS_HOST."/billing/insurance-policies/{$foreignPolicy->id}/edit")->assertNotFound();
    $this->put(BS_HOST."/billing/insurance-policies/{$foreignPolicy->id}", bsPolicyPayload($foreignPatient->id, ['policy_number' => 'B-POL']))->assertNotFound();
    $this->delete(BS_HOST."/billing/insurance-policies/{$foreignPolicy->id}")->assertNotFound();

    app(TenantManager::class)->setCurrent($this->otherTenant);
    expect(ChargeItem::find($foreignItem->id))->not->toBeNull();
    expect($foreignCfg->fresh()->is_active)->toBeTrue();
    expect(InsurancePolicy::find($foreignPolicy->id))->not->toBeNull();
});

it('does not list other tenants\' records', function () {
    app(TenantManager::class)->setCurrent($this->otherTenant);
    ChargeItem::create(bsChargeItemPayload(['code' => 'B-ONLY', 'name' => 'Tenant B Only']));

    bsAsAdmin();
    ChargeItem::create(bsChargeItemPayload(['code' => 'A-ONLY', 'name' => 'Tenant A Only']));

    $this->get(BS_HOST.'/billing/charge-items')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Billing/ChargeItems/Index')
            ->has('items.data', 1)
            ->where('items.data.0.code', 'A-ONLY'));
});
