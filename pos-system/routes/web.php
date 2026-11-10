<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Deploy hook for GitHub Actions (token-protected, no session/CSRF, 3 calls per minute).
Route::post('/_deploy', DeployController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:3,1')
    ->name('deploy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth', 'branch'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/branch', [AuthController::class, 'switchBranch'])->name('branch.switch');

    // ---- POS (all users) ----
    Route::get('/', [PosController::class, 'index'])->name('pos');
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/products', [PosController::class, 'products'])->name('products');
        Route::get('/lookup', [PosController::class, 'lookup'])->name('lookup');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/last', [PosController::class, 'lastReceipt'])->name('receipt.last');
        Route::get('/receipt/{sale}', [PosController::class, 'receipt'])->name('receipt');
        Route::get('/held', [PosController::class, 'heldList'])->name('held');
        Route::post('/held', [PosController::class, 'hold'])->name('hold');
        Route::get('/held/{held}', [PosController::class, 'heldRestore'])->name('held.restore');
        Route::delete('/held/{held}', [PosController::class, 'heldDelete'])->name('held.delete');
    });

    // ---- Customers (all users) ----
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
    Route::post('/customers/{customer}/payment', [CustomerController::class, 'payment'])->name('customers.payment');

    // ---- Back office (admin) ----
    Route::middleware('admin')->group(function () {
        Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('/inventory/{product}/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

        Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
        Route::post('/suppliers/{supplier}/payment', [SupplierController::class, 'payment'])->name('suppliers.payment');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/bills', [ReportController::class, 'bills'])->name('bills');
            Route::get('/bills/{sale}', [ReportController::class, 'showBill'])->name('bill');
            Route::post('/bills/{sale}/void', [ReportController::class, 'voidBill'])->name('void');
            Route::get('/products', [ReportController::class, 'products'])->name('products');
        });

        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/branches', [SettingController::class, 'storeBranch'])->name('settings.branches.store');
        Route::put('/settings/branches/{branch}', [SettingController::class, 'updateBranch'])->name('settings.branches.update');
        Route::post('/settings/users', [SettingController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{user}', [SettingController::class, 'updateUser'])->name('settings.users.update');
    });
});
