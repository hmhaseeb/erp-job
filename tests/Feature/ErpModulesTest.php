<?php

namespace Tests\Feature;

use App\Livewire\Finance\BooksManager;
use App\Livewire\Finance\ExpenseIncomeManager;
use App\Livewire\Finance\FinancialReports;
use App\Livewire\Hrm\EmployeeDirectory;
use App\Livewire\Hrm\EmployeeLifecycleManager;
use App\Livewire\Hrm\PayrollManager;
use App\Livewire\Inventory\ProductDetail;
use App\Livewire\Inventory\ProductManager;
use App\Livewire\Inventory\StockOperations;
use App\Livewire\JobCards\JobCardDetail;
use App\Livewire\JobCards\JobCardIndex;
use App\Livewire\Purchase\PurchaseInvoiceManager;
use App\Livewire\Purchase\PurchaseOrderManager;
use App\Livewire\Purchase\VendorProfile;
use App\Livewire\Sales\CustomerManager;
use App\Livewire\Sales\CustomerProfile;
use App\Livewire\Sales\SalesInvoiceManager;
use App\Livewire\Sales\SalesOperations;
use App\Livewire\Sales\SalesOrderManager;
use App\Livewire\Settings\CompanySettingsManager;
use App\Models\AccountTransaction;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\JobCard;
use App\Models\PayrollItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ErpSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ErpModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@dreamserp.com')->firstOrFail();
    }

    private function staff(): User
    {
        return User::where('email', 'staff@dreamserp.com')->firstOrFail();
    }

    public function test_guests_redirect_and_admin_can_access_all_erp_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $admin = $this->admin();
        $inv = SalesInvoice::firstOrFail();
        $bill = PurchaseInvoice::firstOrFail();
        $emp = Employee::firstOrFail();
        $jc = JobCard::firstOrFail();
        $prod = Product::firstOrFail();
        $cust = Customer::firstOrFail();
        $sup = Supplier::firstOrFail();

        $routes = [
            route('dashboard'),
            route('profile'),
            route('settings.index'),
            route('hrm.employees'),
            route('hrm.employees.show', $emp->id),
            route('hrm.lifecycle'),
            route('hrm.departments'),
            route('hrm.attendance'),
            route('hrm.leaves'),
            route('hrm.payroll'),
            route('inventory.products'),
            route('inventory.products.show', $prod->id),
            route('inventory.categories'),
            route('inventory.suppliers'),
            route('inventory.suppliers.show', $sup->id),
            route('inventory.stock'),
            route('inventory.stock-adjustments'),
            route('inventory.stock-history'),
            route('inventory.low-stock'),
            route('inventory.reports'),
            route('sales.customers'),
            route('sales.customers.show', $cust->id),
            route('sales.orders'),
            route('sales.invoices'),
            route('sales.invoices.print', $inv->id),
            route('sales.payments'),
            route('sales.balances'),
            route('sales.returns'),
            route('sales.reports'),
            route('purchase.vendors'),
            route('purchase.vendors.show', $sup->id),
            route('purchase.orders'),
            route('purchase.grn'),
            route('purchase.invoices'),
            route('purchase.invoices.print', $bill->id),
            route('purchase.payments'),
            route('purchase.balances'),
            route('purchase.returns'),
            route('purchase.reports'),
            route('finance.expenses'),
            route('finance.incomes'),
            route('finance.daybook'),
            route('finance.cashbook'),
            route('finance.bankbook'),
            route('finance.transactions'),
            route('finance.receipts-payments'),
            route('finance.reports'),
            route('job-cards.index'),
            route('job-cards.show', $jc->id),
            route('job-cards.print', $jc->id),
            route('job-cards.board'),
            route('job-cards.reports'),
            route('users.index'),
            route('users.roles'),
            route('users.logs'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertStatus(200);
        }
    }

    public function test_rbac_module_permissions_enforce_access_control(): void
    {
        $staff = $this->staff();

        // Staff is allowed on operational modules
        $this->actingAs($staff)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($staff)->get(route('sales.invoices'))->assertStatus(200);
        $this->actingAs($staff)->get(route('inventory.products'))->assertStatus(200);
        $this->actingAs($staff)->get(route('job-cards.index'))->assertStatus(200);

        // Staff is blocked on Settings, Finance, HRM, Purchase, and User Access Control
        $this->actingAs($staff)->get(route('settings.index'))->assertStatus(403);
        $this->actingAs($staff)->get(route('users.index'))->assertStatus(403);
        $this->actingAs($staff)->get(route('finance.reports'))->assertStatus(403);
        $this->actingAs($staff)->get(route('hrm.payroll'))->assertStatus(403);
        $this->actingAs($staff)->get(route('purchase.orders'))->assertStatus(403);
    }

    public function test_auth_registration_and_activity_log(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'New ERP Operator',
            'email' => 'operator@dreamserp.com',
            'phone' => '+1 555 0102',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'operator@dreamserp.com']);
        $this->assertTrue(ActivityLog::where('action', 'register')->exists());
    }

    public function test_settings_module_updates_company_currency_and_tax(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CompanySettingsManager::class)
            ->set('company_name', 'Dreams ERP Global Ltd')
            ->set('currency_code', 'EUR')
            ->set('currency_symbol', '€')
            ->set('default_tax_rate', 10.0)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertSame('Dreams ERP Global Ltd', erp_setting('company_name'));
        $this->assertSame('€', erp_setting('currency_symbol'));
    }

    public function test_hrm_employee_registration_lifecycle_and_payroll_posting(): void
    {
        $this->actingAs($this->admin());
        $dept = Department::firstOrFail();
        $desig = Designation::firstOrFail();

        Livewire::test(EmployeeDirectory::class)
            ->call('openCreate')
            ->set('first_name', 'Zara')
            ->set('last_name', 'Malik')
            ->set('email', 'zara.malik@dreamserp.com')
            ->set('department_id', $dept->id)
            ->set('designation_id', $desig->id)
            ->set('basic_salary', 4000)
            ->set('allowances', 500)
            ->call('save')
            ->assertHasNoErrors();

        $emp = Employee::where('email', 'zara.malik@dreamserp.com')->firstOrFail();
        $this->assertSame('onboarding', $emp->lifecycle_status);

        Livewire::test(EmployeeLifecycleManager::class)
            ->set('selectedEmployeeId', $emp->id)
            ->set('event_type', 'confirmation')
            ->set('reason', 'Probation completed successfully')
            ->call('submitTransition')
            ->assertHasNoErrors();

        Livewire::test(EmployeeLifecycleManager::class)
            ->set('selectedEmployeeId', $emp->id)
            ->set('event_type', 'promotion')
            ->set('department_id', $dept->id)
            ->set('designation_id', $desig->id)
            ->set('basic_salary', 5200)
            ->set('allowances', 800)
            ->set('reason', 'Annual excellence promotion')
            ->call('submitTransition')
            ->assertHasNoErrors();

        $emp->refresh();
        $this->assertEquals(5200.00, (float) $emp->basic_salary);
        $this->assertSame('confirmed', $emp->lifecycle_status);

        $beforeTxns = AccountTransaction::count();
        Livewire::test(PayrollManager::class)
            ->set('month', (int) now()->addMonth()->month)
            ->set('year', (int) now()->addMonth()->year)
            ->call('generatePayrollRun')
            ->assertHasNoErrors();

        $pendingItem = PayrollItem::where('payment_status', 'pending')->latest('id')->firstOrFail();
        Livewire::test(PayrollManager::class)
            ->call('paySalaryItem', $pendingItem->id)
            ->assertHasNoErrors();

        $this->assertSame('paid', $pendingItem->fresh()->payment_status);
        $this->assertGreaterThan($beforeTxns, AccountTransaction::count());
    }

    public function test_inventory_product_and_stock_operations(): void
    {
        $this->actingAs($this->admin());
        $cat = Category::whereNull('parent_id')->firstOrFail();
        $unit = Unit::firstOrFail();

        Livewire::test(ProductManager::class)
            ->call('openCreate')
            ->set('name', 'NVMe SSD 2TB')
            ->set('sku', 'SKU-SSD-2TB')
            ->set('category_id', $cat->id)
            ->set('unit_id', $unit->id)
            ->set('cost_price', 110)
            ->set('selling_price', 165)
            ->set('stock_quantity', 20)
            ->set('reorder_level', 5)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('sku', 'SKU-SSD-2TB')->firstOrFail();
        $this->assertEquals(20.0, (float) $product->stock_quantity);

        Livewire::test(StockOperations::class)
            ->set('product_id', $product->id)
            ->set('stock_action', 'in')
            ->set('quantity', 5)
            ->set('notes', 'Direct stock replenishment')
            ->call('recordStockInOut')
            ->assertHasNoErrors();

        $this->assertEquals(25.0, (float) $product->fresh()->stock_quantity);

        Livewire::test(StockOperations::class)
            ->set('product_id', $product->id)
            ->set('adj_type', 'decrease')
            ->set('quantity', 3)
            ->set('adj_reason', 'Audit recount')
            ->call('recordAdjustment')
            ->assertHasNoErrors();

        $this->assertEquals(22.0, (float) $product->fresh()->stock_quantity);
    }

    public function test_sales_flow_order_invoice_payment_and_return(): void
    {
        $this->actingAs($this->admin());
        $wh = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::where('is_service', false)->where('stock_quantity', '>', 10)->firstOrFail();
        $initialQty = (float) $product->stock_quantity;

        Livewire::test(CustomerManager::class)
            ->call('openCreate')
            ->set('name', 'Apex Industries')
            ->set('email', 'billing@apex.example.com')
            ->call('save')
            ->assertHasNoErrors();

        $cust = Customer::where('name', 'Apex Industries')->firstOrFail();

        Livewire::test(SalesOrderManager::class)
            ->call('openCreate')
            ->set('customer_id', $cust->id)
            ->set('warehouse_id', $wh->id)
            ->set('items', [[
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => 3,
                'unit_price' => 200,
                'tax_rate' => 5,
            ]])
            ->call('saveOrder')
            ->assertHasNoErrors();

        $so = SalesOrder::where('customer_id', $cust->id)->latest('id')->firstOrFail();

        Livewire::test(SalesOrderManager::class)
            ->call('convertToInvoice', $so->id)
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertEquals($initialQty - 3, (float) $product->stock_quantity);

        $inv = SalesInvoice::where('sales_order_id', $so->id)->firstOrFail();

        Livewire::test(SalesInvoiceManager::class)
            ->call('openCollectPayment', $inv->id)
            ->set('pay_amount', (float) $inv->balance_due)
            ->call('collectPayment')
            ->assertHasNoErrors();

        $this->assertSame('paid', $inv->fresh()->status);

        Livewire::test(SalesOperations::class)
            ->set('customer_id', $cust->id)
            ->set('return_product_id', $product->id)
            ->set('return_warehouse_id', $wh->id)
            ->set('return_qty', 1)
            ->set('return_price', 200)
            ->set('refund_method', 'cash')
            ->call('recordSalesReturn')
            ->assertHasNoErrors();

        $this->assertEquals($initialQty - 2, (float) $product->fresh()->stock_quantity);
    }

    public function test_purchase_flow_po_grn_bill_and_supplier_payment(): void
    {
        $this->actingAs($this->admin());
        $supplier = Supplier::firstOrFail();
        $wh = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::where('is_service', false)->firstOrFail();
        $startQty = (float) $product->stock_quantity;

        Livewire::test(PurchaseOrderManager::class)
            ->call('openCreate')
            ->set('supplier_id', $supplier->id)
            ->set('warehouse_id', $wh->id)
            ->set('items', [[
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => 5,
                'unit_cost' => 100,
                'tax_rate' => 5,
            ]])
            ->call('saveOrder')
            ->assertHasNoErrors();

        $po = PurchaseOrder::latest('id')->firstOrFail();

        Livewire::test(PurchaseOrderManager::class)
            ->call('openReceiveGrn', $po->id)
            ->call('receiveGoods')
            ->assertHasNoErrors();

        $this->assertEquals($startQty + 5, (float) $product->fresh()->stock_quantity);
        $this->assertSame('received', $po->fresh()->status);

        Livewire::test(PurchaseOrderManager::class)
            ->call('createBillFromPo', $po->id)
            ->assertHasNoErrors();

        $bill = PurchaseInvoice::where('purchase_order_id', $po->id)->firstOrFail();

        Livewire::test(PurchaseInvoiceManager::class)
            ->call('openPayModal', $bill->id)
            ->set('pay_amount', (float) $bill->balance_due)
            ->call('paySupplier')
            ->assertHasNoErrors();

        $this->assertSame('paid', $bill->fresh()->status);
    }

    public function test_finance_expenses_income_vouchers_and_reports(): void
    {
        $this->actingAs($this->admin());
        $expCat = ExpenseCategory::firstOrFail();

        Livewire::test(ExpenseIncomeManager::class)
            ->call('openCreateExpense')
            ->set('expense_category_id', $expCat->id)
            ->set('title', 'Office Cloud Hosting')
            ->set('amount', 320)
            ->set('bank_account_id', null)
            ->call('saveExpense')
            ->assertHasNoErrors();

        Livewire::test(ExpenseIncomeManager::class)
            ->call('openCreateIncome')
            ->set('income_category', 'Consulting')
            ->set('income_title', 'ERP Architecture Audit')
            ->set('income_amount', 750)
            ->set('bank_account_id', null)
            ->call('saveIncome')
            ->assertHasNoErrors();

        Livewire::test(BooksManager::class)
            ->set('voucher_type', 'inflow')
            ->set('voucher_book', 'cash')
            ->set('voucher_amount', 250)
            ->set('voucher_category', 'Petty Cash Topup')
            ->call('createVoucher')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', ['title' => 'Office Cloud Hosting']);
        $this->assertDatabaseHas('incomes', ['title' => 'ERP Architecture Audit']);
        $this->assertDatabaseHas('account_transactions', ['category' => 'Petty Cash Topup']);

        Livewire::test(FinancialReports::class)
            ->assertSee('Income Statement')
            ->set('reportTab', 'balance_sheet')
            ->assertSee('Total Assets');
    }

    public function test_job_card_full_service_lifecycle_and_billing(): void
    {
        $this->actingAs($this->admin());
        $cust = Customer::firstOrFail();
        $tech = Employee::firstOrFail();
        $sparePart = Product::where('is_service', false)->where('stock_quantity', '>', 5)->firstOrFail();
        $startStock = (float) $sparePart->stock_quantity;

        Livewire::test(JobCardIndex::class)
            ->call('openCreate')
            ->set('customer_id', $cust->id)
            ->set('assigned_to', $tech->id)
            ->set('item_name', 'Workstation Pro X1')
            ->set('brand_model', 'Lenovo ThinkStation')
            ->set('serial_number', 'SN-998811')
            ->set('service_description', 'Power supply unit and SSD replacement')
            ->call('createJobCard')
            ->assertHasNoErrors();

        $jc = JobCard::where('serial_number', 'SN-998811')->firstOrFail();

        Livewire::test(JobCardDetail::class, ['id' => $jc->id])
            ->set('labor_charges', 150)
            ->set('status', 'awaiting_approval')
            ->set('progress_percent', 30)
            ->call('saveWorkflow')
            ->set('part_product_id', $sparePart->id)
            ->set('part_name', $sparePart->name)
            ->set('part_qty', 1)
            ->set('part_price', (float) $sparePart->selling_price)
            ->call('addSparePart')
            ->set('approval_notes', 'Customer approved quote via phone')
            ->call('recordCustomerApproval', 'approved')
            ->set('qc_notes', 'Burn-in stress test passed')
            ->call('recordQualityCheck', 'passed')
            ->set('delivered_to', $cust->name)
            ->call('markDelivered')
            ->call('generateServiceInvoice')
            ->assertHasNoErrors();

        $jc->refresh();
        $this->assertSame('delivered', $jc->status);
        $this->assertSame('passed', $jc->qc_status);
        $this->assertSame('approved', $jc->customer_approval_status);
        $this->assertNotNull($jc->sales_invoice_id);
        $this->assertEquals($startStock - 1, (float) $sparePart->fresh()->stock_quantity);

        $serviceInv = SalesInvoice::findOrFail($jc->sales_invoice_id);
        $this->assertSame('job_card', $serviceInv->source);

        Livewire::test(JobCardDetail::class, ['id' => $jc->id])
            ->call('openCollectPayment')
            ->set('pay_amount', (float) $serviceInv->balance_due)
            ->call('collectPayment')
            ->assertHasNoErrors();

        $this->assertSame('paid', $serviceInv->fresh()->status);

        // Verify payment history renders cleanly inside JobCardDetail
        Livewire::test(JobCardDetail::class, ['id' => $jc->id])
            ->assertSee('Payment History')
            ->assertSee($cust->name);
    }

    public function test_product_detail_gallery_and_customer_vendor_profiles_and_job_card_multi_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $product = Product::where('is_service', false)->firstOrFail();
        $p1 = UploadedFile::fake()->image('front.jpg', 300, 300);
        $p2 = UploadedFile::fake()->image('side.jpg', 300, 300);

        Livewire::test(ProductDetail::class, ['id' => $product->id])
            ->set('photos', [$p1, $p2])
            ->set('photoCaption', 'Product Gallery')
            ->call('uploadImages')
            ->assertHasNoErrors()
            ->assertSee($product->sku);

        $this->assertSame(2, $product->images()->count());
        foreach ($product->images as $img) {
            Storage::disk('public')->assertExists($img->image_path);
        }

        // Customer Profile + Payment Collection
        $customer = Customer::firstOrFail();
        $openInv = SalesInvoice::where('customer_id', $customer->id)->where('balance_due', '>', 0)->first();
        if (! $openInv) {
            $openInv = SalesInvoice::create([
                'invoice_no' => 'INV-TEST-901',
                'source' => 'sales',
                'customer_id' => $customer->id,
                'invoice_date' => now()->toDateString(),
                'status' => 'unpaid',
                'subtotal' => 200,
                'tax_amount' => 10,
                'total_amount' => 210,
                'paid_amount' => 0,
                'balance_due' => 210,
            ]);
        }

        Livewire::test(CustomerProfile::class, ['id' => $customer->id])
            ->assertSee($customer->name)
            ->call('openCollectPayment', $openInv->id)
            ->set('pay_amount', (float) $openInv->balance_due)
            ->call('collectPayment')
            ->assertHasNoErrors();

        $this->assertSame('paid', $openInv->fresh()->status);

        // Vendor Profile + Payment Option
        $supplier = Supplier::firstOrFail();
        $openBill = PurchaseInvoice::where('supplier_id', $supplier->id)->where('balance_due', '>', 0)->first();
        if (! $openBill) {
            $openBill = PurchaseInvoice::create([
                'bill_no' => 'PINV-TEST-901',
                'supplier_id' => $supplier->id,
                'invoice_date' => now()->toDateString(),
                'status' => 'unpaid',
                'subtotal' => 300,
                'tax_amount' => 15,
                'total_amount' => 315,
                'paid_amount' => 0,
                'balance_due' => 315,
            ]);
        }

        Livewire::test(VendorProfile::class, ['id' => $supplier->id])
            ->assertSee($supplier->name)
            ->call('openPayModal', $openBill->id)
            ->set('pay_amount', (float) $openBill->balance_due)
            ->call('makePayment')
            ->assertHasNoErrors();

        $this->assertSame('paid', $openBill->fresh()->status);

        // Job Card Multiple Image Upload on Creation + Detail View
        $j1 = UploadedFile::fake()->image('intake-1.jpg', 300, 300);
        $j2 = UploadedFile::fake()->image('intake-2.jpg', 300, 300);

        Livewire::test(JobCardIndex::class)
            ->call('openCreate')
            ->set('customer_id', $customer->id)
            ->set('item_name', 'Laser Printer Pro')
            ->set('serial_number', 'LP-MULTI-IMG')
            ->set('service_description', 'Paper feed & fuser assembly check')
            ->set('photos', [$j1, $j2])
            ->call('createJobCard')
            ->assertHasNoErrors();

        $jc = JobCard::where('serial_number', 'LP-MULTI-IMG')->firstOrFail();
        $this->assertSame(2, $jc->images()->count());

        $j3 = UploadedFile::fake()->image('repair-stage.jpg', 300, 300);
        Livewire::test(JobCardDetail::class, ['id' => $jc->id])
            ->set('photoStage', 'repair')
            ->set('photoCaption', 'Replaced fuser roller')
            ->set('photos', [$j3])
            ->call('uploadImages')
            ->assertHasNoErrors()
            ->assertSee('Attached Device & Inspection Images (3)');

        $this->assertSame(3, $jc->images()->count());
        foreach ($jc->images as $img) {
            Storage::disk('public')->assertExists($img->image_path);
        }

        // Quick-add Customer, Searchable Customer Select, Direct Shop / On-Site Mode, and Incremental newPhotos upload
        $s1 = UploadedFile::fake()->image('site-1.jpg', 300, 300);
        $s2 = UploadedFile::fake()->image('site-2.jpg', 300, 300);

        Livewire::test(JobCardIndex::class)
            ->call('openCreate')
            ->set('new_cust_name', 'Quick Walk-in Customer')
            ->set('new_cust_phone', '+1 555 0199')
            ->call('saveQuickCustomer')
            ->set('parts_source', 'site')
            ->set('service_location', 'Building 7, Floor 3')
            ->set('item_name', 'Commercial HVAC Unit')
            ->set('service_description', 'On-site compressor inspection')
            ->set('newPhotos', [$s1])
            ->set('newPhotos', [$s2])
            ->call('createJobCard')
            ->assertHasNoErrors();

        $siteCustomer = Customer::where('name', 'Quick Walk-in Customer')->firstOrFail();
        $siteJc = JobCard::where('customer_id', $siteCustomer->id)->firstOrFail();
        $this->assertSame('site', $siteJc->parts_source);
        $this->assertNull($siteJc->warehouse_id);
        $this->assertSame('Building 7, Floor 3', $siteJc->service_location);
        $this->assertSame(2, $siteJc->images()->count());

        Livewire::test(JobCardIndex::class)
            ->call('openCreate')
            ->set('customerSearch', 'Walk-in')
            ->assertSee('Quick Walk-in Customer')
            ->call('selectCustomer', $siteCustomer->id)
            ->set('parts_source', 'direct')
            ->set('item_name', 'Bench Power Unit')
            ->set('service_description', 'Direct shop bench repair')
            ->call('createJobCard')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('job_cards', [
            'customer_id' => $siteCustomer->id,
            'item_name' => 'Bench Power Unit',
            'parts_source' => 'direct',
            'warehouse_id' => null,
        ]);

        // Verify updating job card fields (priority, discount, expected delivery) & removing part restores stock
        $benchJc = JobCard::where('item_name', 'Bench Power Unit')->firstOrFail();
        $partProd = Product::where('is_service', false)->where('stock_quantity', '>', 5)->firstOrFail();
        $qtyBefore = (float) $partProd->stock_quantity;

        Livewire::test(JobCardDetail::class, ['id' => $benchJc->id])
            ->set('priority', 'urgent')
            ->set('discount_amount', 25)
            ->set('labor_charges', 125)
            ->call('saveWorkflow')
            ->set('part_product_id', $partProd->id)
            ->set('part_name', $partProd->name)
            ->set('part_qty', 2)
            ->set('part_price', 50)
            ->call('addSparePart')
            ->assertHasNoErrors();

        $this->assertEquals($qtyBefore - 2, (float) $partProd->fresh()->stock_quantity);
        $addedPart = $benchJc->parts()->firstOrFail();

        Livewire::test(JobCardDetail::class, ['id' => $benchJc->id])
            ->call('deleteSparePart', $addedPart->id)
            ->assertHasNoErrors();

        $this->assertEquals($qtyBefore, (float) $partProd->fresh()->stock_quantity);
        $this->assertSame('urgent', $benchJc->fresh()->priority);
        $this->assertEquals(25.00, (float) $benchJc->fresh()->discount_amount);
    }

    public function test_bills_and_orders_cannot_contain_duplicate_products(): void
    {
        $this->actingAs($this->admin());
        $cust = Customer::firstOrFail();
        $sup = Supplier::firstOrFail();
        $prod = Product::where('is_service', false)->firstOrFail();

        Livewire::test(SalesInvoiceManager::class)
            ->call('openCreate')
            ->set('customer_id', $cust->id)
            ->call('addItem')
            ->set('items.0.product_id', $prod->id)
            ->set('items.1.product_id', $prod->id)
            ->assertSet('items.1.product_id', '')
            ->assertHasErrors(['items.1.product_id']);

        Livewire::test(SalesInvoiceManager::class)
            ->call('openCreate')
            ->set('customer_id', $cust->id)
            ->set('items', [
                ['product_id' => $prod->id, 'description' => $prod->name, 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 5],
                ['product_id' => $prod->id, 'description' => $prod->name, 'quantity' => 2, 'unit_price' => 100, 'tax_rate' => 5],
            ])
            ->call('saveInvoice')
            ->assertHasErrors(['items']);

        Livewire::test(PurchaseInvoiceManager::class)
            ->call('openCreate')
            ->set('supplier_id', $sup->id)
            ->call('addItem')
            ->set('items.0.product_id', $prod->id)
            ->set('items.1.product_id', $prod->id)
            ->assertSet('items.1.product_id', '')
            ->assertHasErrors(['items.1.product_id']);

        Livewire::test(PurchaseOrderManager::class)
            ->call('openCreate')
            ->set('supplier_id', $sup->id)
            ->set('items', [
                ['product_id' => $prod->id, 'description' => $prod->name, 'quantity' => 1, 'unit_cost' => 50, 'tax_rate' => 5],
                ['product_id' => $prod->id, 'description' => $prod->name, 'quantity' => 3, 'unit_cost' => 50, 'tax_rate' => 5],
            ])
            ->call('saveOrder')
            ->assertHasErrors(['items']);
    }
}
