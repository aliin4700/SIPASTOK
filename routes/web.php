<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ShippingController;

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

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin', [BerandaController::class, 'berandaBackend'])->name('backend.beranda');
    Route::get('backend/beranda', fn () => redirect()->route('backend.beranda'));
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

    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.update-status');
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


    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/items/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::get('checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('checkout/shipping/provinces', [ShippingController::class, 'provinces'])->name('checkout.shipping.provinces');
    Route::get('checkout/shipping/cities', [ShippingController::class, 'cities'])->name('checkout.shipping.cities');
    Route::post('checkout/shipping/costs', [ShippingController::class, 'costs'])->name('checkout.shipping.costs');
    Route::get('checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('orders/history', [CheckoutController::class, 'history'])->name('orders.history');
});
