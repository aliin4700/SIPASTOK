<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EsbSalesImportController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Backend\DailyOperationController as BackendDailyOperationController;
use App\Http\Controllers\Backend\GoodsReceiptController as BackendGoodsReceiptController;
use App\Http\Controllers\Backend\InventoryItemController as BackendInventoryItemController;
use App\Http\Controllers\Backend\InventoryLookupApiController as BackendInventoryLookupApiController;
use App\Http\Controllers\Backend\InventoryStockController as BackendInventoryStockController;
use App\Http\Controllers\Backend\MaterialRequestController as BackendMaterialRequestController;
use App\Http\Controllers\Backend\ProductionRecordController as BackendProductionRecordController;
use App\Http\Controllers\Backend\PurchaseOrderController as BackendPurchaseOrderController;
use App\Http\Controllers\Backend\RecipeController as BackendRecipeController;
use App\Http\Controllers\Backend\StockMovementController as BackendStockMovementController;
use App\Http\Controllers\Backend\StockOpnameController as BackendStockOpnameController;
use App\Http\Controllers\Backend\SupplierController as BackendSupplierController;
use App\Http\Controllers\Backend\UserAccessController as BackendUserAccessController;
use App\Http\Controllers\Backend\WasteRecordController as BackendWasteRecordController;
use App\Http\Controllers\Manager\GoodsReceiptController as ManagerGoodsReceiptController;
use App\Http\Controllers\Manager\InventoryMonitoringController as ManagerInventoryMonitoringController;
use App\Http\Controllers\Manager\MaterialRequestController as ManagerMaterialRequestController;
use App\Http\Controllers\Manager\ProductionRecordController as ManagerProductionRecordController;
use App\Http\Controllers\Manager\PurchaseOrderController as ManagerPurchaseOrderController;
use App\Http\Controllers\Manager\RecipeController as ManagerRecipeController;
use App\Http\Controllers\Manager\StockOpnameController as ManagerStockOpnameController;
use App\Http\Controllers\Manager\SupplierController as ManagerSupplierController;
use App\Http\Controllers\Manager\WasteRecordController as ManagerWasteRecordController;
use App\Http\Controllers\Staff\DailyOperationController as StaffDailyOperationController;
use App\Http\Controllers\Staff\MaterialRequestController as StaffMaterialRequestController;
use App\Http\Controllers\Staff\OpeningAlertController as StaffOpeningAlertController;
use App\Http\Controllers\Staff\ProductionRecordController as StaffProductionRecordController;
use App\Http\Controllers\Staff\StaffDashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\StockOpnameController as StaffStockOpnameController;
use App\Http\Controllers\Staff\WasteRecordController as StaffWasteRecordController;

// Entry point SIPASTOK diarahkan ke login atau dashboard sesuai role yang aktif.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect(match ((string) auth()->user()->role) {
            'super_admin', 'admin' => route('backend.dashboard'),
            'manager' => route('manager.dashboard'),
            'staff_outlet' => route('staff.dashboard'),
            default => route('login'),
        });
    }

    return redirect()->route('login');
});

// Satu form login dipakai untuk seluruh role internal SIPASTOK.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'login'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.store');
    Route::get('backend/login', fn () => redirect()->route('login'))->name('backend.login');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Panel Manager: monitoring IRA, audit stok opname, dan persetujuan operasional outlet.
