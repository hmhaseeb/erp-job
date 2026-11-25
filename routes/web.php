<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Finance\BooksManager;
use App\Livewire\Finance\ExpenseIncomeManager;
use App\Livewire\Finance\FinancialReports;
use App\Livewire\Hrm\AttendanceManager;
use App\Livewire\Hrm\DepartmentDesignationManager;
use App\Livewire\Hrm\EmployeeDirectory;
use App\Livewire\Hrm\EmployeeLifecycleManager;
use App\Livewire\Hrm\EmployeeProfile;
use App\Livewire\Hrm\LeaveManager;
use App\Livewire\Hrm\PayrollManager;
use App\Livewire\Inventory\CategoryManager;
use App\Livewire\Inventory\ProductDetail;
use App\Livewire\Inventory\ProductManager;
use App\Livewire\Inventory\StockOperations;
use App\Livewire\Inventory\SupplierManager;
use App\Livewire\JobCards\JobCardBoard;
use App\Livewire\JobCards\JobCardDetail;
use App\Livewire\JobCards\JobCardIndex;
use App\Livewire\Profile\ProfileManager;
use App\Livewire\Purchase\GoodsReceiptManager;
use App\Livewire\Purchase\PurchaseInvoiceManager;
use App\Livewire\Purchase\PurchaseOperations;
use App\Livewire\Purchase\PurchaseOrderManager;
use App\Livewire\Purchase\VendorProfile;
use App\Livewire\Sales\CustomerManager;
use App\Livewire\Sales\CustomerProfile;
use App\Livewire\Sales\SalesInvoiceManager;
use App\Livewire\Sales\SalesOperations;
use App\Livewire\Sales\SalesOrderManager;
use App\Livewire\Settings\CompanySettingsManager;
use App\Livewire\Users\ActivityLogViewer;
use App\Livewire\Users\RolePermissionManager;
use App\Livewire\Users\UserManager;
use App\Models\JobCard;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

