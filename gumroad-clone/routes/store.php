<?php

use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\OrderController;
use App\Http\Controllers\Store\SettingsController;
use Illuminate\Support\Facades\Route;

// Store routes with domain/subdomain handling
Route::middleware(['store.connection'])->group(function () {
    // Public store routes
    Route::get('/', function () {
        $products = \App\Models\Store\Product::latest()->paginate(12);
        return view('store.home', compact('products'));
    })->name('store.home');

    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->name('store.products.show');

    Route::post('/checkout/{product}', [OrderController::class, 'checkout'])
        ->name('store.checkout');

    Route::get('/thank-you/{order}', [OrderController::class, 'thankYou'])
        ->name('store.thank-you');

    // Store owner routes (protected)
    Route::middleware(['auth', 'store.owner'])->prefix('dashboard')->group(function () {
        Route::get('/', function () {
            return view('store.dashboard');
        })->name('store.dashboard');

        // Products management
        Route::resource('products', ProductController::class)
            ->except(['show'])
            ->names([
                'index' => 'store.products.index',
                'create' => 'store.products.create',
                'store' => 'store.products.store',
                'edit' => 'store.products.edit',
                'update' => 'store.products.update',
                'destroy' => 'store.products.destroy',
            ]);

        // Orders management
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('store.orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('store.orders.show');
        Route::get('/orders/{order}/download/{fileIndex}', [OrderController::class, 'download'])
            ->name('store.orders.download');

        // Store settings
        Route::get('/settings', [SettingsController::class, 'edit'])
            ->name('store.settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->name('store.settings.update');
    });
});
