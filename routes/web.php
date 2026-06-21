<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RajaOngkirController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAddressController;

Route::get('/', function () {
    // return view('welcome');
    // return redirect()->route('backend.login');
    return redirect()->route('beranda');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [CustomerController::class, 'login'])->name('login');
    Route::post('/login', [CustomerController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [CustomerController::class, 'register'])->name('register');
    Route::post('/register', [CustomerController::class, 'storeRegister'])->name('register.store');
    Route::get('/auth/redirect', [CustomerController::class, 'redirect'])->name('auth.redirect');

    Route::get('backend/login', fn () => redirect()->route('login'))->name('backend.login');
});

Route::get('/auth/google/callback', [CustomerController::class, 'callback'])->name('auth.callback');
Route::post('/logout', [CustomerController::class, 'logout'])->name('logout')->middleware('auth');

if (config('app.debug')) {
    Route::get('/debug/rajaongkir/config', [RajaOngkirController::class, 'debugConfig'])
        ->name('debug.rajaongkir.config');
    Route::get('/debug/rajaongkir/province', [RajaOngkirController::class, 'debugProvince'])
        ->name('debug.rajaongkir.province');
    Route::get('/debug/rajaongkir/cities', [RajaOngkirController::class, 'debugCities'])
        ->name('debug.rajaongkir.cities');
    Route::get('/debug/rajaongkir/cost', [RajaOngkirController::class, 'debugCost'])
        ->name('debug.rajaongkir.cost');
}

Route::middleware(['auth', 'role:manager,admin,super_admin'])->group(function () {
    Route::get('/manager/dashboard', function () {
        return view('panel-placeholder', [
            'title' => 'Manager Dashboard',
            'message' => 'Panel monitoring manager sedang disiapkan.',
        ]);
    })->name('manager.dashboard');

    Route::get('/manager/finance', function () {
        return view('panel-placeholder', [
            'title' => 'Finance Monitoring',
            'message' => 'Area finance hanya untuk manager, admin, dan super admin.',
        ]);
    })->middleware('finance.access')->name('manager.finance.index');
});

Route::middleware(['auth', 'role:cashier,admin,super_admin'])->group(function () {
    Route::get('/cashier', function () {
        return view('panel-placeholder', [
            'title' => 'Cashier POS',
            'message' => 'Panel cashier/POS sedang disiapkan.',
        ]);
    })->name('cashier.index');
});

