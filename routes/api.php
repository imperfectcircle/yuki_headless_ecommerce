<?php

use App\Http\Controllers\Storefront\Catalog\ProductController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/payments', PaymentWebhookController::class);

Route::prefix('storefront/v1')->controller(ProductController::class)->group(function (){
    Route::get('/products', 'index');
    Route::get('/products/{slug}', 'show');
});
