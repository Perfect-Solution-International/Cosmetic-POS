<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosTerminalController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockTakeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard if logged in, else login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated POS & Management Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/hq', [DashboardController::class, 'hqMasterView'])->name('hq.index');

    // POS Cashier Terminal & Shifts
    Route::get('/pos/terminal', [PosTerminalController::class, 'index'])->name('pos.terminal');
    Route::get('/pos/customer-display', [PosTerminalController::class, 'customerDisplay'])->name('pos.customer-display');
    Route::match(['get', 'post'], '/pos/lanka-qr', [PosTerminalController::class, 'generateLankaQr'])->name('pos.lanka-qr');
    Route::match(['get', 'post'], '/pos/lankaqr', [PosTerminalController::class, 'generateLankaQr'])->name('pos.lankaqr');
    Route::post('/pos/shift/start', [PosTerminalController::class, 'startShift'])->name('pos.shift.start');
    Route::post('/pos/shift/close/{id}', [PosTerminalController::class, 'closeShift'])->name('pos.shift.close');
    Route::post('/pos/checkout', [PosTerminalController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/receipt/{id}', [PosTerminalController::class, 'receipt'])->name('pos.receipt');
    Route::get('/pos/receipt/{id}/whatsapp', [PosTerminalController::class, 'whatsappReceipt'])->name('pos.receipt.whatsapp');
    Route::get('/pos/receipt/{id}/escpos', [PosTerminalController::class, 'escposReceipt'])->name('pos.receipt.escpos');
    Route::post('/pos/hardware/open-drawer', [PosTerminalController::class, 'openCashDrawer'])->name('pos.open-drawer');
    Route::post('/pos/hold', [PosTerminalController::class, 'holdSale'])->name('pos.hold');
    Route::get('/pos/held-sales', [PosTerminalController::class, 'getHeldSales'])->name('pos.held-sales');
    Route::post('/pos/held-sales/{id}/resume', [PosTerminalController::class, 'resumeHeldSale'])->name('pos.held-sales.resume');
    Route::delete('/pos/held-sales/{id}', [PosTerminalController::class, 'cancelHeldSale'])->name('pos.held-sales.cancel');
    Route::get('/pos/variations/{id}/branch-stocks', [PosTerminalController::class, 'checkBranchStocks'])->name('pos.branch-stocks');
    Route::post('/pos/verify-pin', [PosTerminalController::class, 'verifyManagerPin'])->name('pos.verify-pin');

    // Quotations & Proforma Invoices (B2B / Estimates)
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::get('/quotations/{id}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::get('/quotations/{id}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::post('/quotations/{id}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status');
    Route::post('/quotations/{id}/convert', [QuotationController::class, 'convertToSale'])->name('quotations.convert');

    // Subscriptions & Monthly Free Trials (Package Management & Acquisition Channel)
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/packages', [SubscriptionController::class, 'packages'])->name('subscriptions.packages');
    Route::post('/subscriptions/packages', [SubscriptionController::class, 'storePackage'])->name('subscriptions.packages.store');
    Route::post('/subscriptions/packages/{id}/toggle', [SubscriptionController::class, 'togglePackage'])->name('subscriptions.packages.toggle');
    Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('/subscriptions/{id}/convert-trial', [SubscriptionController::class, 'convertTrial'])->name('subscriptions.convert');
    Route::post('/subscriptions/{id}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Products & Dynamic Attribute Builder
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{id}/quick-price', [ProductController::class, 'quickPriceUpdate'])->name('products.quick-price');
    Route::patch('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/attributes', [ProductController::class, 'attributes'])->name('products.attributes');
    Route::post('/products/attributes', [ProductController::class, 'storeAttribute'])->name('products.attributes.store');
    Route::post('/products/attributes/{id}/values', [ProductController::class, 'storeAttributeValue'])->name('products.attributes.values.store');

    // Procurement & Suppliers
    Route::get('/procurement/suppliers', [ProcurementController::class, 'suppliers'])->name('procurement.suppliers');
    Route::post('/procurement/suppliers', [ProcurementController::class, 'storeSupplier'])->name('procurement.suppliers.store');
    Route::get('/procurement/purchase-orders', [ProcurementController::class, 'purchaseOrders'])->name('procurement.po');
    Route::post('/procurement/purchase-orders', [ProcurementController::class, 'storePo'])->name('procurement.po.store');
    Route::get('/procurement/purchase-orders/{id}', [ProcurementController::class, 'showPo'])->name('procurement.po.show');
    Route::get('/procurement/purchase-orders/{id}/print', [ProcurementController::class, 'printPo'])->name('procurement.po.print');
    Route::post('/procurement/purchase-orders/{id}/status', [ProcurementController::class, 'updatePoStatus'])->name('procurement.po.status');
    Route::get('/procurement/grn', [ProcurementController::class, 'grnList'])->name('procurement.grn');
    Route::post('/procurement/grn', [ProcurementController::class, 'storeGrn'])->name('procurement.grn.store');
    Route::post('/procurement/grn/{id}/payment', [ProcurementController::class, 'recordPayment'])->name('procurement.grn.payment');
    Route::put('/procurement/grn/{id}/payment', [ProcurementController::class, 'recordPayment'])->name('procurement.grn.payment.update');
    Route::get('/procurement/returns', [ProcurementController::class, 'purchaseReturns'])->name('procurement.returns');
    Route::post('/procurement/returns', [ProcurementController::class, 'storePurchaseReturn'])->name('procurement.returns.store');
    Route::get('/procurement/returns/{id}/print', [ProcurementController::class, 'printPurchaseReturn'])->name('procurement.returns.print');

    // Inventory & Inter-Branch Transfers
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/serials', [InventoryController::class, 'serials'])->name('inventory.serials');
    Route::post('/inventory/serials', [InventoryController::class, 'storeSerials'])->name('inventory.serials.store');
    Route::get('/warranty/lookup', [InventoryController::class, 'warrantyLookup'])->name('warranty.lookup');
    Route::get('/inventory/barcodes', [InventoryController::class, 'barcodes'])->name('inventory.barcodes');
    Route::get('/inventory/batch-expiry', [InventoryController::class, 'batchExpiry'])->name('inventory.batch_expiry');
    Route::get('/inventory/reorder-expiry', [InventoryController::class, 'weeklyReorderExpiryReport'])->name('inventory.reorder_expiry');
    Route::get('/inventory/reorder-expiry/print', [InventoryController::class, 'printWeeklyReorderExpiryReport'])->name('inventory.reorder_expiry.print');
    Route::get('/inventory/reorder-expiry/export-excel', [InventoryController::class, 'exportWeeklyReorderExpiryExcel'])->name('inventory.reorder_expiry.export-excel');
    Route::get('/inventory/transfers', [InventoryController::class, 'transfers'])->name('inventory.transfers');
    Route::post('/inventory/transfers', [InventoryController::class, 'storeTransfer'])->name('inventory.transfers.store');
    Route::post('/inventory/transfers/{id}/receive', [InventoryController::class, 'receiveTransfer'])->name('inventory.transfers.receive');
    Route::post('/inventory/adjustments', [InventoryController::class, 'storeAdjustment'])->name('inventory.adjustments.store');

    // Physical Stock Takes & Cycle Count Audits
    Route::get('/inventory/stock-takes', [StockTakeController::class, 'index'])->name('inventory.stock_takes.index');
    Route::get('/inventory/stock-takes/create', [StockTakeController::class, 'create'])->name('inventory.stock_takes.create');
    Route::post('/inventory/stock-takes', [StockTakeController::class, 'store'])->name('inventory.stock_takes.store');
    Route::get('/inventory/stock-takes/{id}', [StockTakeController::class, 'show'])->name('inventory.stock_takes.show');
    Route::post('/inventory/stock-takes/{id}/scan', [StockTakeController::class, 'scanItem'])->name('inventory.stock_takes.scan');
    Route::post('/inventory/stock-takes/{id}/complete', [StockTakeController::class, 'complete'])->name('inventory.stock_takes.complete');
    Route::get('/inventory/stock-takes/{id}/print', [StockTakeController::class, 'printReport'])->name('inventory.stock_takes.print');

    // Customers (CRM) & Credit Ledger Settlement
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{id}/settle', [CustomerController::class, 'settlePayment'])->name('customers.settle');
    Route::get('/customers/{id}/payments', [CustomerController::class, 'customerPayments'])->name('customers.payments');

    // Sales Ledger & Returns
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{id}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/sales/{id}/return', [SaleController::class, 'returnSale'])->name('sales.return');

    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    // Reports, Shifts & Profit & Loss Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pnl', [ReportController::class, 'profitAndLoss'])->name('reports.pnl');
    Route::get('/reports/pnl/print', [ReportController::class, 'printPnl'])->name('reports.pnl.print');
    Route::get('/reports/pnl/export-excel', [ReportController::class, 'exportPnlExcel'])->name('reports.pnl.export-excel');
    Route::get('/reports/pnl/export-csv', [ReportController::class, 'exportPnlCsv'])->name('reports.pnl.export-csv');
    Route::get('/reports/shifts', [ReportController::class, 'shifts'])->name('reports.shifts');
    Route::get('/reports/shifts/export-excel', [ReportController::class, 'exportShiftsExcel'])->name('reports.shifts.export-excel');
    Route::get('/reports/shifts/{id}/print', [ReportController::class, 'printShiftZReport'])->name('reports.shifts.print');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    // Branches & Counters
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::post('/branches/{id}/registers', [BranchController::class, 'storeRegister'])->name('branches.registers.store');

    // Staff & Role Permissions (Admin Only)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/admin/backup/download', [UserController::class, 'downloadBackup'])->name('admin.backup.download');
});
