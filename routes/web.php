<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;

// Admin login as index
Route::get('/', [AdminAuthController::class, 'showLoginForm']);
Route::post('/', [AdminAuthController::class, 'login']);

// Admin dashboard (protected)
Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])
    ->middleware('admin.auth');
Route::post('/logout', [AdminAuthController::class, 'logout'])
    ->middleware('admin.auth');
