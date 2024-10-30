<?php

use App\Http\Controllers\StoreSiloController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    // Silo Monitoring Routes
    Route::get('/silos', [StoreSiloController::class, 'index'])->name('admin.silos');
    Route::get('/silos/{silo}/transactions', [StoreSiloController::class, 'transactions'])
        ->name('admin.silos.transactions');
    Route::get('/silos/{silo}/export', [StoreSiloController::class, 'exportSiloData'])
        ->name('admin.silos.export');
});
