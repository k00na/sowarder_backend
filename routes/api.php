<?php

use App\Http\Controllers\Api\V1\Cashier\AuthController;
use App\Http\Controllers\Api\V1\Cashier\BoardController;
use App\Http\Controllers\Api\V1\Cashier\BroadcastAuthController;
use App\Http\Controllers\Api\V1\Cashier\SessionController;
use App\Http\Controllers\Api\V1\Guest\GuestMenuController;
use App\Http\Controllers\Api\V1\Guest\GuestOrderController;
use App\Http\Middleware\EnsureCashierRestaurantEnabled;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('guest')->group(function (): void {
        Route::get('tables/{table_token}/menu', [GuestMenuController::class, 'show'])
            ->middleware('throttle:guest-menu');
        Route::post('tables/{table_token}/orders', [GuestOrderController::class, 'store'])
            ->middleware('throttle:guest-order');
    });

    Route::prefix('cashier')->group(function (): void {
        Route::post('auth/login', [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum', EnsureCashierRestaurantEnabled::class])->group(function (): void {
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::get('auth/me', [AuthController::class, 'me']);

            Route::get('board', [BoardController::class, 'index']);
            Route::get('sessions/{tableSession}', [SessionController::class, 'show']);
            Route::post('sessions/{tableSession}/accept', [SessionController::class, 'accept']);
            Route::post('sessions/{tableSession}/end', [SessionController::class, 'end']);

            Route::post('broadcasting/auth', [BroadcastAuthController::class, 'authenticate']);
        });
    });
});
