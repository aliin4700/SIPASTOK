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
use App\Http\Controllers\Cashier\CashierDashboardController;
use App\Http\Controllers\Cashier\CashierShiftController;
use App\Http\Controllers\Cashier\CashierShiftReportController;
use App\Http\Controllers\Cashier\CashierOrderController;
use App\Http\Controllers\Cashier\CashFlowController;
use App\Http\Controllers\Cashier\CashierReceiptController;
use App\Http\Controllers\Backend\CashierShiftReportController as BackendCashierShiftReportController;
use App\Http\Controllers\Backend\UserAccessController as BackendUserAccessController;
use App\Http\Controllers\Backend\FinanceDashboardController as BackendFinanceDashboardController;
use App\Http\Controllers\Backend\SalesTargetController as BackendSalesTargetController;
use App\Http\Controllers\Backend\OperationalExpenseController as BackendOperationalExpenseController;
use App\Http\Controllers\Backend\InventoryItemController as BackendInventoryItemController;
use App\Http\Controllers\Backend\InventoryStockController as BackendInventoryStockController;
use App\Http\Controllers\Backend\StockMovementController as BackendStockMovementController;
use App\Http\Controllers\Backend\MaterialRequestController as BackendMaterialRequestController;
use App\Http\Controllers\Backend\WasteRecordController as BackendWasteRecordController;
use App\Http\Controllers\Manager\CashierShiftReportController as ManagerCashierShiftReportController;
use App\Http\Controllers\Manager\FinanceDashboardController as ManagerFinanceDashboardController;
use App\Http\Controllers\Manager\SalesTargetController as ManagerSalesTargetController;
use App\Http\Controllers\Manager\OperationalExpenseController as ManagerOperationalExpenseController;
use App\Http\Controllers\Manager\InventoryMonitoringController as ManagerInventoryMonitoringController;
use App\Http\Controllers\Manager\MaterialRequestController as ManagerMaterialRequestController;
use App\Http\Controllers\Manager\WasteRecordController as ManagerWasteRecordController;
use App\Http\Controllers\Staff\StaffDashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\DailyOperationController as StaffDailyOperationController;
use App\Http\Controllers\Staff\OpeningAlertController as StaffOpeningAlertController;
use App\Http\Controllers\Staff\MaterialRequestController as StaffMaterialRequestController;
use App\Http\Controllers\Staff\WasteRecordController as StaffWasteRecordController;

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

    Route::get('/manager/finance', [ManagerFinanceDashboardController::class, 'dashboard'])->middleware('finance.access')->name('manager.finance.dashboard');
    Route::get('/manager/sales-targets', [ManagerSalesTargetController::class, 'index'])->middleware('finance.access')->name('manager.sales-targets.index');
    Route::get('/manager/operational-expenses', [ManagerOperationalExpenseController::class, 'index'])->middleware('finance.access')->name('manager.operational-expenses.index');

    Route::get('/manager/cashier-shifts', [ManagerCashierShiftReportController::class, 'index'])->name('manager.cashier-shifts.index');
    Route::get('/manager/cashier-shifts/{shift}', [ManagerCashierShiftReportController::class, 'show'])->name('manager.cashier-shifts.show');
    Route::get('/manager/inventory', [ManagerInventoryMonitoringController::class, 'inventory'])->name('manager.inventory.index');
    Route::get('/manager/daily-operations', [ManagerInventoryMonitoringController::class, 'dailyOperations'])->name('manager.daily-operations.index');
    Route::get('/manager/opening-alerts', [ManagerInventoryMonitoringController::class, 'openingAlerts'])->name('manager.opening-alerts.index');
    Route::get('/manager/material-requests', [ManagerMaterialRequestController::class, 'index'])->name('manager.material-requests.index');
    Route::get('/manager/material-requests/{materialRequest}', [ManagerMaterialRequestController::class, 'show'])->name('manager.material-requests.show');
    Route::get('/manager/waste-records', [ManagerWasteRecordController::class, 'index'])->name('manager.waste-records.index');
    Route::get('/manager/waste-records/{wasteRecord}', [ManagerWasteRecordController::class, 'show'])->name('manager.waste-records.show');
});

