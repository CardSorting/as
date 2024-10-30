<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [ProductController::class, 'index'])->name('home');

// Authentication routes
require __DIR__.'/auth.php';

// Product routes
Route::middleware(['auth'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// Public product routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Order routes
Route::middleware(['auth'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/purchases', [OrderController::class, 'purchases'])->name('orders.purchases');
    Route::get('/orders/sales', [OrderController::class, 'sales'])->name('orders.sales');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/products/{product}/purchase', [OrderController::class, 'store'])->name('orders.store');
    
    // New routes for order download and status update
    Route::get('/orders/{order}/download', [OrderController::class, 'download'])->name('orders.download');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    
    // New route for order export
    Route::get('/orders/export/csv', [OrderController::class, 'export'])->name('orders.export');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
