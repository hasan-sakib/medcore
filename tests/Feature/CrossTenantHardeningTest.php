<?php

use App\Models\OperatingRoom;
use App\Models\OrSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->a = $provisioner->provision(
        ['name' => 'Hard A', 'slug' => 'harda', 'plan' => 'professional'],
        ['name' => 'A Admin', 'email' => 'admin@harda.test', 'password' => 'pass']
    );
    $this->b = $provisioner->provision(
        ['name' => 'Hard B', 'slug' => 'hardb', 'plan' => 'professional'],
        ['name' => 'B Admin', 'email' => 'admin@hardb.test', 'password' => 'pass']
    );

    $this->adminOf = fn ($tenant) => User::withoutGlobalScopes()->where('email', 'admin@'.$tenant->slug.'.test')->first();

    app(TenantManager::class)->setCurrent($this->a);
    $this->roomA = OperatingRoom::create(['name' => 'OR A1', 'room_number' => 'A1', 'or_type' => 'general']);
    app(TenantManager::class)->setCurrent($this->b);
    $this->roomB = OperatingRoom::create(['name' => 'OR B1', 'room_number' => 'B1', 'or_type' => 'general']);

    app(TenantManager::class)->setCurrent($this->a);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->a->id);
    $this->surgeon = User::factory()->forTenant($this->a)->create();
    $this->surgeon->assignRole('doctor');
});

function orPayload($room, $surgeon, string $start, string $end, string $name = 'Procedure'): array
{
    return [
        'operating_room_id' => $room->id,
        'surgeon_id' => $surgeon->id,
        'procedure_name' => $name,
        'scheduled_start' => $start,
        'scheduled_end' => $end,
    ];
}

it('refuses to double-book an operating room', function () {
    $day = now()->addDays(2)->toDateString();
    $this->actingAs(($this->adminOf)($this->a));

    $this->post('http://harda.medcore.local/operating-rooms/schedules', orPayload($this->roomA, $this->surgeon, "$day 09:00:00", "$day 11:00:00"))
        ->assertRedirect();
    expect(OrSchedule::count())->toBe(1);

    $this->post('http://harda.medcore.local/operating-rooms/schedules', orPayload($this->roomA, $this->surgeon, "$day 10:00:00", "$day 12:00:00", 'Overlap'))
        ->assertSessionHasErrors('scheduled_start');
    expect(OrSchedule::count())->toBe(1);

    // Back-to-back (starts exactly when the first ends) is allowed.
    $this->post('http://harda.medcore.local/operating-rooms/schedules', orPayload($this->roomA, $this->surgeon, "$day 11:00:00", "$day 12:00:00", 'Next'))
        ->assertSessionHasNoErrors();
    expect(OrSchedule::count())->toBe(2);
});

it('does not let a cancelled procedure block the room', function () {
    $day = now()->addDays(3)->toDateString();
    $this->actingAs(($this->adminOf)($this->a));

    $this->post('http://harda.medcore.local/operating-rooms/schedules', orPayload($this->roomA, $this->surgeon, "$day 09:00:00", "$day 11:00:00"));
    OrSchedule::first()->update(['status' => 'cancelled']);

    $this->post('http://harda.medcore.local/operating-rooms/schedules', orPayload($this->roomA, $this->surgeon, "$day 09:30:00", "$day 10:30:00", 'Reuse'))
        ->assertSessionHasNoErrors();
    expect(OrSchedule::count())->toBe(2);
});

it('rejects another tenant\'s operating room id', function () {
    $day = now()->addDays(2)->toDateString();
    $this->actingAs(($this->adminOf)($this->a));

    $this->post('http://harda.medcore.local/operating-rooms/schedules', orPayload($this->roomB, $this->surgeon, "$day 09:00:00", "$day 11:00:00"))
        ->assertSessionHasErrors('operating_room_id');
    expect(OrSchedule::withoutTenant()->count())->toBe(0);
});

it('rejects another tenant\'s patient id in tenant-scoped validation', function () {
    app(TenantManager::class)->setCurrent($this->b);
    $foreignPatient = Patient::factory()->forTenant($this->b)->create();

    $this->actingAs(($this->adminOf)($this->a));

    // Admitting to a bed / creating an invoice for a patient of another hospital must fail validation.
    $this->post('http://harda.medcore.local/invoices', ['patient_id' => $foreignPatient->id])
        ->assertSessionHasErrors('patient_id');
});

it('signs out a user who opens another tenant\'s host with a shared session', function () {
    $this->actingAs(($this->adminOf)($this->a));

    $this->get('http://harda.medcore.local/dashboard')->assertOk();

    $this->get('http://hardb.medcore.local/dashboard')
        ->assertRedirect('http://hardb.medcore.local/login');

    $this->assertGuest();
});

it('leaves super admins alone on tenant hosts', function () {
    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super->fresh());

    $this->get('http://harda.medcore.local/dashboard')->assertOk();
    $this->assertAuthenticated();
});
