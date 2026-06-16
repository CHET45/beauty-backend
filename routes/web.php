<?php

use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('services.index');
});

Route::resource('services', ServiceController::class)->except(['show']);