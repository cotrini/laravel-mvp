<?php

use App\Modules\Payment\Http\Controllers\PaymentOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('payments')->name('payments.')->group(function () {
    Route::get('/publication', [PaymentOrderController::class, 'show'])->name('publication.show');
    Route::post('/publication', [PaymentOrderController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('publication.store');
    Route::get('/return', [PaymentOrderController::class, 'handleReturn'])->name('return');
});
