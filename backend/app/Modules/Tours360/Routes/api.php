<?php

use App\Http\Middleware\AuthenticateFirebaseToken;
use App\Modules\Tours360\Http\Controllers\Tour360ApiController;
use App\Modules\Tours360\Http\Controllers\Tour360WebController;
use Illuminate\Support\Facades\Route;

Route::get('/tours', [Tour360ApiController::class, 'index'])
    ->name('tours360.index');
Route::get('/tours/{slug}', [Tour360ApiController::class, 'show'])
    ->name('tours360.show');

Route::middleware(AuthenticateFirebaseToken::class)->group(function (): void {
    Route::get('/my/tours', [Tour360ApiController::class, 'mine'])
        ->name('tours360.mine');
    Route::post('/tours/scene-images', [Tour360WebController::class, 'storeSceneImage'])
        ->name('tours360.scene-images.store');
    Route::post('/tours', [Tour360WebController::class, 'store'])
        ->name('tours360.store');
    Route::put('/tours/{id}', [Tour360WebController::class, 'update'])
        ->whereNumber('id')
        ->name('tours360.update');
    Route::delete('/tours/{id}', [Tour360WebController::class, 'destroy'])
        ->whereNumber('id')
        ->name('tours360.destroy');
});
