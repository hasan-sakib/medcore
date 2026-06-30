<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdmin\TenantController;
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
    Route::get('/', DashboardController::class)->name('dashboard');
});
