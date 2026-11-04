<?php

use App\Http\Middleware\AuthenticateFirebaseToken;
use App\Modules\User\Http\Controllers\UserApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/firebase', [UserApiController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('auth.firebase');

Route::middleware(AuthenticateFirebaseToken::class)->group(function (): void {
    Route::get('/me', [UserApiController::class, 'show'])->name('me.show');
    Route::patch('/me/phone', [UserApiController::class, 'completePhone'])
        ->name('me.phone.update');
});
