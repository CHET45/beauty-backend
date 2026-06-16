<?php

use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

Route::apiResource('services', ServiceController::class);