<?php

use App\Http\Controllers\OwnerInviteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('signed')->group(function (): void {
    Route::get('/owner-invite/{user}', [OwnerInviteController::class, 'show'])
        ->name('owner-invite.show');
    Route::post('/owner-invite/{user}', [OwnerInviteController::class, 'store'])
        ->name('owner-invite.store');
});
