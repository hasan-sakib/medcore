<?php

use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\OperatingRoom;
use App\Models\Room;
use App\Models\User;
use App\Models\Ward;
use App\Services\PatientService;
use App\Services\TenantProvisioningService;
use App\Support\TenantManager;
use Inertia\Testing\AssertableInertia as Assert;

const FAC_HOST = 'http://facha.medcore.local/admin/facilities';
const FAC_HOST_B = 'http://fachb.medcore.local/admin/facilities';

beforeEach(function () {
    $provisioner = app(TenantProvisioningService::class);

    $this->tenant = $provisioner->provision(
        ['name' => 'Facilities A', 'slug' => 'facha', 'plan' => 'professional'],
        ['name' => 'FA Admin', 'email' => 'admin@facha.test', 'password' => 'pass']
    );
    $this->tenantB = $provisioner->provision(
        ['name' => 'Facilities B', 'slug' => 'fachb', 'plan' => 'professional'],
        ['name' => 'FB Admin', 'email' => 'admin@fachb.test', 'password' => 'pass']
    );

    app(TenantManager::class)->setCurrent($this->tenant);

    $this->admin = User::factory()->forTenant($this->tenant)->create();
    $this->admin->assignRole('tenant-admin');
    $this->admin = $this->admin->fresh();

    $this->nurse = User::factory()->forTenant($this->tenant)->create();
    $this->nurse->assignRole('nurse');
    $this->nurse = $this->nurse->fresh();
});

function facWard(string $code = 'GWA'): Ward
{
    return Ward::create(['name' => "Ward {$code}", 'code' => $code, 'floor' => '1', 'ward_type' => 'general', 'is_active' => true]);
}

it('admin can create a ward, then a room, then a bed', function () {
    $this->actingAs($this->admin);

    $this->post(FAC_HOST.'/wards', [
        'name' => 'General Ward A', 'code' => 'GWA', 'floor' => '1', 'ward_type' => 'general',
    ])->assertRedirect();

    $ward = Ward::where('code', 'GWA')->firstOrFail();
    expect($ward->tenant_id)->toBe($this->tenant->id)->and($ward->is_active)->toBeTrue();

    $this->patch(FAC_HOST."/wards/{$ward->id}", [
        'name' => 'General Ward A2', 'code' => 'GWA', 'floor' => '2', 'ward_type' => 'general',
    ])->assertSessionHasNoErrors();
    expect($ward->fresh()->name)->toBe('General Ward A2');

    $this->post(FAC_HOST.'/rooms', [
        'ward_id' => $ward->id, 'room_number' => 'GWA-R01', 'room_type' => 'general',
    ])->assertRedirect();

    $room = Room::where('room_number', 'GWA-R01')->firstOrFail();
    expect($room->ward_id)->toBe($ward->id);

    $this->post(FAC_HOST.'/beds', [
        'ward_id' => $ward->id, 'room_id' => $room->id, 'bed_number' => 'GWA-001', 'bed_type' => 'standard',
    ])->assertRedirect();

    $bed = Bed::where('bed_number', 'GWA-001')->firstOrFail();
    expect($bed->room_id)->toBe($room->id)
        ->and($bed->status)->toBe('available')
        ->and($bed->tenant_id)->toBe($this->tenant->id);
});

it('admin can create, update and delete an operating room', function () {
    $this->actingAs($this->admin);

    $this->post(FAC_HOST.'/operating-rooms', [
        'name' => 'Theatre 1', 'room_number' => 'OR-01', 'or_type' => 'general',
    ])->assertRedirect();

    $or = OperatingRoom::where('room_number', 'OR-01')->firstOrFail();

    $this->patch(FAC_HOST."/operating-rooms/{$or->id}", [
        'name' => 'Theatre One', 'room_number' => 'OR-01', 'or_type' => 'cardiac', 'status' => 'maintenance',
    ])->assertRedirect();

    expect($or->fresh()->name)->toBe('Theatre One')->and($or->fresh()->status)->toBe('maintenance');

    $this->delete(FAC_HOST."/operating-rooms/{$or->id}")->assertRedirect();
    expect(OperatingRoom::find($or->id))->toBeNull();
});

