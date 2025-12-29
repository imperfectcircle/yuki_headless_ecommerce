<?php

use App\Http\Controllers\Storefront\Cart\AddCartItemController;
use App\Http\Controllers\Storefront\Cart\CartController;
use App\Http\Controllers\Storefront\Cart\CreateCartController;
use App\Http\Controllers\Storefront\Cart\DeleteCartItemController;
use App\Http\Controllers\Storefront\Cart\UpdateCartItemController;
use App\Http\Controllers\Storefront\Catalog\ProductController;
use App\Http\Controllers\Storefront\Checkout\CheckoutController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Middleware\EnsureCartToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Storefront API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('storefront/v1')->name('storefront.')->group(function () {
    
    // Catalog (Public - No authentication required)
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{slug}', [ProductController::class, 'show'])->name('show');
    });

    // Cart Creation (Public)
    Route::post('/carts', CreateCartController::class)->name('carts.create');

    // Cart Management (Requires cart token)
    Route::middleware(EnsureCartToken::class)->prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('/items', AddCartItemController::class)->name('items.store');
        Route::patch('/items/{itemId}', UpdateCartItemController::class)->name('items.update');
        Route::delete('/items/{itemId}', DeleteCartItemController::class)->name('items.destroy');
    });

    // Checkout (Requires cart token)
    Route::middleware(EnsureCartToken::class)->group(function () {
        Route::post('/checkout', CheckoutController::class)->name('checkout');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes receive webhooks from payment providers.
| They should NOT have CSRF protection.
*/

Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/payments/{provider}', [PaymentWebhookController::class, '__invoke'])
        ->whereIn('provider', ['stripe', 'paypal'])
        ->name('payments');
});