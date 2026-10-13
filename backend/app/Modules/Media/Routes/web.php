<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Media\Http\Controllers\MediaWebController;

/*
|--------------------------------------------------------------------------
| Media Web Routes
|--------------------------------------------------------------------------
|
| Aquí registras las rutas web para el módulo de media. Estas rutas
| están protegidas por autenticación para que solo los usuarios registrados
| puedan acceder a sus paneles de control.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/media', [MediaWebController::class, 'index'])->name('media.index');
    Route::get('/media/create', [MediaWebController::class, 'create'])->name('media.create');
    Route::post('/media', [MediaWebController::class, 'store'])->name('media.store');
    Route::get('/media/{id}', [MediaWebController::class, 'show'])->name('media.show');
    Route::get('/media/{id}/edit', [MediaWebController::class, 'edit'])->name('media.edit');
    Route::put('/media/{id}', [MediaWebController::class, 'update'])->name('media.update');
    Route::delete('/media/{id}', [MediaWebController::class, 'destroy'])->name('media.destroy');
});