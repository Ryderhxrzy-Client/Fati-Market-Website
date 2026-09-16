<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\TurnoverController;

// Public pages. These URLs are used on the Google OAuth consent screen and
// must be reachable without signing in.
Route::view('/', 'home')->name('home');
Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');
Route::view('/terms-of-service', 'terms-of-service')->name('terms-of-service');

// The counter turnover page a phone lands on after scanning the QR the
// console shows. It carries its own short-lived key rather than a session,
// because the phone that scans it has never signed in here.
Route::get('/turnover/{ref}', [TurnoverController::class, 'show'])->name('turnover.show');
Route::post('/turnover/{ref}', [TurnoverController::class, 'complete'])->name('turnover.complete');

// Keep the admin console separate from the public website.
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');


// Admin dashboard and pages (protected)
Route::middleware('admin.auth')->group(function () {
    Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('admin.dashboard');

    // COUNTER SCAN DISABLED - the scanning page is off for now. The two
    // routes below are the turnover itself, not the scanner, and stay on.
    // Route::get('/counter', [AdminAuthController::class, 'counter'])->name('admin.counter');

    // Receiving an item: here on this computer, or handed off to a phone
    // with a camera. Both end up in TurnoverService.
    Route::post('/counter/handoff', [TurnoverController::class, 'handoff'])->name('admin.turnover.handoff');
    Route::post('/counter/turnover/{item_id}', [TurnoverController::class, 'consoleComplete'])->name('admin.turnover.complete');

    // Inventory Management
    Route::get('/inventory/private-offers', [AdminAuthController::class, 'privateOffers'])->name('admin.private-offers');
    Route::get('/inventory/acquired-items', [AdminAuthController::class, 'acquiredItems'])->name('admin.acquired-items');
    Route::get('/inventory/public-listings', [AdminAuthController::class, 'publicListings'])->name('admin.public-listings');
    Route::get('/inventory/reserved-items', [AdminAuthController::class, 'reservedItems'])->name('admin.reserved-items');
    Route::get('/inventory/sold-items', [AdminAuthController::class, 'soldItems'])->name('admin.sold-items');

    // Transactions
    // The same list twice, as on the mobile app: "Manage orders" carries the
    // decisions, "Transaction history" is the read-only record.
    Route::get('/transactions/manage', [AdminAuthController::class, 'manageOrders'])->name('admin.transactions.manage');
    Route::get('/transactions/history', [AdminAuthController::class, 'transactionHistory'])->name('admin.transactions.history');
    Route::get('/transactions/points-given', [AdminAuthController::class, 'pointsGiven'])->name('admin.transactions.points-given');
    Route::get('/transactions/points-received', [AdminAuthController::class, 'pointsReceived'])->name('admin.transactions.points-received');
    Route::get('/transactions/cash', [AdminAuthController::class, 'cashTransactions'])->name('admin.transactions.cash');
    Route::get('/transactions/trade', [AdminAuthController::class, 'tradeTransactions'])->name('admin.transactions.trade');
    Route::get('/transactions/profit', [AdminAuthController::class, 'profitSummary'])->name('admin.transactions.profit');
    Route::get('/transactions', [AdminAuthController::class, 'transactionHistory'])->name('admin.transactions');

    // Reports & Analytics
    Route::get('/reports/items-acquired', [AdminAuthController::class, 'itemsAcquiredReport'])->name('admin.reports.items-acquired');
    Route::get('/reports/items-sold', [AdminAuthController::class, 'itemsSoldReport'])->name('admin.reports.items-sold');
    Route::get('/reports/total-profit', [AdminAuthController::class, 'totalProfitReport'])->name('admin.reports.total-profit');
    Route::get('/reports/categories', [AdminAuthController::class, 'categoriesReport'])->name('admin.reports.categories');
    Route::get('/reports/users', [AdminAuthController::class, 'usersReport'])->name('admin.reports.users');
    // NOTE: There is no AdminAuthController::salesReport() in this project.
    // Use the separate pages instead:
    // - /reports/items-acquired
    // - /reports/items-sold
    Route::get('/reports/profit', [AdminAuthController::class, 'profitReport'])->name('admin.reports.profit');
    // Home reports route (optional): keep it, but do not mark it as the same as acquired/sold.
    Route::get('/reports', [AdminAuthController::class, 'itemsAcquiredReport'])->name('admin.reports');

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
    Route::post('/profile', [AdminAuthController::class, 'updateProfileName'])->name('admin.profile.update');
    Route::post('/profile/picture', [AdminAuthController::class, 'updateProfilePicture'])->name('admin.profile.picture');
    Route::post('/profile/password', [AdminAuthController::class, 'updatePassword'])->name('admin.profile.password');
    Route::get('/settings', [\App\Http\Controllers\Admin\GcashSettingsController::class, 'show'])->name('admin.settings');
    Route::post('/settings/gcash', [\App\Http\Controllers\Admin\GcashSettingsController::class, 'update'])->name('admin.settings.gcash.update');
    // Signing out is a POST from the sidebar form, but a proxy that redirects
    // http to https turns that into a GET, which used to be a 405 page. Both
    // verbs end the session.
    Route::match(['get', 'post'], '/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
