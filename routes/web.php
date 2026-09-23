<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\ItemLookupController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\StuffRequestController;
use App\Http\Controllers\StockRequestController;

// Public Routes (no auth)
Route::get('/', [StuffRequestController::class, 'publicIndex'])->name('public.stuff-request');
Route::post('/request-stuff', [StuffRequestController::class, 'store'])->name('public.stuff-request.store');
Route::get('/stock-request', [StuffRequestController::class, 'publicIndex'])->name('public.stock-request');
Route::post('/request-stock', [StuffRequestController::class, 'store'])->name('public.stock-request.store');
Route::get('/public/api/teknik/monthly-data', [StuffRequestController::class, 'publicMonthlyData'])->name('public.teknik.monthlyData');
Route::get('/public/api/teknik/ship-unloader-data', [StuffRequestController::class, 'publicShipUnloaderData'])->name('public.teknik.shipUnloaderData');
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');


// Auth Routes
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

// Protected Routes
Route::middleware(['auth', \App\Http\Middleware\RestrictTeknikAccess::class])->group(function () {

    // Dashboard - admin & manager only
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('userAkses:admin,manajer');

    // Daily approval from dashboard - admin only
    Route::post('/dashboard/approve-date', [DashboardController::class, 'approveByDate'])->name('dashboard.approveByDate')->middleware('userAkses:admin');
    Route::post('/dashboard/reject-date', [DashboardController::class, 'rejectByDate'])->name('dashboard.rejectByDate')->middleware('userAkses:admin');

    // Dashboard AJAX API endpoints
    Route::get('/dashboard/api/search-items', [DashboardController::class, 'searchItems'])->name('dashboard.searchItems')->middleware('userAkses:admin,manajer');
    Route::get('/dashboard/api/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chartData')->middleware('userAkses:admin,manajer');
    Route::get('/dashboard/api/category-by-year', [DashboardController::class, 'categoryByYear'])->name('dashboard.categoryByYear')->middleware('userAkses:admin,manajer');
    Route::get('/dashboard/api/monthly-data', [DashboardController::class, 'monthlyData'])
        ->name('dashboard.monthlyData');
    // Autocomplete API endpoints (for search suggestions across all pages)
    Route::get('/api/autocomplete/items', [ItemController::class, 'autocomplete'])->name('api.autocomplete.items');
    Route::get('/api/autocomplete/stuff-requests', [StuffRequestController::class, 'autocomplete'])->name('api.autocomplete.stuff-requests')->middleware('userAkses:admin,staf');
    Route::get('/api/autocomplete/users', [UserController::class, 'autocomplete'])->name('api.autocomplete.users')->middleware('userAkses:admin');

    // Items - admin only
    // Lookups routes must come BEFORE resource to avoid matching items/{item}
    Route::get('/items/lookups', [ItemLookupController::class, 'index'])->name('items.lookups.index')->middleware('userAkses:admin');
    Route::post('/items/lookup/replace', [ItemLookupController::class, 'replace'])->name('items.lookup.replace')->middleware('userAkses:admin');
    Route::resource('items', ItemController::class)->middleware('userAkses:admin');
    Route::get('/items/{item}/edit-data', [ItemController::class, 'edit'])->name('items.editData')->middleware('userAkses:admin');


    // Transactions - admin & staff
    Route::resource('transactions', TransactionController::class)->only(['index', 'create'])->middleware('userAkses:admin,staf');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit')->middleware('userAkses:admin');
    Route::get('/transactions/{transaction}/edit-data', [TransactionController::class, 'edit'])->name('transactions.editData')->middleware('userAkses:admin');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update')->middleware('userAkses:admin');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy')->middleware('userAkses:admin');
    Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve')->middleware('userAkses:admin');
    Route::post('/transactions/{transaction}/reject', [TransactionController::class, 'reject'])->name('transactions.reject')->middleware('userAkses:admin');

    // AJAX: Get item details
    Route::get('/api/items/{id}', [TransactionController::class, 'getItemDetails'])->name('api.items.show');

    // Users - admin can CRUD
    Route::resource('users', UserController::class)->middleware('userAkses:admin');
    Route::get('/users/{user}/edit-data', [UserController::class, 'edit'])->name('users.editData')->middleware('userAkses:admin');

    // User approval - manager only
    Route::post('users/{id}/approve-account', [UserController::class, 'approveUser'])->name('users.approveAccount')->middleware('userAkses:manajer');
    Route::post('users/{id}/reject-account', [UserController::class, 'rejectUser'])->name('users.rejectAccount')->middleware('userAkses:manajer');

    // Reset user password to default - superadmin only
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword')->middleware('userAkses:superadmin');

    // Pending users list for manager
    Route::get('/pending-users', [UserController::class, 'index'])->name('pendingUsers.index')->middleware('userAkses:manajer');

    // Stock recap - admin, manager & staff
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index')->middleware('userAkses:admin,manajer,staf');

    // Stock requests - Umum: staff creates/admin approves, Teknik: admin creates/manager approves
    Route::get('/Stok-request', [StockRequestController::class, 'index'])->name('stock-requests.index')->middleware('userAkses:admin,manajer,staf');
    Route::post('/Stok-request', [StockRequestController::class, 'store'])->name('stock-requests.store')->middleware('userAkses:admin,staf');
    Route::get('/Stok-request/export', [StockRequestController::class, 'export'])->name('stock-requests.export')->middleware('userAkses:admin,manajer,staf');
    Route::post('/Stok-request/{stockRequest}/approve', [StockRequestController::class, 'approve'])->name('stock-requests.approve')->middleware('userAkses:admin,manajer');
    Route::post('/Stok-request/{stockRequest}/reject', [StockRequestController::class, 'reject'])->name('stock-requests.reject')->middleware('userAkses:admin,manajer');
    Route::delete('/Stok-request/{stockRequest}', [StockRequestController::class, 'destroy'])->name('stock-requests.destroy')->middleware('userAkses:superadmin');

    // Stuff requests - admin & staff
    Route::get('/stuff-requests', [StuffRequestController::class, 'adminIndex'])->name('stuff-requests.index')->middleware('userAkses:admin,staf');
    Route::post('/stuff-requests/{stuffRequest}/approve', [StuffRequestController::class, 'approve'])->name('stuff-requests.approve')->middleware('userAkses:admin');
    Route::post('/stuff-requests/{stuffRequest}/reject', [StuffRequestController::class, 'reject'])->name('stuff-requests.reject')->middleware('userAkses:admin');
    Route::post('/stuff-requests/{stuffRequest}/complete', [StuffRequestController::class, 'complete'])->name('stuff-requests.complete')->middleware('userAkses:admin,staf');
    Route::post('/stuff-requests/{stuffRequest}/cancel', [StuffRequestController::class, 'cancel'])->name('stuff-requests.cancel')->middleware('userAkses:admin,staf');
    Route::delete('/stuff-requests/{stuffRequest}', [StuffRequestController::class, 'destroy'])->name('stuff-requests.destroy')->middleware('userAkses:superadmin');

    // Reports - admin & manager
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('userAkses:admin,manajer');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export')->middleware('userAkses:admin,manajer');

    // Import data from Excel - admin only
    Route::get('/import', [ImportController::class, 'index'])->name('import.index')->middleware('userAkses:admin');
    Route::post('/import', [ImportController::class, 'import'])->name('import.process')->middleware('userAkses:admin');
    Route::get('/import/template/{type}', [ImportController::class, 'downloadTemplate'])->name('import.template')->middleware('userAkses:admin');

    // Google Sheets sync - admin only
    Route::post('/sync/sheets', [GoogleSheetController::class, 'fullSync'])->name('sync.sheets')->middleware('userAkses:admin');
});
