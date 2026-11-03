<?php

use App\Modules\Help\Http\Controllers\HelpCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/centro-de-ayuda', [HelpCenterController::class, 'index'])->name('help.index');

Route::middleware(['auth', 'verified', 'can:manage-users'])->group(function () {
    Route::post('/centro-de-ayuda', [HelpCenterController::class, 'store'])->name('help.store');
    Route::delete('/centro-de-ayuda/{article}', [HelpCenterController::class, 'destroy'])->name('help.destroy');
});
