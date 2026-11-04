<?php

use App\Modules\Credito\Http\Controllers\CreditApiController;
use Illuminate\Support\Facades\Route;

Route::get('/credit/banks', [CreditApiController::class, 'banks'])
    ->name('credit.banks.index');
Route::post('/credit/simulations', [CreditApiController::class, 'simulate'])
    ->name('credit.simulations.store');
