<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DispenseController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\MedicineBatchController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyDashboardController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\VitalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — MedCore
|--------------------------------------------------------------------------
| All routes inherit the IdentifyTenant + HandleInertiaRequests middleware
| registered globally in bootstrap/app.php.
|
| Super-admin routes are resolved from admin.medcore.local; the
| IdentifyTenant middleware leaves $tenant = null for that subdomain.
*/

// ── Auth ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ── Super Admin (admin.medcore.local — tenant_id = null) ─────────────────────
Route::prefix('super-admin')
    ->name('super-admin.')
    ->middleware(['auth', 'super-admin'])
    ->group(function () {
        Route::resource('tenants', TenantController::class);
    });

// ── Tenant-scoped authenticated routes ──────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::redirect('/', '/dashboard');

    // ── Phase 2 + 3: Admin-only management (tenant-admin role required) ─────
    Route::middleware('role:tenant-admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('departments', DepartmentController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::resource('doctor-schedules', DoctorScheduleController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            // Phase 3: medicine management (write ops admin-only)
            Route::resource('medicines', MedicineController::class)
                ->only(['create', 'store', 'edit', 'update', 'destroy']);

            Route::resource('suppliers', SupplierController::class);

            Route::resource('purchase-orders', PurchaseOrderController::class)
                ->only(['index', 'create', 'store', 'show', 'update']);
        });

    // ── Phase 2: Patients ────────────────────────────────────────────────────
    Route::middleware('permission:patients.view')
        ->group(function () {
            Route::resource('patients', PatientController::class);
        });

    // ── Phase 2: Appointments ────────────────────────────────────────────────
    // Slots route must be declared BEFORE the resource to prevent Laravel
    // routing 'slots' as the {appointment} parameter.
    Route::middleware('permission:appointments.view')
        ->group(function () {
            Route::get('appointments/slots', [AppointmentController::class, 'slots'])
                ->name('appointments.slots');

            Route::resource('appointments', AppointmentController::class);
        });

    // ── Phase 3: Medicine catalog (view-only, broader permission) ────────────
    Route::middleware('permission:medicines.view')
        ->group(function () {
            Route::get('medicines', [MedicineController::class, 'index'])->name('medicines.index');
            Route::get('medicines/{medicine}', [MedicineController::class, 'show'])->name('medicines.show');
            Route::get('pharmacy/dashboard', [PharmacyDashboardController::class, 'index'])->name('pharmacy.dashboard');
        });

    // ── Phase 3: Batch intake (GRN) ──────────────────────────────────────────
    Route::middleware('permission:medicine-batches.create')
        ->group(function () {
            Route::get('medicine-batches', [MedicineBatchController::class, 'index'])->name('medicine-batches.index');
            Route::get('medicine-batches/create', [MedicineBatchController::class, 'create'])->name('medicine-batches.create');
            Route::post('medicine-batches', [MedicineBatchController::class, 'store'])->name('medicine-batches.store');
        });

    // ── Phase 3: Prescriptions ────────────────────────────────────────────────
    Route::middleware('permission:prescriptions.view')
        ->group(function () {
            Route::resource('prescriptions', PrescriptionController::class)->only(['index', 'show']);
        });

    Route::middleware('permission:prescriptions.create')
        ->group(function () {
            Route::get('prescriptions/create', [PrescriptionController::class, 'create'])->name('prescriptions.create');
            Route::post('prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
        });

    // ── Phase 3: Dispense POS ─────────────────────────────────────────────────
    Route::middleware('permission:dispense-records.create')
        ->group(function () {
            Route::get('pharmacy/dispense', [DispenseController::class, 'index'])->name('pharmacy.dispense');
            Route::post('pharmacy/dispense', [DispenseController::class, 'store'])->name('pharmacy.dispense.store');
        });

    // ── Phase 3: Stock movement audit trail ───────────────────────────────────
    Route::middleware('permission:stock-movements.view')
        ->group(function () {
            Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
        });

    // ── Phase 2: Encounters + nested sub-resources ───────────────────────────
    Route::middleware('permission:encounters.view')
        ->group(function () {
            Route::resource('encounters', EncounterController::class)
                ->only(['index', 'store', 'show', 'update']);

            Route::prefix('encounters/{encounter}')
                ->name('encounters.')
                ->group(function () {
                    Route::post('clinical-notes', [ClinicalNoteController::class, 'store'])
                        ->name('clinical-notes.store')
                        ->middleware('permission:clinical-notes.create');

                    Route::patch('clinical-notes/{clinicalNote}', [ClinicalNoteController::class, 'update'])
                        ->name('clinical-notes.update')
                        ->middleware('permission:clinical-notes.edit');

                    Route::delete('clinical-notes/{clinicalNote}', [ClinicalNoteController::class, 'destroy'])
                        ->name('clinical-notes.destroy')
                        ->middleware('permission:clinical-notes.edit');

                    Route::post('vitals', [VitalController::class, 'store'])
                        ->name('vitals.store')
                        ->middleware('permission:vitals.create');

                    Route::patch('vitals/{vital}', [VitalController::class, 'update'])
                        ->name('vitals.update')
                        ->middleware('permission:vitals.edit');
                });
        });
});
