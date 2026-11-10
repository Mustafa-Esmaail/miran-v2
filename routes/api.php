<?php

use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderItemController;
use App\Http\Controllers\Webhooks\MoyasarWebhookController;
use App\Http\Controllers\Webhooks\TabbyWebhookController;
use App\Http\Controllers\Webhooks\TamaraWebhookController;
use App\Http\Controllers\Webhooks\ZoomWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/moyasar', MoyasarWebhookController::class);
Route::post('/webhooks/tamara', TamaraWebhookController::class);
Route::post('/webhooks/tabby', TabbyWebhookController::class);
Route::post('/webhooks/zoom', ZoomWebhookController::class);

// Read-only API for external systems, authenticated by per-client API keys (see ApiClient).
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['api.client', 'throttle:api-clients'])
    ->group(function (): void {
        Route::middleware('api.ability:orders')->group(function (): void {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{orderNo}', [OrderController::class, 'show'])->name('orders.show');
            Route::get('/orders/{orderNo}/items', [OrderItemController::class, 'index'])->name('orders.items.index');
        });
    });
