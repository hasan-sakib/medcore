<?php

use App\Models\Patient;
use App\Models\Tenant;
use App\Support\TenantManager;
use Illuminate\Support\Facades\DB;

// Confirm PHI is stored encrypted and blind indexes are searchable.

it('stores PHI encrypted in the database', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $patient = new Patient;
    $patient->mrn = 'MRN-0001-TESTENC';
    $patient->setPhiField('first_name', 'Alice');
    $patient->setPhiField('last_name', 'Smith');
    $patient->setPhiField('date_of_birth', '1990-05-15');
    $patient->save();

    // The model accessor decrypts — returns plaintext
    expect($patient->first_name)->toBe('Alice');
    expect($patient->last_name)->toBe('Smith');

    // The raw DB value must NOT be plaintext (it's AES-256-GCM ciphertext)
    $raw = DB::table('patients')->where('id', $patient->id)->value('first_name');
    expect($raw)->not->toBe('Alice');
    expect($raw)->not->toBeNull();
});

it('round-trips PHI through encryption correctly on reload', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $patient = new Patient;
    $patient->mrn = 'MRN-0001-ROUNDTRIP';
    $patient->setPhiField('first_name', 'Bob');
    $patient->setPhiField('last_name', 'Jones');
    $patient->setPhiField('date_of_birth', '1985-11-20');
    $patient->setPhiField('phone', '+1-555-0100');
    $patient->save();

    $loaded = Patient::find($patient->id);

    expect($loaded->first_name)->toBe('Bob');
    expect($loaded->last_name)->toBe('Jones');
    expect($loaded->date_of_birth)->toBe('1985-11-20');
    expect($loaded->phone)->toBe('+1-555-0100');
});

it('computes blind index columns on save', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $patient = new Patient;
    $patient->mrn = 'MRN-0001-BLINDIDX';
    $patient->setPhiField('first_name', 'Carol');
    $patient->setPhiField('last_name', 'White');
    $patient->setPhiField('date_of_birth', '1978-03-22');
    $patient->setPhiField('national_id', 'NID12345');
    $patient->setPhiField('phone', '+44-7700-900123');
    $patient->save();

    $raw = DB::table('patients')->where('id', $patient->id)->first();

    expect($raw->first_name_index)->toBeString()->toHaveLength(64);
    expect($raw->last_name_index)->toBeString()->toHaveLength(64);
    expect($raw->national_id_index)->toBeString()->toHaveLength(64);
    expect($raw->phone_index)->toBeString()->toHaveLength(64);
});

it('produces identical blind index for same plaintext across two patients', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $p1 = new Patient;
    $p1->mrn = 'MRN-0001-SAMEA';
    $p1->setPhiField('first_name', 'Diana');
    $p1->setPhiField('last_name', 'Taylor');
    $p1->setPhiField('date_of_birth', '1992-07-01');
    $p1->save();

    $p2 = new Patient;
    $p2->mrn = 'MRN-0001-SAMEB';
    $p2->setPhiField('first_name', 'Diana');
    $p2->setPhiField('last_name', 'Taylor');
    $p2->setPhiField('date_of_birth', '1995-01-10');
    $p2->save();

    $raw1 = DB::table('patients')->where('id', $p1->id)->value('first_name_index');
    $raw2 = DB::table('patients')->where('id', $p2->id)->value('first_name_index');

    expect($raw1)->toBe($raw2);
});

it('finds patient by blind index search', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $patient = new Patient;
    $patient->mrn = 'MRN-0001-SRCH';
    $patient->setPhiField('first_name', 'Eve');
    $patient->setPhiField('last_name', 'Martin');
    $patient->setPhiField('date_of_birth', '2000-09-15');
    $patient->setPhiField('phone', '+1-555-9999');
    $patient->save();

    $result = Patient::searchByBlindIndex('phone', '+1-555-9999')->first();

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($patient->id);
    expect($result->first_name)->toBe('Eve');
});

it('does not find patient by partial phone blind index (exact match only)', function () {
    $tenant = Tenant::factory()->create();
    app(TenantManager::class)->setCurrent($tenant);

    $patient = new Patient;
    $patient->mrn = 'MRN-0001-PARTIAL';
    $patient->setPhiField('first_name', 'Frank');
    $patient->setPhiField('last_name', 'Lee');
    $patient->setPhiField('date_of_birth', '1999-12-01');
    $patient->setPhiField('phone', '+1-555-1234');
    $patient->save();

    $result = Patient::searchByBlindIndex('phone', '+1-555-12')->first();

    expect($result)->toBeNull();
});