it('nurse cannot manage or view facilities admin', function () {
    $ward = facWard();
    $this->actingAs($this->nurse);

    $this->get(FAC_HOST)->assertForbidden();
    $this->post(FAC_HOST.'/wards', ['name' => 'X', 'code' => 'X', 'ward_type' => 'general'])->assertForbidden();
    $this->post(FAC_HOST.'/beds', ['ward_id' => $ward->id, 'bed_number' => 'B1', 'bed_type' => 'standard'])->assertForbidden();
    $this->post(FAC_HOST.'/operating-rooms', ['name' => 'X', 'room_number' => 'X', 'or_type' => 'general'])->assertForbidden();

    expect(Ward::count())->toBe(1)->and(Bed::count())->toBe(0);
});

it('rejects a duplicate bed number within a tenant but allows it in another tenant', function () {
    $ward = facWard();
    Bed::create(['ward_id' => $ward->id, 'bed_number' => 'DUP-1', 'bed_type' => 'standard', 'status' => 'available', 'is_active' => true]);

    $this->actingAs($this->admin);
    $this->post(FAC_HOST.'/beds', ['ward_id' => $ward->id, 'bed_number' => 'DUP-1', 'bed_type' => 'standard'])
        ->assertSessionHasErrors('bed_number');
    expect(Bed::count())->toBe(1);

    // Same number in tenant B is fine.
    app(TenantManager::class)->setCurrent($this->tenantB);
    $wardB = Ward::create(['name' => 'B Ward', 'code' => 'BW', 'ward_type' => 'general', 'is_active' => true]);
    $adminB = User::factory()->forTenant($this->tenantB)->create();
    $adminB->assignRole('tenant-admin');

    $this->actingAs($adminB->fresh());
    $this->post(FAC_HOST_B.'/beds', ['ward_id' => $wardB->id, 'bed_number' => 'DUP-1', 'bed_type' => 'standard'])
        ->assertSessionHasNoErrors();
    expect(Bed::where('bed_number', 'DUP-1')->count())->toBe(1);
});

it('cannot delete an occupied bed, nor one with allocation history', function () {
    $ward = facWard();
    $bed = Bed::create(['ward_id' => $ward->id, 'bed_number' => 'OCC-1', 'bed_type' => 'standard', 'status' => 'available', 'is_active' => true]);

    $this->actingAs($this->admin);
    $patient = app(PatientService::class)->create([
        'first_name' => 'Jane', 'last_name' => 'Doe', 'date_of_birth' => '1975-04-20',
    ]);

    $allocation = BedAllocation::create([
        'bed_id' => $bed->id, 'patient_id' => $patient->id, 'allocated_by' => $this->admin->id, 'admitted_at' => now(),
    ]);
    $bed->update(['status' => 'occupied']);

    $this->delete(FAC_HOST."/beds/{$bed->id}")->assertSessionHas('error');
    expect(Bed::find($bed->id))->not->toBeNull();

    // Occupied beds cannot be deactivated or have status forced either.
    $this->patch(FAC_HOST."/beds/{$bed->id}", [
        'ward_id' => $ward->id, 'bed_number' => 'OCC-1', 'bed_type' => 'standard', 'status' => 'available', 'is_active' => false,
    ])->assertSessionHas('error');
    expect($bed->fresh()->status)->toBe('occupied')->and($bed->fresh()->is_active)->toBeTrue();

    // After discharge it has history, so deletion is still refused.
    $allocation->update(['discharged_at' => now()]);
    $bed->update(['status' => 'available']);
    $this->delete(FAC_HOST."/beds/{$bed->id}")->assertSessionHas('error');
    expect(Bed::find($bed->id))->not->toBeNull();

    // ...but it can be deactivated.
    $this->patch(FAC_HOST."/beds/{$bed->id}", [
        'ward_id' => $ward->id, 'bed_number' => 'OCC-1', 'bed_type' => 'standard', 'is_active' => false,
    ])->assertSessionHasNoErrors();
    expect($bed->fresh()->is_active)->toBeFalse();
});

