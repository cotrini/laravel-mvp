<?php

use App\Modules\Core\Http\Controllers\CoreApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [CoreApiController::class, 'health'])
    ->name('core.health');
