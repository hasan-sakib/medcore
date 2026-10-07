<?php

use App\Http\Controllers\BedController;
use App\Http\Controllers\OperatingRoomAdminController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\WardController;
use Illuminate\Support\Facades\Route;

// Routes for the facilities feature. Included from routes/web.php inside the auth+verified group.

Route::prefix('admin/facilities')->group(function () {
    // Wards / rooms / beds
    Route::middleware('permission:wards.view')->group(function () {
        Route::get('/', [WardController::class, 'index']);
    });

    Route::middleware('permission:wards.manage')->group(function () {
        Route::post('/wards', [WardController::class, 'store']);
        Route::patch('/wards/{ward}', [WardController::class, 'update']);
        Route::delete('/wards/{ward}', [WardController::class, 'destroy']);

        Route::post('/rooms', [RoomController::class, 'store']);
        Route::patch('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        Route::post('/beds', [BedController::class, 'store']);
        Route::patch('/beds/{bed}', [BedController::class, 'update']);
        Route::delete('/beds/{bed}', [BedController::class, 'destroy']);
    });

    // Operating rooms
    Route::middleware('permission:operating-rooms.view')->group(function () {
        Route::get('/operating-rooms', [OperatingRoomAdminController::class, 'index']);
    });

    Route::middleware('permission:operating-rooms.manage')->group(function () {
        Route::post('/operating-rooms', [OperatingRoomAdminController::class, 'store']);
        Route::patch('/operating-rooms/{operatingRoom}', [OperatingRoomAdminController::class, 'update']);
        Route::delete('/operating-rooms/{operatingRoom}', [OperatingRoomAdminController::class, 'destroy']);
    });
});
