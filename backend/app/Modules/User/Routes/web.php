<?php

use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\UserWebController;

Route::middleware('web')->group(function () {
    // Rutas de Registro
    Route::get('/register', [UserWebController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [UserWebController::class, 'register']);

    // Rutas de Login
    Route::get('/login', [UserWebController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UserWebController::class, 'login']);

    // Ruta de Logout
    Route::post('/logout', [UserWebController::class, 'logout'])->name('logout');
});