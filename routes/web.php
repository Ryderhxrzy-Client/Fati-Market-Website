<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;

// Admin login as index
Route::get('/', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/', [AdminAuthController::class, 'login'])->name('admin.login.post');

// Admin dashboard and pages (protected)
Route::middleware('admin.auth')->group(function () {
    Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('admin.dashboard');

    // Inventory Management
    Route::get('/inventory/private-offers', [AdminAuthController::class, 'privateOffers'])->name('admin.private-offers');
    Route::get('/inventory/acquired-items', [AdminAuthController::class, 'acquiredItems'])->name('admin.acquired-items');
    Route::get('/inventory/public-listings', [AdminAuthController::class, 'publicListings'])->name('admin.public-listings');
    Route::get('/inventory/reserved-items', [AdminAuthController::class, 'reservedItems'])->name('admin.reserved-items');
    Route::get('/inventory/sold-items', [AdminAuthController::class, 'soldItems'])->name('admin.sold-items');

    // Transactions
    Route::get('/transactions/history', [AdminAuthController::class, 'transactionHistory'])->name('admin.transactions.history');
    Route::get('/transactions/points-given', [AdminAuthController::class, 'pointsGiven'])->name('admin.transactions.points-given');
    Route::get('/transactions/points-received', [AdminAuthController::class, 'pointsReceived'])->name('admin.transactions.points-received');
    Route::get('/transactions/cash', [AdminAuthController::class, 'cashTransactions'])->name('admin.transactions.cash');
    Route::get('/transactions/trade', [AdminAuthController::class, 'tradeTransactions'])->name('admin.transactions.trade');
    Route::get('/transactions/profit', [AdminAuthController::class, 'profitSummary'])->name('admin.transactions.profit');
    Route::get('/transactions', [AdminAuthController::class, 'transactionHistory'])->name('admin.transactions');

    // Reports & Analytics
    Route::get('/reports/sales', [AdminAuthController::class, 'salesReport'])->name('admin.reports.sales');
    Route::get('/reports/profit', [AdminAuthController::class, 'profitReport'])->name('admin.reports.profit');
    Route::get('/reports/categories', [AdminAuthController::class, 'categoriesReport'])->name('admin.reports.categories');
    Route::get('/reports/users', [AdminAuthController::class, 'usersReport'])->name('admin.reports.users');
    Route::get('/reports', [AdminAuthController::class, 'salesReport'])->name('admin.reports');

    // Categories
    Route::get('/categories', [AdminAuthController::class, 'categories'])->name('admin.categories');

    // Activity Logs
    Route::get('/activity', [AdminAuthController::class, 'activity'])->name('admin.activity');

    // Chat & Communication
    Route::get('/conversations', [AdminAuthController::class, 'conversations'])->name('admin.conversations');

    // User Management
    Route::get('/students', [AdminAuthController::class, 'students'])->name('admin.students');

    // Profile & Settings
    Route::get('/profile', [AdminAuthController::class, 'profile'])->name('admin.profile');
    Route::get('/settings', [AdminAuthController::class, 'settings'])->name('admin.settings');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
