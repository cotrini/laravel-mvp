<?php

use App\Modules\User\Http\Controllers\UserWebController;
use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\Auth\FirebaseAuthController;

Route::middleware('web')->group(function () {
    // Rutas de Registro
    Route::get('/register', [UserWebController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [UserWebController::class, 'register']);

    // Rutas de Login
    Route::get('/login', [UserWebController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UserWebController::class, 'login']);

    // Ruta de autenticación con Firebase
    Route::post('/firebase-auth', [FirebaseAuthController::class, 'authenticate'])->name('firebase-auth');

    // Ruta de Logout
    Route::post('/logout', [UserWebController::class, 'logout'])->name('logout');
});

Route::middleware(['web', 'auth', 'verified', 'can:manage-users'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserWebController::class, 'adminIndex'])->name('users.index');
        Route::post('/users', [UserWebController::class, 'adminStore'])->name('users.store');
        Route::put('/users/{managedUser}/filmmaker-properties', [UserWebController::class, 'assignProperties'])
            ->name('users.properties.update');
    });
