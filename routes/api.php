<?php

use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
