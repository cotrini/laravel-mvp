<?php

use App\Http\Middleware\AuthenticateFirebaseToken;
use App\Modules\Dashboard\Http\Controllers\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateFirebaseToken::class)
    ->get('/dashboard', [DashboardApiController::class, 'show'])
    ->name('dashboard.show');
