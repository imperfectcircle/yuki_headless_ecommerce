<?php

use App\Http\Controllers\Storefront\Auth\AuthController;
use App\Http\Controllers\Storefront\Cart\AddCartItemController;
use App\Http\Controllers\Storefront\Cart\CartController;
use App\Http\Controllers\Storefront\Cart\CreateCartController;
use App\Http\Controllers\Storefront\Cart\DeleteCartItemController;
use App\Http\Controllers\Storefront\Cart\UpdateCartItemController;
use App\Http\Controllers\Storefront\Catalog\ProductController;
use App\Http\Controllers\Storefront\Checkout\CheckoutController;
use App\Http\Controllers\Storefront\Order\OrderController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Middleware\EnsureCartToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
|--------------------------------------------------------------------------
| Storefront API Routes (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('storefront/v1')->name('storefront.')->group(function () {
   
    /**
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->name('auth.')->group(function () {
        // Public routes
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email');
        
        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('resend-verification');
        });
    });

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

/**
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

/*
|--------------------------------------------------------------------------
| Storefront Order Routes
|--------------------------------------------------------------------------
*/

Route::prefix('storefront/v1')
    ->name('storefront.')
    ->group(function () {
        
        Route::prefix('orders')->name('orders.')->group(function () {
            // List orders (authenticated or guest with email)
            Route::get('/', [OrderController::class, 'index'])->name('index');
            
            // Get order by ID (authenticated only)
            Route::get('/{id}', [OrderController::class, 'show'])->name('show')->middleware(['auth:sanctum']);
            
            // Get order by number (guest with email)
            Route::get('/lookup/{orderNumber}', [OrderController::class, 'showByNumber'])->name('show-by-number');
            
            // Track order
            Route::get('/{id}/tracking', [OrderController::class, 'tracking'])->name('tracking');
        });
    });
