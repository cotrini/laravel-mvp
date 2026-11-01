<?php

use App\Modules\Credito\Http\Controllers\BankInterestRateController;
use App\Modules\Credito\Http\Controllers\CreditSimulatorController;
use Illuminate\Support\Facades\Route;

Route::get('/simulador-credito', [CreditSimulatorController::class, 'show'])
    ->name('credit-simulator.show');
Route::post('/simulador-credito', [CreditSimulatorController::class, 'calculate'])
    ->name('credit-simulator.calculate');

Route::middleware(['auth', 'verified', 'can:manage-users'])
    ->prefix('/admin/intereses-bancos')
    ->name('admin.bank-interest-rates.')
    ->group(function (): void {
        Route::get('/', [BankInterestRateController::class, 'index'])->name('index');
        Route::put('/{bank}', [BankInterestRateController::class, 'update'])->name('update');
    });