Route::middleware(['auth', 'role:manager,admin,super_admin'])->group(function () {
    Route::get('/manager/dashboard', [ManagerInventoryMonitoringController::class, 'dashboard'])->name('manager.dashboard');
    Route::get('/manager/inventory', [ManagerInventoryMonitoringController::class, 'inventory'])->name('manager.inventory.index');
    Route::get('/manager/daily-operations', [ManagerInventoryMonitoringController::class, 'dailyOperations'])->name('manager.daily-operations.index');
    Route::get('/manager/daily-operations/{dailyOperation}', [ManagerInventoryMonitoringController::class, 'dailyOperationShow'])->name('manager.daily-operations.show');
    Route::post('/manager/daily-operations/{dailyOperation}/approve', [ManagerInventoryMonitoringController::class, 'dailyOperationApprove'])->name('manager.daily-operations.approve');
    Route::get('/manager/opening-alerts', [ManagerInventoryMonitoringController::class, 'openingAlerts'])->name('manager.opening-alerts.index');
    Route::get('/manager/material-requests', [ManagerMaterialRequestController::class, 'index'])->name('manager.material-requests.index');
    Route::post('/manager/material-requests/bulk-approve', [ManagerMaterialRequestController::class, 'bulkApprove'])->name('manager.material-requests.bulk-approve');
    Route::get('/manager/material-requests/{materialRequest}', [ManagerMaterialRequestController::class, 'show'])->name('manager.material-requests.show');
    Route::post('/manager/material-requests/{materialRequest}/issue-stock', [ManagerMaterialRequestController::class, 'issueStock'])->name('manager.material-requests.issue-stock');
    Route::post('/manager/material-requests/{materialRequest}/receipt', [ManagerMaterialRequestController::class, 'updateReceipt'])->name('manager.material-requests.receipt.update');
    Route::delete('/manager/material-requests/{materialRequest}/receipt', [ManagerMaterialRequestController::class, 'deleteReceipt'])->name('manager.material-requests.receipt.destroy');
    Route::get('/manager/material-requests/{materialRequest}/receipt/download', [ManagerMaterialRequestController::class, 'downloadReceipt'])->name('manager.material-requests.receipt.download');
    Route::get('/manager/waste-records', [ManagerWasteRecordController::class, 'index'])->name('manager.waste-records.index');
    Route::get('/manager/waste-records/{wasteRecord}', [ManagerWasteRecordController::class, 'show'])->name('manager.waste-records.show');
    Route::post('/manager/waste-records/{wasteRecord}/apply-stock', [ManagerWasteRecordController::class, 'applyStock'])->name('manager.waste-records.apply-stock');
    Route::get('/manager/production-records', [ManagerProductionRecordController::class, 'index'])->name('manager.production-records.index');
    Route::post('/manager/production-records/bulk-approve', [ManagerProductionRecordController::class, 'bulkApprove'])->name('manager.production-records.bulk-approve');
    Route::get('/manager/production-records/{productionRecord}', [ManagerProductionRecordController::class, 'show'])->name('manager.production-records.show');
    Route::post('/manager/production-records/{productionRecord}/apply-stock', [ManagerProductionRecordController::class, 'applyStock'])->name('manager.production-records.apply-stock');
    Route::get('/manager/stock-opnames', [ManagerStockOpnameController::class, 'index'])->name('manager.stock-opnames.index');
    Route::get('/manager/stock-opnames/{stockOpname}', [ManagerStockOpnameController::class, 'show'])->name('manager.stock-opnames.show');
    Route::post('/manager/stock-opnames/{stockOpname}/review', [ManagerStockOpnameController::class, 'review'])->name('manager.stock-opnames.review');
    Route::get('/manager/suppliers', [ManagerSupplierController::class, 'index'])->name('manager.suppliers.index');
    Route::get('/manager/suppliers/{supplier}', [ManagerSupplierController::class, 'show'])->name('manager.suppliers.show');
    Route::get('/manager/purchase-orders', [ManagerPurchaseOrderController::class, 'index'])->name('manager.purchase-orders.index');
    Route::get('/manager/purchase-orders/{purchaseOrder}', [ManagerPurchaseOrderController::class, 'show'])->name('manager.purchase-orders.show');
    Route::get('/manager/goods-receipts', [ManagerGoodsReceiptController::class, 'index'])->name('manager.goods-receipts.index');
    Route::get('/manager/goods-receipts/{goodsReceipt}', [ManagerGoodsReceiptController::class, 'show'])->name('manager.goods-receipts.show');
    Route::post('/manager/goods-receipts/{goodsReceipt}/apply-stock', [ManagerGoodsReceiptController::class, 'applyStock'])->name('manager.goods-receipts.apply-stock');
    Route::get('/manager/recipes', [ManagerRecipeController::class, 'index'])->name('manager.recipes.index');
    Route::get('/manager/recipes/{recipe}', [ManagerRecipeController::class, 'show'])->name('manager.recipes.show');
    Route::get('/manager/esb-imports', [EsbSalesImportController::class, 'index'])->name('manager.esb-imports.index');
    Route::post('/manager/esb-imports/preview', [EsbSalesImportController::class, 'preview'])->name('manager.esb-imports.preview');
    Route::post('/manager/esb-imports/execute', [EsbSalesImportController::class, 'execute'])->name('manager.esb-imports.execute');
    Route::post('/manager/esb-imports/quick-create-item', [EsbSalesImportController::class, 'quickCreateItem'])->name('manager.esb-imports.quick-create-item');
});

