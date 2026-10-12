<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard Web Routes
|--------------------------------------------------------------------------
|
| Aquí registras las rutas web para el módulo de dashboard. Estas rutas
| están protegidas por autenticación para que solo los usuarios registrados
| puedan acceder a sus paneles de control.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
});