// Authenticated ERP Workspace
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', Overview::class)->name('dashboard');
    Route::get('/profile', ProfileManager::class)->name('profile');

    // Module 1: Settings & Company Configuration
    Route::middleware('module.permission:settings,view')->prefix('settings')->group(function () {
        Route::get('/', CompanySettingsManager::class)->name('settings.index');
    });

    // Module 2: HRM (Human Resource Management)
    Route::middleware('module.permission:hrm,view')->prefix('hrm')->group(function () {
        Route::get('/employees', EmployeeDirectory::class)->name('hrm.employees');
        Route::get('/employees/{id}', EmployeeProfile::class)->name('hrm.employees.show');
        Route::get('/lifecycle', EmployeeLifecycleManager::class)->name('hrm.lifecycle');
        Route::get('/departments', DepartmentDesignationManager::class)->name('hrm.departments');
        Route::get('/attendance', AttendanceManager::class)->name('hrm.attendance');
        Route::get('/leaves', LeaveManager::class)->name('hrm.leaves');
        Route::get('/payroll', PayrollManager::class)->name('hrm.payroll');
    });

    // Module 3: Inventory Management
    Route::middleware('module.permission:inventory,view')->prefix('inventory')->group(function () {
        Route::get('/products', ProductManager::class)->name('inventory.products');
        Route::get('/products/{id}', ProductDetail::class)->whereNumber('id')->name('inventory.products.show');
        Route::get('/categories', CategoryManager::class)->name('inventory.categories');
        Route::get('/suppliers', SupplierManager::class)->name('inventory.suppliers');
        Route::get('/suppliers/{id}', VendorProfile::class)->whereNumber('id')->name('inventory.suppliers.show');
        Route::get('/stock', StockOperations::class)->defaults('mode', 'stock')->name('inventory.stock');
        Route::get('/stock-adjustments', StockOperations::class)->defaults('mode', 'adjustments')->name('inventory.stock-adjustments');
        Route::get('/stock-history', StockOperations::class)->defaults('mode', 'history')->name('inventory.stock-history');
        Route::get('/low-stock', StockOperations::class)->defaults('mode', 'low-stock')->name('inventory.low-stock');
        Route::get('/reports', StockOperations::class)->defaults('mode', 'reports')->name('inventory.reports');
    });

    // Module 4: Sales Management
    Route::middleware('module.permission:sales,view')->prefix('sales')->group(function () {
        Route::get('/customers', CustomerManager::class)->name('sales.customers');
        Route::get('/customers/{id}', CustomerProfile::class)->whereNumber('id')->name('sales.customers.show');
        Route::get('/orders', SalesOrderManager::class)->name('sales.orders');
        Route::get('/invoices', SalesInvoiceManager::class)->name('sales.invoices');
        Route::get('/invoices/{id}/print', fn (int $id) => view('sales.invoice-print', [
            'invoice' => SalesInvoice::with(['customer', 'items', 'salesOrder', 'jobCard'])->findOrFail($id),
        ]))->name('sales.invoices.print');
        Route::get('/payments', SalesOperations::class)->defaults('mode', 'payments')->name('sales.payments');
        Route::get('/balances', SalesOperations::class)->defaults('mode', 'balances')->name('sales.balances');
        Route::get('/returns', SalesOperations::class)->defaults('mode', 'returns')->name('sales.returns');
        Route::get('/reports', SalesOperations::class)->defaults('mode', 'reports')->name('sales.reports');
    });

    // Module 5: Purchase Management
    Route::middleware('module.permission:purchase,view')->prefix('purchase')->group(function () {
        Route::get('/vendors', SupplierManager::class)->name('purchase.vendors');
        Route::get('/vendors/{id}', VendorProfile::class)->whereNumber('id')->name('purchase.vendors.show');
        Route::get('/orders', PurchaseOrderManager::class)->name('purchase.orders');
        Route::get('/goods-received', GoodsReceiptManager::class)->name('purchase.grn');
        Route::get('/invoices', PurchaseInvoiceManager::class)->name('purchase.invoices');
        Route::get('/invoices/{id}/print', fn (int $id) => view('purchase.bill-print', [
            'bill' => PurchaseInvoice::with(['supplier', 'items', 'purchaseOrder', 'goodsReceipt'])->findOrFail($id),
        ]))->name('purchase.invoices.print');
        Route::get('/payments', PurchaseOperations::class)->defaults('mode', 'payments')->name('purchase.payments');
        Route::get('/balances', PurchaseOperations::class)->defaults('mode', 'balances')->name('purchase.balances');
        Route::get('/returns', PurchaseOperations::class)->defaults('mode', 'returns')->name('purchase.returns');
        Route::get('/reports', PurchaseOperations::class)->defaults('mode', 'reports')->name('purchase.reports');
    });

    // Module 6: Finance & Accounts Management
    Route::middleware('module.permission:finance,view')->prefix('finance')->group(function () {
        Route::get('/expenses', ExpenseIncomeManager::class)->defaults('mode', 'expenses')->name('finance.expenses');
        Route::get('/incomes', ExpenseIncomeManager::class)->defaults('mode', 'incomes')->name('finance.incomes');
        Route::get('/daybook', BooksManager::class)->defaults('mode', 'daybook')->name('finance.daybook');
        Route::get('/cashbook', BooksManager::class)->defaults('mode', 'cashbook')->name('finance.cashbook');
        Route::get('/bankbook', BooksManager::class)->defaults('mode', 'bankbook')->name('finance.bankbook');
        Route::get('/transactions', BooksManager::class)->defaults('mode', 'transactions')->name('finance.transactions');
        Route::get('/vouchers', BooksManager::class)->defaults('mode', 'vouchers')->name('finance.receipts-payments');
        Route::get('/reports', FinancialReports::class)->name('finance.reports');
    });

    // Module 7: Job Card / Service Management
    Route::middleware('module.permission:job_cards,view')->prefix('job-cards')->group(function () {
        Route::get('/', JobCardIndex::class)->name('job-cards.index');
        Route::get('/board', JobCardBoard::class)->defaults('mode', 'board')->name('job-cards.board');
        Route::get('/reports', JobCardBoard::class)->defaults('mode', 'reports')->name('job-cards.reports');
        Route::get('/{id}', JobCardDetail::class)->whereNumber('id')->name('job-cards.show');
        Route::get('/{id}/print', fn (int $id) => view('job-cards.print', [
            'jobCard' => JobCard::with(['customer', 'technician', 'parts', 'images', 'salesInvoice'])->findOrFail($id),
        ]))->whereNumber('id')->name('job-cards.print');
    });

    // Module 8: User Management & Access Control
    Route::middleware('module.permission:users,view')->prefix('users')->group(function () {
        Route::get('/', UserManager::class)->name('users.index');
        Route::get('/roles', RolePermissionManager::class)->name('users.roles');
        Route::get('/activity-logs', ActivityLogViewer::class)->name('users.logs');
    });
});