// Panel Staff Outlet: operasional harian divisi & pencatatan stok opname.
Route::middleware(['auth', 'role:staff_outlet,admin,super_admin'])->group(function () {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/staff/daily-operations', [StaffDailyOperationController::class, 'index'])->name('staff.daily-operations.index');
    Route::get('/staff/daily-operations/closing-summary', [StaffDailyOperationController::class, 'closingSummary'])->name('staff.daily-operations.closing-summary');
    Route::post('/staff/daily-operations/open', [StaffDailyOperationController::class, 'open'])->name('staff.daily-operations.open');
    Route::post('/staff/daily-operations/submit', [StaffDailyOperationController::class, 'submitClosing'])->name('staff.daily-operations.submit-closing');
    Route::post('/staff/daily-operations/{operation}/submit', [StaffDailyOperationController::class, 'submit'])->name('staff.daily-operations.submit');
    Route::get('/staff/opening-alerts', [StaffOpeningAlertController::class, 'index'])->name('staff.opening-alerts.index');
    Route::get('/staff/material-requests', [StaffMaterialRequestController::class, 'index'])->name('staff.material-requests.index');
    Route::get('/staff/material-requests/create', [StaffMaterialRequestController::class, 'create'])->name('staff.material-requests.create');
    Route::post('/staff/material-requests', [StaffMaterialRequestController::class, 'store'])->name('staff.material-requests.store');
    Route::get('/staff/material-requests/{materialRequest}', [StaffMaterialRequestController::class, 'show'])->name('staff.material-requests.show');
    Route::get('/staff/material-requests/{materialRequest}/edit', [StaffMaterialRequestController::class, 'edit'])->name('staff.material-requests.edit');
    Route::put('/staff/material-requests/{materialRequest}', [StaffMaterialRequestController::class, 'update'])->name('staff.material-requests.update');
    Route::post('/staff/material-requests/{materialRequest}/submit', [StaffMaterialRequestController::class, 'submit'])->name('staff.material-requests.submit');
    Route::post('/staff/material-requests/{materialRequest}/cancel', [StaffMaterialRequestController::class, 'cancel'])->name('staff.material-requests.cancel');
    Route::post('/staff/material-requests/{materialRequest}/receipt', [StaffMaterialRequestController::class, 'updateReceipt'])->name('staff.material-requests.receipt.update');
    Route::delete('/staff/material-requests/{materialRequest}/receipt', [StaffMaterialRequestController::class, 'deleteReceipt'])->name('staff.material-requests.receipt.destroy');
    Route::get('/staff/material-requests/{materialRequest}/receipt/download', [StaffMaterialRequestController::class, 'downloadReceipt'])->name('staff.material-requests.receipt.download');
    Route::get('/staff/waste', [StaffWasteRecordController::class, 'index'])->name('staff.waste.index');
    Route::get('/staff/waste/create', [StaffWasteRecordController::class, 'create'])->name('staff.waste.create');
    Route::post('/staff/waste', [StaffWasteRecordController::class, 'store'])->name('staff.waste.store');
    Route::get('/staff/waste/{wasteRecord}', [StaffWasteRecordController::class, 'show'])->name('staff.waste.show');
    Route::post('/staff/waste/{wasteRecord}/submit', [StaffWasteRecordController::class, 'submit'])->name('staff.waste.submit');
    Route::post('/staff/waste/{wasteRecord}/cancel', [StaffWasteRecordController::class, 'cancel'])->name('staff.waste.cancel');
    Route::get('/staff/production', [StaffProductionRecordController::class, 'index'])->name('staff.production.index');
    Route::get('/staff/production/create', [StaffProductionRecordController::class, 'create'])->name('staff.production.create');
    Route::post('/staff/production', [StaffProductionRecordController::class, 'store'])->name('staff.production.store');
    Route::get('/staff/production/{productionRecord}', [StaffProductionRecordController::class, 'show'])->name('staff.production.show');
    Route::post('/staff/production/{productionRecord}/submit', [StaffProductionRecordController::class, 'submit'])->name('staff.production.submit');
    Route::post('/staff/production/{productionRecord}/cancel', [StaffProductionRecordController::class, 'cancel'])->name('staff.production.cancel');
    Route::get('/staff/stock-opnames', [StaffStockOpnameController::class, 'index'])->name('staff.stock-opnames.index');
    Route::get('/staff/stock-opnames/create', [StaffStockOpnameController::class, 'create'])->name('staff.stock-opnames.create');
    Route::post('/staff/stock-opnames', [StaffStockOpnameController::class, 'store'])->name('staff.stock-opnames.store');
    Route::get('/staff/stock-opnames/{stockOpname}', [StaffStockOpnameController::class, 'show'])->name('staff.stock-opnames.show');
    Route::post('/staff/stock-opnames/{stockOpname}/submit', [StaffStockOpnameController::class, 'submit'])->name('staff.stock-opnames.submit');
    Route::post('/staff/stock-opnames/{stockOpname}/cancel', [StaffStockOpnameController::class, 'cancel'])->name('staff.stock-opnames.cancel');
});

