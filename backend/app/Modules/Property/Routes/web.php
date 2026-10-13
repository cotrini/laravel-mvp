<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Property\Http\Controllers\PropertyWebController;

/*
|--------------------------------------------------------------------------
| Property Web Routes
|--------------------------------------------------------------------------
|
| Aquí registras las rutas web para el módulo de propiedades. Estas rutas
| devuelven vistas HTML, renderizan componentes de Inertia o manejan 
| formularios tradicionales con sesiones y cookies.
|
*/

// Rutas públicas (accesibles para cualquier visitante de la web)
Route::get('/properties', [PropertyWebController::class, 'index'])->name('properties.index');


// Rutas protegidas (ejemplo: solo para usuarios autenticados como agentes o administradores)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/properties/create', [PropertyWebController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyWebController::class, 'store'])->name('properties.store');
    
    Route::get('/properties/{id}/edit', [PropertyWebController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{id}', [PropertyWebController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{id}', [PropertyWebController::class, 'destroy'])->name('properties.destroy');
    Route::put('/properties/{id}', [PropertyWebController::class, 'update'])->name('properties.update');
});

Route::get('/properties/{id}', [PropertyWebController::class, 'show'])->name('properties.show');