<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Menu;
use App\Http\Controllers\Pos;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Menu online (publik, tanpa login)
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/menu');

Route::prefix('menu')->name('menu.')->group(function () {
    Route::get('/', [Menu\MenuController::class, 'index'])->name('index');
    Route::post('/checkout', [Menu\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout');
    Route::get('/order/{order}', [Menu\OrderStatusController::class, 'show'])->middleware('throttle:60,1')->name('order.show');
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/akun', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/akun/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::put('/akun/pin', [AccountController::class, 'updatePin'])->middleware('role:owner')->name('account.pin');
});

/*
|--------------------------------------------------------------------------
| POS — Kasir & Owner
|--------------------------------------------------------------------------
*/
Route::prefix('pos')->name('pos.')->middleware(['auth', 'role:owner,kasir'])->group(function () {
    Route::get('/', Pos\DashboardController::class)->name('dashboard');

    Route::get('/shift', [Pos\ShiftController::class, 'show'])->name('shift.show');
    Route::post('/shift', [Pos\ShiftController::class, 'store'])->name('shift.open');
    Route::put('/shift', [Pos\ShiftController::class, 'update'])->name('shift.close');

    Route::get('/kasir', [Pos\PosOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [Pos\PosOrderController::class, 'store'])->name('orders.store');

    Route::get('/orders', [Pos\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Pos\OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/struk', [Pos\OrderController::class, 'receipt'])->name('orders.receipt');

    Route::post('/orders/{order}/pay', [Pos\OrderActionController::class, 'pay'])->name('orders.pay');
    Route::post('/orders/{order}/advance', [Pos\OrderActionController::class, 'advance'])->name('orders.advance');
    Route::post('/orders/{order}/complete', [Pos\OrderActionController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/cancel', [Pos\OrderActionController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/void', [Pos\OrderActionController::class, 'void'])->name('orders.void');
    Route::put('/orders/{order}/items', [Pos\OrderActionController::class, 'updateItems'])->name('orders.items');

    Route::get('/online', [Pos\OnlineOrderController::class, 'index'])->name('online.index');

    Route::get('/produk', [Pos\ProductAvailabilityController::class, 'index'])->name('products.index');
    Route::patch('/produk/{product}/ketersediaan', [Pos\ProductAvailabilityController::class, 'update'])->name('products.availability');
});

/*
|--------------------------------------------------------------------------
| Back-office — Owner
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/transaksi', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transaksi/export/{format}', [Admin\TransactionController::class, 'export'])->whereIn('format', ['xlsx', 'pdf'])->name('transactions.export');
    Route::get('/shift', [Admin\ShiftReportController::class, 'index'])->name('shifts.index');

    Route::resource('produk', Admin\ProductController::class)->except('show')->parameters(['produk' => 'product'])->names('products');
    Route::resource('kategori', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['kategori' => 'category'])->names('categories');
    Route::resource('pembayaran', Admin\PaymentMethodController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['pembayaran' => 'paymentMethod'])->names('payment-methods');

    Route::get('/stok', [Admin\StockController::class, 'index'])->name('stock.index');
    Route::get('/stok/riwayat', [Admin\StockController::class, 'movements'])->name('stock.movements');
    Route::post('/stok/{product}', [Admin\StockController::class, 'store'])->name('stock.store');

    Route::get('/user', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/user', [Admin\UserController::class, 'store'])->name('users.store');
    Route::put('/user/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::put('/user/{user}/password', [Admin\UserController::class, 'resetPassword'])->name('users.password');

    Route::get('/toko', [Admin\StoreSettingController::class, 'edit'])->name('store.edit');
    Route::put('/toko', [Admin\StoreSettingController::class, 'update'])->name('store.update');
    Route::put('/toko/status', [Admin\StoreSettingController::class, 'toggle'])->name('store.toggle');

    Route::get('/log', [Admin\ActivityLogController::class, 'index'])->name('logs.index');
});
