<?php

use App\Modules\Payment\Http\Controllers\WompiWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/wompi', WompiWebhookController::class)->name('webhooks.wompi');
