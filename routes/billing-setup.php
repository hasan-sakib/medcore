<?php

use App\Http\Controllers\ChargeItemController;
use App\Http\Controllers\InsurancePolicyController;
use App\Http\Controllers\TaxConfigController;
use Illuminate\Support\Facades\Route;

// Routes for the billing-setup feature. Included from routes/web.php inside the auth+verified group.

Route::prefix('billing')->name('billing.')->group(function () {
    // ── Charge items (price list) ────────────────────────────────────────────
    // Static routes (create) are declared before the parameterised {chargeItem} routes.
    Route::middleware('permission:charge-items.manage')
        ->prefix('charge-items')
        ->name('charge-items.')
        ->group(function () {
            Route::get('/', [ChargeItemController::class, 'index'])->name('index');
            Route::get('create', [ChargeItemController::class, 'create'])->name('create');
            Route::post('/', [ChargeItemController::class, 'store'])->name('store');
            Route::get('{chargeItem}/edit', [ChargeItemController::class, 'edit'])->name('edit');
            Route::put('{chargeItem}', [ChargeItemController::class, 'update'])->name('update');
            Route::delete('{chargeItem}', [ChargeItemController::class, 'destroy'])->name('destroy');
        });

    // ── Tax configurations ───────────────────────────────────────────────────
    Route::middleware('permission:tax-configs.manage')
        ->prefix('tax-configs')
        ->name('tax-configs.')
        ->group(function () {
            Route::get('/', [TaxConfigController::class, 'index'])->name('index');
            Route::get('create', [TaxConfigController::class, 'create'])->name('create');
            Route::post('/', [TaxConfigController::class, 'store'])->name('store');
            Route::get('{taxConfig}/edit', [TaxConfigController::class, 'edit'])->name('edit');
            Route::put('{taxConfig}', [TaxConfigController::class, 'update'])->name('update');
            Route::delete('{taxConfig}', [TaxConfigController::class, 'destroy'])->name('destroy');
        });

    // ── Insurance policies ───────────────────────────────────────────────────
    Route::prefix('insurance-policies')->name('insurance-policies.')->group(function () {
        // Write access (declared first: static paths before {insurancePolicy}).
        Route::middleware('permission:insurance-policies.manage')->group(function () {
            Route::get('create', [InsurancePolicyController::class, 'create'])->name('create');
            Route::get('patient-search', [InsurancePolicyController::class, 'patientSearch'])->name('patient-search');
            Route::post('/', [InsurancePolicyController::class, 'store'])->name('store');
            Route::get('{insurancePolicy}/edit', [InsurancePolicyController::class, 'edit'])->name('edit');
            Route::put('{insurancePolicy}', [InsurancePolicyController::class, 'update'])->name('update');
            Route::delete('{insurancePolicy}', [InsurancePolicyController::class, 'destroy'])->name('destroy');
        });

        // Read-only list: billing staff (invoices.view) may look up a patient's policies.
        Route::middleware('permission:invoices.view|insurance-policies.manage')
            ->get('/', [InsurancePolicyController::class, 'index'])->name('index');
    });
});
