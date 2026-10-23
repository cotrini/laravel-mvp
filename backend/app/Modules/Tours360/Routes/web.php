<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Tours360\Http\Controllers\Tour360WebController;

/*
|--------------------------------------------------------------------------
| Tour360 Web Routes
|--------------------------------------------------------------------------
|
| Aquí registras las rutas web para el módulo de propiedades. Estas rutas
| devuelven vistas HTML, renderizan componentes de Inertia o manejan 
| formularios tradicionales con sesiones y cookies.
|
*/

// // Rutas públicas (accesibles para cualquier visitante de la web)
Route::get('/tours', [Tour360WebController::class, 'index'])->name('tours360.index');


// Rutas protegidas (ejemplo: solo para usuarios autenticados como agentes o administradores)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tours/create', [Tour360WebController::class, 'create'])->name('tours360.create');
    Route::post('/tours', [Tour360WebController::class, 'store'])->name('tours360.store');

    Route::get('/tours/{id}/edit', [Tour360WebController::class, 'edit'])->name('tours360.edit');
    Route::put('/tours/{id}', [Tour360WebController::class, 'update'])->name('tours360.update');
    Route::delete('/tours/{id}', [Tour360WebController::class, 'destroy'])->name('tours360.destroy');
    Route::put('/tours/{id}', [Tour360WebController::class, 'update'])->name('tours360.update');
});

Route::get('/tours/{id}', [Tour360WebController::class, 'show'])->name('tours360.show');
Route::get('/tours/{id}/embed', [Tour360WebController::class, 'embed'])->name('tours360.embed');
Route::get('/tours/{slug}/scene/{sceneId}', [Tour360WebController::class, 'viewer'])->name('tours360.viewer');