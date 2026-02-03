<?php

use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProviderCallbackController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')
    ->name('notifications.')
    ->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])
            ->name('index');

        Route::post('/', [NotificationController::class, 'store'])
            ->name('store');

        Route::get('/{notification}', [NotificationController::class, 'show'])
            ->whereUuid('notification')
            ->name('show');

        Route::post('/{notification}/cancel', [NotificationController::class, 'cancel'])
            ->whereUuid('notification')
            ->name('cancel');

        Route::get('/batch/{batchId}', [NotificationController::class, 'batch'])
            ->whereUuid('batchId')
            ->name('batch.show');

        Route::post('/batch', [NotificationController::class, 'storeBatch'])
            ->name('batch.store');
    });

Route::prefix('templates')
    ->name('templates.')
    ->group(function (): void {
        Route::get('/', [TemplateController::class, 'index'])
            ->name('index');

        Route::post('/', [TemplateController::class, 'store'])
            ->name('store');

        Route::post('/validate', [TemplateController::class, 'validateTemplate'])
            ->name('validate');

        Route::get('/{template}', [TemplateController::class, 'show'])
            ->whereUuid('template')
            ->name('show');

        Route::patch('/{template}', [TemplateController::class, 'update'])
            ->whereUuid('template')
            ->name('update');

        Route::delete('/{template}', [TemplateController::class, 'destroy'])
            ->whereUuid('template')
            ->name('destroy');
    });

Route::prefix('providers')
    ->name('providers.')
    ->group(function (): void {
        Route::post('/{provider}/callbacks', [ProviderCallbackController::class, 'store'])
            ->name('callbacks');
    });

Route::get('/metrics', [MetricsController::class, 'index'])
    ->name('metrics.index');
