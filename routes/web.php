<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('ui');

Route::get('/health', [HealthController::class, 'show'])
    ->name('health.show');
