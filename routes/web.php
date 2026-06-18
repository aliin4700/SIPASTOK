<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KategoriController;
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
    Route::resource('backend/customer', CustomerController::class, ['as' => 'backend']);

    // Route untuk Kategori
    Route::resource('backend/kategori', KategoriController::class, ['as' => 'backend']);

    // Route untuk Produk
    Route::resource('backend/produk', ProductController::class, ['as' => 'backend']);
    Route::post('foto-produk/store', fn () => back()->with('error', 'Manajemen foto produk akan dirapikan pada refactor product berikutnya.'))->name('backend.foto_produk.store');
    Route::delete('foto-produk/{id}', fn () => back()->with('error', 'Manajemen foto produk akan dirapikan pada refactor product berikutnya.'))->name('backend.foto_produk.destroy');

    // Route untuk laporan produk
    Route::get('backend/laporan/formproduk', fn () => redirect()->route('backend.produk.index'))->name('backend.laporan.formproduk');
    Route::post('backend/laporan/cetakproduk', fn () => redirect()->route('backend.produk.index'))->name('backend.laporan.cetakproduk');

    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.update-status');
});

// Frontend
Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');
Route::get('/produk/detail/{product}', [ProductController::class, 'detail'])->name('produk.detail');
Route::get('/produk/kategori/{id}', [ProductController::class, 'category'])->name('produk.kategori');
Route::get('/produk/all', [ProductController::class, 'catalog'])->name('produk.all');

// Group route untuk customer
Route::middleware('is.customer')->group(function () {
    // Route untuk menampilkan halaman akun customer
    Route::get('/customer/akun/{id}', [CustomerController::class, 'akun'])
        ->name('customer.akun');

    // Route untuk mengupdate data akun customer
    Route::put('/customer/updateakun/{id}', [CustomerController::class, 'updateAkun'])
        ->name('customer.updateakun');


    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('cart/item/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/item/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('checkout/shipping/provinces', [ShippingController::class, 'provinces'])->name('checkout.shipping.provinces');
    Route::get('checkout/shipping/cities', [ShippingController::class, 'cities'])->name('checkout.shipping.cities');
    Route::post('checkout/shipping/costs', [ShippingController::class, 'costs'])->name('checkout.shipping.costs');
    Route::get('checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('orders/history', [CheckoutController::class, 'history'])->name('orders.history');
});