// Backend Admin SIPASTOK: master data bahan baku, pengadaan, verifikasi stok opname, & manajemen user.
Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin', fn () => redirect()->route('backend.dashboard'))->name('backend.beranda');
    Route::get('backend/beranda', fn () => redirect()->route('backend.dashboard'));
    Route::get('backend/dashboard', [DashboardController::class, 'index'])->name('backend.dashboard');
    Route::post('backend/logout', [LoginController::class, 'logoutBackend'])->name('backend.logout');

    // Manajemen User & Hak Akses Outlet/Divisi
    Route::get('backend/users/{user}/access', [BackendUserAccessController::class, 'edit'])->name('backend.user.access.edit');
    Route::put('backend/users/{user}/access', [BackendUserAccessController::class, 'update'])->name('backend.user.access.update');
    Route::resource('backend/user', UserController::class, ['as' => 'backend']);
    Route::get('backend/laporan/formuser', [UserController::class, 'formUser'])->name('backend.laporan.formuser');
    Route::post('backend/laporan/cetakuser', [UserController::class, 'cetakUser'])->name('backend.laporan.cetakuser');

    // Inventori, Mutasi Stok, Pengadaan, & Stock Opname
    Route::resource('backend/inventory-items', BackendInventoryItemController::class)->except(['show'])->names('backend.inventory-items');
    Route::get('backend/inventory-stocks', [BackendInventoryStockController::class, 'index'])->name('backend.inventory-stocks.index');
    Route::get('backend/inventory-stocks/{inventoryStock}/edit', [BackendInventoryStockController::class, 'edit'])->name('backend.inventory-stocks.edit');
    Route::put('backend/inventory-stocks/{inventoryStock}', [BackendInventoryStockController::class, 'update'])->name('backend.inventory-stocks.update');
    Route::get('backend/stock-movements', [BackendStockMovementController::class, 'index'])->name('backend.stock-movements.index');
    Route::get('backend/material-requests', [BackendMaterialRequestController::class, 'index'])->name('backend.material-requests.index');
    Route::post('backend/material-requests/bulk-approve', [BackendMaterialRequestController::class, 'bulkApprove'])->name('backend.material-requests.bulk-approve');
    Route::get('backend/material-requests/{materialRequest}', [BackendMaterialRequestController::class, 'show'])->name('backend.material-requests.show');
    Route::delete('backend/material-requests/{materialRequest}', [BackendMaterialRequestController::class, 'destroy'])->name('backend.material-requests.destroy');
    Route::post('backend/material-requests/{materialRequest}/issue-stock', [BackendMaterialRequestController::class, 'issueStock'])->name('backend.material-requests.issue-stock');
    Route::post('backend/material-requests/{materialRequest}/receipt', [BackendMaterialRequestController::class, 'updateReceipt'])->name('backend.material-requests.receipt.update');
    Route::delete('backend/material-requests/{materialRequest}/receipt', [BackendMaterialRequestController::class, 'deleteReceipt'])->name('backend.material-requests.receipt.destroy');
    Route::get('backend/material-requests/{materialRequest}/receipt/download', [BackendMaterialRequestController::class, 'downloadReceipt'])->name('backend.material-requests.receipt.download');
    Route::get('backend/waste-records', [BackendWasteRecordController::class, 'index'])->name('backend.waste-records.index');
    Route::get('backend/waste-records/{wasteRecord}', [BackendWasteRecordController::class, 'show'])->name('backend.waste-records.show');
    Route::post('backend/waste-records/{wasteRecord}/apply-stock', [BackendWasteRecordController::class, 'applyStock'])->name('backend.waste-records.apply-stock');
    Route::get('backend/production-records', [BackendProductionRecordController::class, 'index'])->name('backend.production-records.index');
    Route::post('backend/production-records/bulk-approve', [BackendProductionRecordController::class, 'bulkApprove'])->name('backend.production-records.bulk-approve');
    Route::get('backend/production-records/{productionRecord}', [BackendProductionRecordController::class, 'show'])->name('backend.production-records.show');
    Route::post('backend/production-records/{productionRecord}/apply-stock', [BackendProductionRecordController::class, 'applyStock'])->name('backend.production-records.apply-stock');
    Route::get('backend/stock-opnames', [BackendStockOpnameController::class, 'index'])->name('backend.stock-opnames.index');
    Route::get('backend/stock-opnames/{stockOpname}', [BackendStockOpnameController::class, 'show'])->name('backend.stock-opnames.show');
    Route::post('backend/stock-opnames/{stockOpname}/review', [BackendStockOpnameController::class, 'review'])->name('backend.stock-opnames.review');
    Route::get('backend/daily-operations', [BackendDailyOperationController::class, 'index'])->name('backend.daily-operations.index');
    Route::get('backend/daily-operations/{dailyOperation}', [BackendDailyOperationController::class, 'show'])->name('backend.daily-operations.show');
    Route::post('backend/daily-operations/{dailyOperation}/approve', [BackendDailyOperationController::class, 'approve'])->name('backend.daily-operations.approve');
    Route::get('backend/suppliers', [BackendSupplierController::class, 'index'])->name('backend.suppliers.index');
    Route::get('backend/suppliers/create', [BackendSupplierController::class, 'create'])->name('backend.suppliers.create');
    Route::post('backend/suppliers', [BackendSupplierController::class, 'store'])->name('backend.suppliers.store');
    Route::get('backend/suppliers/{supplier}', [BackendSupplierController::class, 'show'])->name('backend.suppliers.show');
    Route::get('backend/suppliers/{supplier}/edit', [BackendSupplierController::class, 'edit'])->name('backend.suppliers.edit');
    Route::put('backend/suppliers/{supplier}', [BackendSupplierController::class, 'update'])->name('backend.suppliers.update');
    // API Lookup Ringan (AJAX Remote Search Item & On-Demand PO Items)
    Route::get('backend/api/inventory-items/search', [BackendInventoryLookupApiController::class, 'searchItems'])->name('backend.api.inventory-items.search');
    Route::get('backend/api/purchase-orders/{purchaseOrder}/items', [BackendInventoryLookupApiController::class, 'purchaseOrderItems'])->name('backend.api.purchase-orders.items');

    Route::get('backend/purchase-orders', [BackendPurchaseOrderController::class, 'index'])->name('backend.purchase-orders.index');
    Route::get('backend/purchase-orders/create', [BackendPurchaseOrderController::class, 'create'])->name('backend.purchase-orders.create');
    Route::post('backend/purchase-orders', [BackendPurchaseOrderController::class, 'store'])->name('backend.purchase-orders.store');
    Route::get('backend/purchase-orders/{purchaseOrder}', [BackendPurchaseOrderController::class, 'show'])->name('backend.purchase-orders.show');
    Route::post('backend/purchase-orders/{purchaseOrder}/submit', [BackendPurchaseOrderController::class, 'submit'])->name('backend.purchase-orders.submit');
    Route::post('backend/purchase-orders/{purchaseOrder}/cancel', [BackendPurchaseOrderController::class, 'cancel'])->name('backend.purchase-orders.cancel');
    Route::get('backend/goods-receipts', [BackendGoodsReceiptController::class, 'index'])->name('backend.goods-receipts.index');
    Route::get('backend/goods-receipts/create', [BackendGoodsReceiptController::class, 'create'])->name('backend.goods-receipts.create');
    Route::post('backend/goods-receipts', [BackendGoodsReceiptController::class, 'store'])->name('backend.goods-receipts.store');
    Route::get('backend/goods-receipts/{goodsReceipt}', [BackendGoodsReceiptController::class, 'show'])->name('backend.goods-receipts.show');
    Route::post('backend/goods-receipts/{goodsReceipt}/submit', [BackendGoodsReceiptController::class, 'submit'])->name('backend.goods-receipts.submit');
    Route::post('backend/goods-receipts/{goodsReceipt}/cancel', [BackendGoodsReceiptController::class, 'cancel'])->name('backend.goods-receipts.cancel');
    Route::post('backend/goods-receipts/{goodsReceipt}/apply-stock', [BackendGoodsReceiptController::class, 'applyStock'])->name('backend.goods-receipts.apply-stock');
    Route::get('backend/recipes', [BackendRecipeController::class, 'index'])->name('backend.recipes.index');
    Route::get('backend/recipes/create', [BackendRecipeController::class, 'create'])->name('backend.recipes.create');
    Route::post('backend/recipes', [BackendRecipeController::class, 'store'])->name('backend.recipes.store');
    Route::get('backend/recipes/{recipe}', [BackendRecipeController::class, 'show'])->name('backend.recipes.show');
    Route::get('backend/recipes/{recipe}/edit', [BackendRecipeController::class, 'edit'])->name('backend.recipes.edit');
    Route::put('backend/recipes/{recipe}', [BackendRecipeController::class, 'update'])->name('backend.recipes.update');
    Route::get('backend/esb-imports', [EsbSalesImportController::class, 'index'])->name('backend.esb-imports.index');
    Route::post('backend/esb-imports/preview', [EsbSalesImportController::class, 'preview'])->name('backend.esb-imports.preview');
    Route::post('backend/esb-imports/execute', [EsbSalesImportController::class, 'execute'])->name('backend.esb-imports.execute');
    Route::post('backend/esb-imports/quick-create-item', [EsbSalesImportController::class, 'quickCreateItem'])->name('backend.esb-imports.quick-create-item');
});