Route::middleware(['auth', 'role:staff_outlet,admin,super_admin'])->group(function () {
    Route::get('/staff/dashboard', function () {
        return view('panel-placeholder', [
            'title' => 'Staff Outlet Dashboard',
            'message' => 'Panel staff outlet sedang disiapkan.',
        ]);
    })->name('staff.dashboard');
});

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin', fn () => redirect()->route('backend.dashboard'))->name('backend.beranda');
    Route::get('backend/beranda', fn () => redirect()->route('backend.dashboard'));
    Route::get('backend/dashboard', [DashboardController::class, 'index'])->name('backend.dashboard');
    Route::post('backend/logout', [LoginController::class, 'logoutBackend'])->name('backend.logout');

    // Route untuk User
    // Route::resource('backend/user', UserController::class)->middleware('auth');
    Route::resource('backend/user', UserController::class, ['as' => 'backend']);
    // Route untuk laporan user
    Route::get('backend/laporan/formuser', [UserController::class, 'formUser'])->name('backend.laporan.formuser');
    Route::post('backend/laporan/cetakuser', [UserController::class, 'cetakUser'])->name('backend.laporan.cetakuser');

    // Route untuk customer
    Route::get('backend/customers', [CustomerController::class, 'index'])->name('backend.customer.index');
    Route::get('backend/customers/{customer}', [CustomerController::class, 'show'])->name('backend.customer.show');

    Route::resource('backend/categories', CategoryController::class)->except(['show'])->names('backend.category');
    Route::resource('backend/products', ProductController::class)->names('backend.product');
    Route::post('backend/products/{product}/photos', [ProductController::class, 'storePhoto'])->name('backend.product.photo.store');
    Route::delete('backend/product-photos/{photo}', [ProductController::class, 'destroyPhoto'])->name('backend.product.photo.destroy');

    // Route untuk laporan produk
    Route::get('backend/reports/products', fn () => redirect()->route('backend.product.index'))->name('backend.report.product.index');
    Route::post('backend/reports/products/print', fn () => redirect()->route('backend.product.index'))->name('backend.report.product.print');

    Route::get('backend/orders', [AdminOrderController::class, 'index'])->name('backend.order.index');
    Route::get('backend/orders/{order}', [AdminOrderController::class, 'show'])->name('backend.order.show');
    Route::patch('backend/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('backend.order.updateStatus');
    Route::patch('backend/orders/{order}/tracking', [AdminOrderController::class, 'updateTracking'])->name('backend.order.updateTracking');

    Route::get('backend/payments', [PaymentController::class, 'index'])->name('backend.payment.index');
    Route::get('backend/payments/{payment}', [PaymentController::class, 'adminShow'])->name('backend.payment.show');
    Route::patch('backend/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('backend.payment.verify');
    Route::patch('backend/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('backend.payment.reject');
    Route::patch('backend/payments/{payment}/confirm-cash', [PaymentController::class, 'confirmCash'])->name('backend.payment.confirmCash');

    Route::get('backend/chats', [ConversationController::class, 'index'])->name('backend.chat.index');
    Route::get('backend/chats/{conversation}', [ConversationController::class, 'adminShow'])->name('backend.chat.show');
    Route::post('backend/chats/{conversation}/messages', [MessageController::class, 'adminSend'])->name('backend.chat.send');
    Route::get('backend/chats/{conversation}/messages', [MessageController::class, 'adminFetch'])->name('backend.chat.fetch');
});

// Frontend
Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');
Route::get('/products', [ProductController::class, 'catalog'])->name('product.index');
Route::get('/products/category/{category:slug}', [ProductController::class, 'category'])->name('product.category');
Route::get('/products/{product}', [ProductController::class, 'detail'])->name('product.detail');

// Group route untuk customer
Route::middleware(['auth', 'is.customer'])->group(function () {
    // Route untuk menampilkan halaman akun customer
    Route::get('/customer/account', [CustomerController::class, 'account'])
        ->name('customer.account');

    // Route untuk mengupdate data akun customer
    Route::put('/customer/account', [CustomerController::class, 'updateAccount'])
        ->name('customer.account.update');

    Route::get('/customer/addresses', [CustomerAddressController::class, 'index'])->name('customer.address.index');
    Route::get('/customer/addresses/create', [CustomerAddressController::class, 'create'])->name('customer.address.create');
    Route::post('/customer/addresses', [CustomerAddressController::class, 'store'])->name('customer.address.store');
    Route::get('/customer/addresses/{address}/edit', [CustomerAddressController::class, 'edit'])->name('customer.address.edit');
    Route::put('/customer/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customer.address.update');
    Route::delete('/customer/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.address.destroy');
    Route::patch('/customer/addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])->name('customer.address.setDefault');

    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/items/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('rajaongkir/provinces', [RajaOngkirController::class, 'provinces'])->name('rajaongkir.provinces');
    Route::get('rajaongkir/cities', [RajaOngkirController::class, 'cities'])->name('rajaongkir.cities');
    Route::get('rajaongkir/districts', [RajaOngkirController::class, 'districts'])->name('rajaongkir.districts');
    Route::post('rajaongkir/cost', [RajaOngkirController::class, 'cost'])->name('rajaongkir.cost');
    Route::get('orders', [OrderController::class, 'history'])->name('order.history');
    Route::get('orders/{order}/chat', [ConversationController::class, 'show'])->name('chat.show');
    Route::post('orders/{order}/chat/messages', [MessageController::class, 'send'])->name('chat.send');
    Route::get('orders/{order}/chat/messages', [MessageController::class, 'fetch'])->name('chat.fetch');
    Route::get('orders/{order}', [OrderController::class, 'detail'])->name('order.detail');
    Route::get('payments/{order}', [PaymentController::class, 'show'])->name('payment.show');
    Route::get('payments/{order}/upload-proof', [PaymentController::class, 'uploadProof'])->name('payment.uploadProof');
    Route::post('payments/{order}/upload-proof', [PaymentController::class, 'storeProof'])->name('payment.storeProof');
});
