<?php

use App\Modules\User\Http\Controllers\Auth\FirebaseAuthController;
use App\Modules\User\Http\Controllers\Auth\PasswordResetController;
use App\Modules\User\Http\Controllers\UserWebController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Rutas de Registro
    Route::get('/register', [UserWebController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [UserWebController::class, 'register']);
    Route::get('/email/verify', [UserWebController::class, 'showEmailVerificationNotice'])
        ->middleware('auth')
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [UserWebController::class, 'verifyEmail'])
        ->middleware(['auth', 'signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [UserWebController::class, 'resendEmailVerification'])
        ->middleware(['auth', 'throttle:6,1'])
        ->name('verification.send');

    // Rutas de Login
    Route::get('/login', [UserWebController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UserWebController::class, 'login']);
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])
        ->middleware('guest')
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
        ->middleware(['guest', 'throttle:6,1'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->middleware('guest')
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware(['guest', 'throttle:6,1'])
        ->name('password.update');

    // Ruta de autenticación con Firebase
    Route::post('/firebase-auth', [FirebaseAuthController::class, 'authenticate'])->name('firebase-auth');

    // Ruta de Logout
    Route::post('/logout', [UserWebController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/completar-telefono', [UserWebController::class, 'showCompletePhoneForm'])
            ->name('profile.phone.show');
        Route::post('/completar-telefono', [UserWebController::class, 'completePhone'])
            ->name('profile.phone.store');
    });
});

Route::middleware(['web', 'auth', 'verified', 'can:manage-users'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserWebController::class, 'adminIndex'])->name('users.index');
        Route::post('/users', [UserWebController::class, 'adminStore'])->name('users.store');
        Route::put('/users/{managedUser}', [UserWebController::class, 'adminUpdate'])->name('users.update');
        Route::patch('/users/{managedUser}/ban', [UserWebController::class, 'toggleBan'])->name('users.ban');
        Route::post('/users/{managedUser}/properties', [UserWebController::class, 'assignProperty'])
            ->name('users.properties.store');
        Route::delete('/users/{managedUser}/properties/{property}', [UserWebController::class, 'removeAssignedProperty'])
            ->name('users.properties.destroy');
    });
