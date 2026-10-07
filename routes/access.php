<?php

use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Routes for the access feature. Included from routes/web.php inside the auth+verified group.
// (The unauthenticated /two-factor-challenge routes live in the `guest` group in web.php.)

// ── Profile / security (any authenticated user) ──────────────────────────────
Route::get('profile/security', [SecurityController::class, 'show'])->name('profile.security');
Route::post('profile/security/two-factor', [SecurityController::class, 'enable'])->name('profile.security.enable');
Route::post('profile/security/two-factor/confirm', [SecurityController::class, 'confirm'])->name('profile.security.confirm');
Route::post('profile/security/two-factor/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('profile.security.recovery-codes');
Route::delete('profile/security/two-factor', [SecurityController::class, 'disable'])->name('profile.security.disable');

// ── Tenant user & role management ────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    // Static routes first, then parameterised.
    Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('admin.users.index');
    Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('admin.users.create');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('admin.users.store');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])->whereNumber('id')->middleware('permission:users.edit')->name('admin.users.edit');
    Route::patch('users/{id}', [UserController::class, 'update'])->whereNumber('id')->middleware('permission:users.edit')->name('admin.users.update');
    Route::post('users/{id}/restore', [UserController::class, 'restore'])->whereNumber('id')->middleware('permission:users.edit')->name('admin.users.restore');
    Route::delete('users/{id}', [UserController::class, 'destroy'])->whereNumber('id')->middleware('permission:users.delete')->name('admin.users.destroy');

    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.manage')->name('admin.roles.index');
    Route::put('roles/{id}', [RoleController::class, 'update'])->whereNumber('id')->middleware('permission:roles.manage')->name('admin.roles.update');
});
