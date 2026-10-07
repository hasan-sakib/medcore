<?php

use App\Http\Controllers\AnalyticsDashboardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\BedAllocationController;
use App\Http\Controllers\BedBoardController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ClinicalNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DispenseController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\EncounterController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicineBatchController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\OperatingRoomController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPortalAccountController;
use App\Http\Controllers\PharmacyDashboardController;
use App\Http\Controllers\Portal\AppointmentController as PortalAppointmentController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PublicController;
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

// ── Public site (central domain only — medcore.local / localhost) ────────────
Route::middleware('central-only')->name('public.')->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('home');
    Route::get('/hospitals', [PublicController::class, 'hospitals'])->name('hospitals');
    Route::get('/hospitals/{slug}', [PublicController::class, 'hospitalShow'])->name('hospitals.show');
    Route::get('/doctors', [PublicController::class, 'doctors'])->name('doctors');
    Route::get('/book-appointment', [PublicController::class, 'appointmentForm'])->name('appointment.book');
    Route::post('/book-appointment', [PublicController::class, 'appointmentStore'])->name('appointment.store');
    Route::get('/appointment-confirmed', [PublicController::class, 'appointmentConfirm'])->name('appointment.confirm');
});

// ── Auth ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.login.store');
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

// ── Patient Portal ───────────────────────────────────────────────────────────
Route::prefix('portal')
    ->name('portal.')
    ->middleware(['auth', 'patient-portal'])
    ->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');

        Route::get('appointments', [PortalAppointmentController::class, 'index'])->name('appointments.index');
        Route::get('appointments/book', [PortalAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [PortalAppointmentController::class, 'store'])->name('appointments.store');
        Route::patch('appointments/{appointment}/cancel', [PortalAppointmentController::class, 'cancel'])->name('appointments.cancel');

        Route::get('invoices', [PortalInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', [PortalInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

        Route::get('profile', [PortalProfileController::class, 'show'])->name('profile');
        Route::patch('profile', [PortalProfileController::class, 'update'])->name('profile.update');
    });

// ── Tenant-scoped authenticated routes ──────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

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
            Route::post('patients/{patient}/portal-account', [PatientPortalAccountController::class, 'store'])
                ->name('patients.portal-account.store');
            Route::delete('patients/{patient}/portal-account', [PatientPortalAccountController::class, 'destroy'])
                ->name('patients.portal-account.destroy');
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
    // create/store must be declared BEFORE the {prescription} show route, otherwise
    // 'create' is matched as a prescription id and 404s.
    Route::middleware('permission:prescriptions.create')
        ->group(function () {
            Route::get('prescriptions/create', [PrescriptionController::class, 'create'])->name('prescriptions.create');
            Route::post('prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
        });

    Route::middleware('permission:prescriptions.view')
        ->group(function () {
            Route::resource('prescriptions', PrescriptionController::class)->only(['index', 'show']);
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

    // ── Phase 5: Analytics ───────────────────────────────────────────────────
    Route::middleware('permission:reports.view')
        ->group(function () {
            Route::get('analytics', AnalyticsDashboardController::class)->name('analytics.dashboard');
        });

    // ── Phase 5: Invoices ─────────────────────────────────────────────────────
    // create/store must come before {invoice} to prevent 'create' being matched as a parameter
    Route::middleware('permission:invoices.create')
        ->group(function () {
            Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        });

    Route::middleware('permission:invoices.view')
        ->group(function () {
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        });

    Route::middleware('permission:invoices.edit')
        ->group(function () {
            Route::post('invoices/{invoice}/lines', [InvoiceController::class, 'addLine'])->name('invoices.lines.store');
            Route::patch('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
            Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
        });

    // ── Phase 5: Claims ───────────────────────────────────────────────────────
    Route::middleware('permission:claims.view')
        ->group(function () {
            Route::get('claims', [ClaimController::class, 'index'])->name('claims.index');
        });

    Route::middleware('permission:claims.create')
        ->group(function () {
            Route::post('claims', [ClaimController::class, 'store'])->name('claims.store');
            Route::patch('claims/{claim}/status', [ClaimController::class, 'updateStatus'])->name('claims.update-status');
        });

    // ── Phase 4: Bed Board ────────────────────────────────────────────────────
    Route::middleware('permission:beds.view')
        ->group(function () {
            Route::get('beds/board', [BedBoardController::class, 'index'])->name('beds.board');
            Route::post('beds/{bed}/available', [BedBoardController::class, 'markAvailable'])
                ->name('beds.available');
            Route::post('beds/{bed}/maintenance', [BedBoardController::class, 'toggleMaintenance'])
                ->name('beds.maintenance');
        });

    Route::middleware('permission:bed-allocations.create')
        ->group(function () {
            Route::post('bed-allocations', [BedAllocationController::class, 'store'])
                ->name('bed-allocations.store');
        });

    Route::middleware('permission:bed-allocations.edit')
        ->group(function () {
            Route::patch('bed-allocations/{bedAllocation}/discharge', [BedAllocationController::class, 'discharge'])
                ->name('bed-allocations.discharge');
        });

    // ── Phase 4: Operating Rooms ─────────────────────────────────────────────
    Route::middleware('permission:operating-rooms.view')
        ->group(function () {
            Route::get('operating-rooms', [OperatingRoomController::class, 'index'])->name('operating-rooms.index');
        });

    Route::middleware('permission:or-schedules.manage')
        ->group(function () {
            Route::post('operating-rooms/schedules', [OperatingRoomController::class, 'storeSchedule'])
                ->name('or-schedules.store');
            Route::patch('operating-rooms/schedules/{orSchedule}/status', [OperatingRoomController::class, 'updateScheduleStatus'])
                ->name('or-schedules.update-status');
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

// ── Feature route files (included inside auth + verified) ──────────────────────
Route::middleware(['auth', 'verified'])->group(base_path('routes/facilities.php'));
Route::middleware(['auth', 'verified'])->group(base_path('routes/access.php'));
Route::middleware(['auth', 'verified'])->group(base_path('routes/billing-setup.php'));
