<?php

use App\Http\Middleware\AuthenticateFirebaseToken;
use App\Modules\Property\Http\Controllers\PropertyApiController;
use App\Modules\Property\Http\Controllers\PropertyWebController;
use Illuminate\Support\Facades\Route;

Route::get('/properties', [PropertyApiController::class, 'index'])
    ->name('properties.index');
Route::get('/properties/{property}', [PropertyApiController::class, 'show'])
    ->whereNumber('property')
    ->name('properties.show');

Route::middleware(AuthenticateFirebaseToken::class)->group(function (): void {
    Route::get('/my/properties', [PropertyApiController::class, 'mine'])
        ->name('properties.mine');
    Route::post('/properties/{id}/video', [PropertyWebController::class, 'storeVideo'])
        ->whereNumber('id')
        ->name('properties.video.store');
});
