<?php

use App\Modules\Property\Http\Controllers\PropertyWebController;
use App\Modules\Property\Http\Controllers\LocationCatalogController;
use Illuminate\Support\Facades\Route;

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
Route::get('/properties/{property}/blueprint', [PropertyWebController::class, 'showBlueprint'])
    ->name('properties.blueprint.show');
Route::post('/properties/{property}/media-request', [PropertyWebController::class, 'requestMedia'])
    ->middleware('throttle:6,1')
    ->name('properties.media-request');
Route::get('/location-catalog/departments/{department}/cities', [LocationCatalogController::class, 'cities'])
    ->name('location-catalog.cities');
Route::get('/location-catalog/cities/{city}/neighborhoods', [LocationCatalogController::class, 'neighborhoods'])
    ->name('location-catalog.neighborhoods');

// Rutas protegidas (ejemplo: solo para usuarios autenticados como agentes o administradores)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/properties/create', [PropertyWebController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyWebController::class, 'store'])->name('properties.store');
    Route::post('/properties/{id}/video', [PropertyWebController::class, 'storeVideo'])->name('properties.video.store');
    Route::post('/properties/{id}/blueprint', [PropertyWebController::class, 'storeBlueprint'])->name('properties.blueprint.store');

    Route::get('/properties/{id}/edit', [PropertyWebController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{id}', [PropertyWebController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{id}', [PropertyWebController::class, 'destroy'])->name('properties.destroy');

    Route::middleware('can:manage-location-catalog')->prefix('location-catalog')->name('location-catalog.')->group(function () {
        Route::get('/', [LocationCatalogController::class, 'index'])->name('index');
        Route::post('/departments', [LocationCatalogController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}', [LocationCatalogController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [LocationCatalogController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::post('/cities', [LocationCatalogController::class, 'storeCity'])->name('cities.store');
        Route::put('/cities/{city}', [LocationCatalogController::class, 'updateCity'])->name('cities.update');
        Route::delete('/cities/{city}', [LocationCatalogController::class, 'destroyCity'])->name('cities.destroy');
        Route::post('/neighborhoods', [LocationCatalogController::class, 'storeNeighborhood'])->name('neighborhoods.store');
        Route::put('/neighborhoods/{neighborhood}', [LocationCatalogController::class, 'updateNeighborhood'])->name('neighborhoods.update');
        Route::delete('/neighborhoods/{neighborhood}', [LocationCatalogController::class, 'destroyNeighborhood'])->name('neighborhoods.destroy');
    });
});

Route::get('/properties/{id}', [PropertyWebController::class, 'show'])->name('properties.show');