Route::prefix('cashier')->name('cashier.')->middleware(['auth', 'role:cashier,admin,super_admin'])->group(function () {
    Route::get('/', [CashierDashboardController::class, 'index'])->name('index');
    Route::get('/shift', [CashierShiftController::class, 'index'])->name('shift.index');
    Route::get('/shift/report', [CashierShiftReportController::class, 'report'])->name('shift.report');
    Route::post('/shift/open', [CashierShiftController::class, 'open'])->name('shift.open');
    Route::post('/shift/close', [CashierShiftController::class, 'close'])->name('shift.close');
    Route::get('/orders', [CashierOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/cart/add', [CashierOrderController::class, 'addToCart'])->name('orders.cart.add');
    Route::patch('/orders/cart/update', [CashierOrderController::class, 'updateCart'])->name('orders.cart.update');
    Route::delete('/orders/cart/remove', [CashierOrderController::class, 'removeFromCart'])->name('orders.cart.remove');
    Route::delete('/orders/cart/clear', [CashierOrderController::class, 'clearCart'])->name('orders.cart.clear');
    Route::post('/orders/finalize', [CashierOrderController::class, 'finalize'])->name('orders.finalize');
    Route::get('/orders/history', [CashierOrderController::class, 'history'])->name('orders.history');
    Route::get('/cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');
    Route::post('/cash-flow', [CashFlowController::class, 'store'])->name('cash-flow.store');
    Route::get('/receipts', [CashierReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{order}', [CashierReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{order}/print', [CashierReceiptController::class, 'print'])->name('receipts.print');
});

Route::middleware(['auth', 'role:staff_outlet,admin,super_admin'])->group(function () {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/staff/daily-operations', [StaffDailyOperationController::class, 'index'])->name('staff.daily-operations.index');
    Route::post('/staff/daily-operations/open', [StaffDailyOperationController::class, 'open'])->name('staff.daily-operations.open');
    Route::post('/staff/daily-operations/{operation}/submit', [StaffDailyOperationController::class, 'submit'])->name('staff.daily-operations.submit');
    Route::get('/staff/opening-alerts', [StaffOpeningAlertController::class, 'index'])->name('staff.opening-alerts.index');
    Route::get('/staff/material-requests', [StaffMaterialRequestController::class, 'index'])->name('staff.material-requests.index');
    Route::get('/staff/material-requests/create', [StaffMaterialRequestController::class, 'create'])->name('staff.material-requests.create');
    Route::post('/staff/material-requests', [StaffMaterialRequestController::class, 'store'])->name('staff.material-requests.store');
    Route::get('/staff/material-requests/{materialRequest}', [StaffMaterialRequestController::class, 'show'])->name('staff.material-requests.show');
    Route::post('/staff/material-requests/{materialRequest}/submit', [StaffMaterialRequestController::class, 'submit'])->name('staff.material-requests.submit');
    Route::post('/staff/material-requests/{materialRequest}/cancel', [StaffMaterialRequestController::class, 'cancel'])->name('staff.material-requests.cancel');
    Route::get('/staff/waste', [StaffWasteRecordController::class, 'index'])->name('staff.waste.index');
    Route::get('/staff/waste/create', [StaffWasteRecordController::class, 'create'])->name('staff.waste.create');
    Route::post('/staff/waste', [StaffWasteRecordController::class, 'store'])->name('staff.waste.store');
    Route::get('/staff/waste/{wasteRecord}', [StaffWasteRecordController::class, 'show'])->name('staff.waste.show');
    Route::post('/staff/waste/{wasteRecord}/submit', [StaffWasteRecordController::class, 'submit'])->name('staff.waste.submit');
    Route::post('/staff/waste/{wasteRecord}/cancel', [StaffWasteRecordController::class, 'cancel'])->name('staff.waste.cancel');
});

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin', fn () => redirect()->route('backend.dashboard'))->name('backend.beranda');
    Route::get('backend/beranda', fn () => redirect()->route('backend.dashboard'));
    Route::get('backend/dashboard', [DashboardController::class, 'index'])->name('backend.dashboard');
    Route::post('backend/logout', [LoginController::class, 'logoutBackend'])->name('backend.logout');

    // Route untuk User
    // Route::resource('backend/user', UserController::class)->middleware('auth');
    Route::get('backend/users/{user}/access', [BackendUserAccessController::class, 'edit'])->name('backend.user.access.edit');
    Route::put('backend/users/{user}/access', [BackendUserAccessController::class, 'update'])->name('backend.user.access.update');
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

    Route::get('backend/cashier-shifts', [BackendCashierShiftReportController::class, 'index'])->name('backend.cashier-shifts.index');
    Route::get('backend/cashier-shifts/{shift}', [BackendCashierShiftReportController::class, 'show'])->name('backend.cashier-shifts.show');
    Route::patch('backend/cashier-shifts/{shift}/review', [BackendCashierShiftReportController::class, 'review'])->name('backend.cashier-shifts.review');

    Route::get('backend/finance', [BackendFinanceDashboardController::class, 'dashboard'])->middleware('finance.access')->name('backend.finance.dashboard');
    Route::resource('backend/sales-targets', BackendSalesTargetController::class)->except(['show'])->names('backend.sales-targets');
    Route::resource('backend/operational-expenses', BackendOperationalExpenseController::class)->except(['show'])->names('backend.operational-expenses');
    Route::resource('backend/inventory-items', BackendInventoryItemController::class)->except(['show'])->names('backend.inventory-items');
    Route::get('backend/inventory-stocks', [BackendInventoryStockController::class, 'index'])->name('backend.inventory-stocks.index');
    Route::get('backend/inventory-stocks/{inventoryStock}/edit', [BackendInventoryStockController::class, 'edit'])->name('backend.inventory-stocks.edit');
    Route::put('backend/inventory-stocks/{inventoryStock}', [BackendInventoryStockController::class, 'update'])->name('backend.inventory-stocks.update');
    Route::get('backend/stock-movements', [BackendStockMovementController::class, 'index'])->name('backend.stock-movements.index');
    Route::get('backend/material-requests', [BackendMaterialRequestController::class, 'index'])->name('backend.material-requests.index');
    Route::get('backend/material-requests/{materialRequest}', [BackendMaterialRequestController::class, 'show'])->name('backend.material-requests.show');
    Route::get('backend/waste-records', [BackendWasteRecordController::class, 'index'])->name('backend.waste-records.index');
    Route::get('backend/waste-records/{wasteRecord}', [BackendWasteRecordController::class, 'show'])->name('backend.waste-records.show');

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
