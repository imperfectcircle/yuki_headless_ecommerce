<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;
use App\Admin\Controllers\ProductController;
use App\Domains\Customer\Actions\VerifyStorefrontUserEmail;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentProviderController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::resource('products', ProductController::class);
});

Route::controller(PaymentProviderController::class)->prefix('payments/providers')->group(function () {
    Route::get('/', 'index')->name('admin.payment-providers.index');
    Route::patch('{provider}/toggle', 'toggle')->name('admin.payment-providers.toggle');
    Route::post('reorder', 'reorder')->name('admin.payment-providers.reorder');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    
    // Order Management Routes
    Route::prefix('orders')->name('orders.')->group(function () {
        // Lista ordini (Inertia page)
        Route::get('/', [OrderController::class, 'index'])->name('index');
        
        // Dettaglio ordine (Inertia page)
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        
        // Actions (POST routes for Inertia forms)
        Route::post('/{id}/process', [OrderController::class, 'process'])->name('process');
        Route::post('/{id}/fulfill', [OrderController::class, 'fulfill'])->name('fulfill');
        Route::post('/{id}/ship', [OrderController::class, 'ship'])->name('ship');
        Route::post('/{id}/complete', [OrderController::class, 'complete'])->name('complete');
        Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/refund', [OrderController::class, 'refund'])->name('refund');
        
        // Update status generico
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->name('update-status');
    });
});

// Test
Route::get('/verify-account', function (Request $request) {
    $token = $request->query('token');
    
    if (!$token) {
        return view('auth.verification-error', [
            'message' => 'Token mancante'
        ]);
    }
    
    try {
        $verifyEmail = new VerifyStorefrontUserEmail();
        $user = $verifyEmail->execute($token);
        
        return view('auth.verification-success', [
            'email' => $user->email
        ]);
    } catch (\DomainException $e) {
        return view('auth.verification-error', [
            'message' => $e->getMessage()
        ]);
    }
})->name('verify-email');

require __DIR__.'/auth.php';