it('deletes an unused bed and refuses to delete a ward that still has rooms or beds', function () {
    $ward = facWard();
    $bed = Bed::create(['ward_id' => $ward->id, 'bed_number' => 'FREE-1', 'bed_type' => 'standard', 'status' => 'available', 'is_active' => true]);

    $this->actingAs($this->admin);

    $this->delete(FAC_HOST."/wards/{$ward->id}")->assertSessionHas('error');
    expect(Ward::find($ward->id))->not->toBeNull();

    $this->delete(FAC_HOST."/beds/{$bed->id}")->assertSessionHas('success');
    expect(Bed::find($bed->id))->toBeNull();

    $this->delete(FAC_HOST."/wards/{$ward->id}")->assertSessionHas('success');
    expect(Ward::find($ward->id))->toBeNull();
});

it('tenant A cannot see or edit tenant B facilities', function () {
    app(TenantManager::class)->setCurrent($this->tenantB);
    $wardB = Ward::create(['name' => 'B Ward', 'code' => 'BW', 'ward_type' => 'general', 'is_active' => true]);
    $roomB = Room::create(['ward_id' => $wardB->id, 'room_number' => 'BW-R1', 'room_type' => 'general', 'is_active' => true]);
    $bedB = Bed::create(['ward_id' => $wardB->id, 'room_id' => $roomB->id, 'bed_number' => 'BW-1', 'bed_type' => 'standard', 'status' => 'available', 'is_active' => true]);
    $orB = OperatingRoom::create(['name' => 'B OR', 'room_number' => 'BOR-1', 'or_type' => 'general', 'status' => 'available', 'is_active' => true]);

    app(TenantManager::class)->setCurrent($this->tenant);
    $wardA = facWard('AW');

    $this->actingAs($this->admin);

    // Listing only shows tenant A's ward.
    $this->get(FAC_HOST)->assertInertia(fn (Assert $page) => $page
        ->component('Facilities/Index')
        ->has('wards', 1)
        ->where('wards.0.id', $wardA->id));

    // Direct access to tenant B's records 404s.
    $this->patch(FAC_HOST."/wards/{$wardB->id}", ['name' => 'Hacked', 'code' => 'BW', 'ward_type' => 'general'])->assertNotFound();
    $this->delete(FAC_HOST."/wards/{$wardB->id}")->assertNotFound();
    $this->patch(FAC_HOST."/rooms/{$roomB->id}", ['ward_id' => $wardA->id, 'room_number' => 'BW-R1', 'room_type' => 'general'])->assertNotFound();
    $this->patch(FAC_HOST."/beds/{$bedB->id}", ['ward_id' => $wardA->id, 'bed_number' => 'BW-1', 'bed_type' => 'standard'])->assertNotFound();
    $this->delete(FAC_HOST."/beds/{$bedB->id}")->assertNotFound();
    $this->patch(FAC_HOST."/operating-rooms/{$orB->id}", ['name' => 'Hacked', 'room_number' => 'BOR-1', 'or_type' => 'general'])->assertNotFound();
    $this->delete(FAC_HOST."/operating-rooms/{$orB->id}")->assertNotFound();

    // Cannot attach a new bed/room to tenant B's ward.
    $this->post(FAC_HOST.'/rooms', ['ward_id' => $wardB->id, 'room_number' => 'EVIL', 'room_type' => 'general'])
        ->assertSessionHasErrors('ward_id');
    $this->post(FAC_HOST.'/beds', ['ward_id' => $wardB->id, 'bed_number' => 'EVIL', 'bed_type' => 'standard'])
        ->assertSessionHasErrors('ward_id');

    app(TenantManager::class)->setCurrent($this->tenantB);
    expect($wardB->fresh()->name)->toBe('B Ward')
        ->and(Room::where('room_number', 'EVIL')->count())->toBe(0)
        ->and(Bed::where('bed_number', 'EVIL')->count())->toBe(0);
});
