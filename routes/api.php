<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\AvailableSlotController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:public-api')->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/available-slots', AvailableSlotController::class);
    Route::get('/appointments', [AppointmentController::class, 'index']);
});

Route::post('/appointments', [AppointmentController::class, 'store'])
    ->middleware('throttle:booking');

Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware('throttle:admin-login');

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('services', AdminServiceController::class)->except(['show']);

    Route::get('/appointments', [AdminAppointmentController::class, 'index']);
    Route::patch('/appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus']);
    Route::delete('/appointments/{appointment}', [AdminAppointmentController::class, 'destroy']);
});
