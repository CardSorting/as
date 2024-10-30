<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Onboarding Routes
    Route::get('/onboarding/start', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::get('/onboarding/trial', [OnboardingController::class, 'trial'])->name('onboarding.trial');
    Route::get('/onboarding/checkout', [OnboardingController::class, 'checkout'])->name('onboarding.checkout');
    Route::post('/onboarding/process', [OnboardingController::class, 'process'])->name('onboarding.process');
    Route::get('/onboarding/setup', [OnboardingController::class, 'setup'])->name('onboarding.setup');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
