<?php

use App\Modules\Media\Http\Controllers\MediaApiController;
use Illuminate\Support\Facades\Route;

Route::get('/properties/{property}/media', [MediaApiController::class, 'index'])
    ->whereNumber('property')
    ->name('properties.media.index');
