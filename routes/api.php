<?php

use App\Http\Controllers\Storefront\Cart\AddCartItemController;
use App\Http\Controllers\Storefront\Cart\CartController;
use App\Http\Controllers\Storefront\Cart\DeleteCartItemController;
use App\Http\Controllers\Storefront\Cart\UpdateCartItemController;
use App\Http\Controllers\Storefront\Catalog\ProductController;
use App\Http\Controllers\Storefront\Checkout\CheckoutController;
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

Route::prefix('storefront/v1')->group(function () {
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', AddCartItemController::class);
    Route::patch('/cart/items/{itemId}', UpdateCartItemController::class);
    Route::delete('/cart/items/{itemId}', DeleteCartItemController::class);
});

Route::prefix('storefront/v1')->group(function () {
    Route::post('/checkout', CheckoutController::class);
});
