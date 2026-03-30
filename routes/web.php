<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrityController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])
        ->middleware('role:admin,supplier')
        ->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])
        ->middleware('role:admin,supplier')
        ->name('items.store');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');

    Route::post('/items/{item}/status', [TrackingController::class, 'store'])
        ->middleware('role:admin,kurir')
        ->name('tracking.store');

    Route::get('/integrity', [IntegrityController::class, 'index'])
        ->middleware('role:admin')
        ->name('integrity.index');

    Route::get('/audit-trails', [AuditTrailController::class, 'index'])
        ->middleware('role:admin')
        ->name('audit.index');
});
