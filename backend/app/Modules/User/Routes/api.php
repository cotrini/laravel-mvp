<?php

use App\Http\Middleware\AuthenticateFirebaseToken;
use App\Modules\User\Http\Controllers\Auth\ApiAuthController;
use App\Modules\User\Http\Controllers\UserApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/firebase', [UserApiController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('auth.firebase');

Route::post('/auth/register', [ApiAuthController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('auth.register');
Route::post('/auth/login', [ApiAuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('auth.login');
Route::post('/auth/email/resend', [ApiAuthController::class, 'resendVerification'])
    ->middleware('throttle:6,1')
    ->name('auth.email.resend');

Route::middleware(AuthenticateFirebaseToken::class)->group(function (): void {
    Route::post('/auth/logout', [ApiAuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [UserApiController::class, 'show'])->name('me.show');
    Route::patch('/me/phone', [UserApiController::class, 'completePhone'])
        ->name('me.phone.update');
});
