<?php

namespace Database\Seeders;

use App\Models\AccountTransaction;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Income;
use App\Models\JobCard;
use App\Models\JobCardLog;
use App\Models\JobCardPart;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PaymentMethod;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TaxRate;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ErpSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions (Module-wise Access Control across all 8 modules)
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full unrestricted access across all ERP modules.',
            'status' => 'active',
        ]);

        $managerRole = Role::create([
            'name' => 'Operations Manager',
            'slug' => 'manager',
            'description' => 'Manage HRM, Inventory, Sales, Purchase, Finance, and Job Cards.',
            'status' => 'active',
        ]);

        $staffRole = Role::create([
            'name' => 'Staff / Technician',
            'slug' => 'staff',
            'description' => 'Day-to-day operations for Sales, Inventory, and Service Job Cards.',
            'status' => 'active',
        ]);

        $moduleCatalog = [
            ['module' => 'dashboard', 'feature' => 'Executive Dashboard', 'slug' => 'dashboard.overview'],
            ['module' => 'settings', 'feature' => 'Company & App Settings', 'slug' => 'settings.manage'],
            ['module' => 'hrm', 'feature' => 'Employees & Lifecycle', 'slug' => 'hrm.employees'],
            ['module' => 'hrm', 'feature' => 'Attendance & Leave', 'slug' => 'hrm.attendance'],
            ['module' => 'hrm', 'feature' => 'Payroll Processing', 'slug' => 'hrm.payroll'],
            ['module' => 'inventory', 'feature' => 'Products & Categories', 'slug' => 'inventory.products'],
            ['module' => 'inventory', 'feature' => 'Stock Management & Transfers', 'slug' => 'inventory.stock'],
            ['module' => 'sales', 'feature' => 'Customers & Sales Orders', 'slug' => 'sales.orders'],
            ['module' => 'sales', 'feature' => 'Invoices, Payments & Returns', 'slug' => 'sales.invoices'],
            ['module' => 'purchase', 'feature' => 'Purchase Orders & GRN', 'slug' => 'purchase.orders'],
            ['module' => 'purchase', 'feature' => 'Bills, Payments & Returns', 'slug' => 'purchase.invoices'],
            ['module' => 'finance', 'feature' => 'Income, Expenses & Books', 'slug' => 'finance.ledger'],
            ['module' => 'finance', 'feature' => 'Financial Reports', 'slug' => 'finance.reports'],
            ['module' => 'job_cards', 'feature' => 'Job Cards & Quality Check', 'slug' => 'job_cards.manage'],
            ['module' => 'job_cards', 'feature' => 'Service Billing & Reports', 'slug' => 'job_cards.billing'],
            ['module' => 'users', 'feature' => 'Users, Roles & Logs', 'slug' => 'users.access'],
        ];

        foreach ($moduleCatalog as $item) {
            $perm = Permission::create($item);

            // Admin: full access
            RolePermission::create([
                'role_id' => $adminRole->id,
                'permission_id' => $perm->id,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);

            // Manager: everything except deleting users/settings
            RolePermission::create([
                'role_id' => $managerRole->id,
                'permission_id' => $perm->id,
                'can_view' => true,
                'can_create' => ! in_array($item['module'], ['users']),
                'can_edit' => ! in_array($item['module'], ['users']),
                'can_delete' => false,
            ]);

            // Staff: view & create in operational modules
            $staffAllowed = in_array($item['module'], ['dashboard', 'inventory', 'sales', 'job_cards']);
            RolePermission::create([
                'role_id' => $staffRole->id,
                'permission_id' => $perm->id,
                'can_view' => $staffAllowed,
                'can_create' => $staffAllowed,
                'can_edit' => $item['module'] === 'job_cards',
                'can_delete' => false,
            ]);
        }

        // 2. Departments & Designations
        $deptEng = Department::create(['name' => 'Engineering & Service', 'code' => 'ENG', 'description' => 'Technical service, repairs & engineering', 'status' => 'active']);
        $deptSales = Department::create(['name' => 'Sales & CRM', 'code' => 'SLS', 'description' => 'B2B and retail sales operations', 'status' => 'active']);
        $deptFin = Department::create(['name' => 'Finance & Accounts', 'code' => 'FIN', 'description' => 'Accounting, payroll & audit', 'status' => 'active']);
        $deptHr = Department::create(['name' => 'Human Resources', 'code' => 'HRM', 'description' => 'Talent, payroll & employee lifecycle', 'status' => 'active']);
        $deptInv = Department::create(['name' => 'Inventory & Procurement', 'code' => 'INV', 'description' => 'Warehouse, stock & vendor purchasing', 'status' => 'active']);

        $desigMgr = Designation::create(['department_id' => $deptEng->id, 'name' => 'Operations Manager', 'grade' => 'M2', 'status' => 'active']);
        $desigTech = Designation::create(['department_id' => $deptEng->id, 'name' => 'Senior Service Technician', 'grade' => 'E3', 'status' => 'active']);
        $desigSales = Designation::create(['department_id' => $deptSales->id, 'name' => 'Sales Executive', 'grade' => 'E2', 'status' => 'active']);
        $desigAcc = Designation::create(['department_id' => $deptFin->id, 'name' => 'Senior Accountant', 'grade' => 'E3', 'status' => 'active']);
        $desigHr = Designation::create(['department_id' => $deptHr->id, 'name' => 'HR Specialist', 'grade' => 'E2', 'status' => 'active']);
        $desigStore = Designation::create(['department_id' => $deptInv->id, 'name' => 'Warehouse Supervisor', 'grade' => 'E2', 'status' => 'active']);

        // 3. Users (Admin, Manager, Staff)
        $adminUser = User::create([
            'name' => 'Jamie Anderson',
            'email' => 'admin@dreamserp.com',
            'phone' => '+1 202 555 0101',
            'role_id' => $adminRole->id,
            'department_id' => $deptFin->id,
            'user_type' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
            'last_login_at' => now()->subHours(1),
        ]);

        $managerUser = User::create([
            'name' => 'Ethan Walker',
            'email' => 'manager@dreamserp.com',
            'phone' => '+1 202 555 0142',
            'role_id' => $managerRole->id,
            'department_id' => $deptEng->id,
            'user_type' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password'),
            'last_login_at' => now()->subHours(3),
        ]);

        $staffUser = User::create([
            'name' => 'Steven Schroer',
            'email' => 'staff@dreamserp.com',
            'phone' => '+1 202 555 0188',
            'role_id' => $staffRole->id,
            'department_id' => $deptEng->id,
            'user_type' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        // 4. Settings, Bank Accounts, Payment Methods, Tax Rates
        CompanySetting::create([
            'company_name' => 'Dreams ERP Solutions Inc.',
            'trade_name' => 'Dreams ERP',
            'tagline' => 'One Platform. Every Business Operation Unified.',
            'registration_no' => 'REG-US-2026-4410',
            'tax_number' => 'TRN-890451230',
            'license_no' => 'BIZ-994102',
            'business_type' => 'Private Limited',
            'industry' => 'Enterprise Technology & Hardware Services',
            'website' => 'https://dreamserp.example.com',
            'email' => 'contact@dreamserp.com',
            'support_email' => 'support@dreamserp.com',
            'phone' => '+1 (212) 555-0190',
            'mobile' => '+1 (212) 555-0199',
            'address_line1' => '123 Business Ave, Suite 400',
            'address_line2' => 'Midtown Manhattan',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'United States',
            'postal_code' => '10001',
            'default_theme' => 'light',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'currency_decimals' => 2,
            'tax_enabled' => true,
            'tax_label' => 'VAT',
            'default_tax_rate' => 5.00,
            'date_format' => 'd M Y',
            'time_format' => 'h:i A',
            'timezone' => 'UTC',
            'language' => 'en',
            'fiscal_year_label' => '2026-2027',
            'fiscal_year_start_month' => 1,
            'fiscal_year_end_month' => 12,
            'invoice_terms' => 'Payment due within 15 days. Please reference the invoice number on bank transfers.',
            'invoice_footer_note' => 'Thank you for choosing Dreams ERP Solutions.',
            'low_stock_threshold' => 8,
            'per_page' => 10,
        ]);

        $bankMain = BankAccount::create([
            'account_name' => 'Primary Operating Account',
            'bank_name' => 'Chase Business Bank',
            'account_number' => '4400981274',
            'iban' => 'US64CHAS00004400981274',
            'swift_code' => 'CHASUS33',
            'branch' => 'Manhattan Main',
            'account_type' => 'checking',
            'opening_balance' => 85000.00,
            'current_balance' => 103450.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $bankReserve = BankAccount::create([
            'account_name' => 'Payroll & Reserve Account',
            'bank_name' => 'Bank of America',
            'account_number' => '8820194401',
            'iban' => 'US12BOFA00008820194401',
            'swift_code' => 'BOFAUS3N',
            'branch' => 'Downtown Corporate',
            'account_type' => 'savings',
            'opening_balance' => 45000.00,
            'current_balance' => 45000.00,
            'is_default' => false,
            'status' => 'active',
        ]);

        $pmCash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'description' => 'Cash Drawer / Petty Cash', 'is_active' => true]);
        $pmBank = PaymentMethod::create(['name' => 'Bank Transfer', 'code' => 'BANK', 'type' => 'bank', 'bank_account_id' => $bankMain->id, 'description' => 'Wire / ACH Bank Transfer', 'is_active' => true]);
        $pmCard = PaymentMethod::create(['name' => 'Credit / Debit Card', 'code' => 'CARD', 'type' => 'card', 'bank_account_id' => $bankMain->id, 'description' => 'POS Terminal & Card Payments', 'is_active' => true]);
        PaymentMethod::create(['name' => 'Cheque', 'code' => 'CHEQUE', 'type' => 'cheque', 'bank_account_id' => $bankMain->id, 'description' => 'Company or Customer Cheque', 'is_active' => true]);

        $taxVat5 = TaxRate::create(['name' => 'Standard VAT 5%', 'code' => 'VAT5', 'rate' => 5.00, 'is_default' => true, 'is_active' => true]);
        TaxRate::create(['name' => 'GST 18%', 'code' => 'GST18', 'rate' => 18.00, 'is_default' => false, 'is_active' => true]);
        TaxRate::create(['name' => 'Zero Rated 0%', 'code' => 'ZERO', 'rate' => 0.00, 'is_default' => false, 'is_active' => true]);

        // 5. HRM: Employees, Lifecycle Events, Leave Types & Requests, Attendance, Payroll
        $emp1 = Employee::create([
            'emp_code' => 'EMP-0001',
            'user_id' => $adminUser->id,
            'first_name' => 'Jamie',
            'last_name' => 'Anderson',
            'email' => 'admin@dreamserp.com',
            'phone' => '+1 202 555 0101',
            'gender' => 'Male',
            'department_id' => $deptFin->id,
            'designation_id' => $desigAcc->id,
            'employment_type' => 'full_time',
            'shift' => 'Day',
            'join_date' => '2024-02-01',
            'confirmation_date' => '2024-05-01',
            'lifecycle_status' => 'confirmed',
            'basic_salary' => 6500,
            'allowances' => 1200,
            'bank_name' => 'Chase Business Bank',
            'bank_account_no' => '****8841',
            'status' => 'active',
        ]);

        $emp2 = Employee::create([
            'emp_code' => 'EMP-0002',
            'user_id' => $managerUser->id,
            'first_name' => 'Ethan',
            'last_name' => 'Walker',
            'email' => 'manager@dreamserp.com',
            'phone' => '+1 202 555 0142',
            'gender' => 'Male',
            'department_id' => $deptEng->id,
            'designation_id' => $desigMgr->id,
            'employment_type' => 'full_time',
            'shift' => 'Day',
            'join_date' => '2024-06-15',
            'confirmation_date' => '2024-09-15',
            'lifecycle_status' => 'confirmed',
            'basic_salary' => 5800,
            'allowances' => 950,
            'bank_name' => 'Chase Business Bank',
            'bank_account_no' => '****3329',
            'status' => 'active',
        ]);

        $emp3 = Employee::create([
            'emp_code' => 'EMP-0003',
            'user_id' => $staffUser->id,
            'first_name' => 'Steven',
            'last_name' => 'Schroer',
            'email' => 'staff@dreamserp.com',
            'phone' => '+1 202 555 0188',
            'gender' => 'Male',
            'department_id' => $deptEng->id,
            'designation_id' => $desigTech->id,
            'manager_id' => $emp2->id,
            'employment_type' => 'full_time',
            'shift' => 'Day',
            'join_date' => '2025-01-10',
            'confirmation_date' => '2025-04-10',
            'lifecycle_status' => 'confirmed',
            'basic_salary' => 4200,
            'allowances' => 600,
            'bank_name' => 'Bank of America',
            'bank_account_no' => '****7710',
            'status' => 'active',
        ]);

        $emp4 = Employee::create([
            'emp_code' => 'EMP-0004',
            'first_name' => 'Chloe',
            'last_name' => 'Mitchell',
            'email' => 'chloe.mitchell@dreamserp.com',
            'phone' => '+1 202 555 0164',
            'gender' => 'Female',
            'department_id' => $deptSales->id,
            'designation_id' => $desigSales->id,
            'manager_id' => $emp2->id,
            'employment_type' => 'full_time',
            'shift' => 'Day',
            'join_date' => '2026-09-01',
            'probation_end_date' => '2026-12-01',
            'lifecycle_status' => 'probation',
            'basic_salary' => 3800,
            'allowances' => 500,
            'status' => 'active',
        ]);

        $emp5 = Employee::create([
            'emp_code' => 'EMP-0005',
            'first_name' => 'Avery',
            'last_name' => 'Thompson',
            'email' => 'avery.thompson@dreamserp.com',
            'phone' => '+1 202 555 0195',
            'gender' => 'Female',
            'department_id' => $deptHr->id,
            'designation_id' => $desigHr->id,
            'employment_type' => 'full_time',
            'shift' => 'Day',
            'join_date' => '2025-05-01',
            'confirmation_date' => '2025-08-01',
            'lifecycle_status' => 'confirmed',
            'basic_salary' => 4400,
            'allowances' => 650,
            'status' => 'active',
        ]);

        $emp6 = Employee::create([
            'emp_code' => 'EMP-0006',
            'first_name' => 'Daniel',
            'last_name' => 'Roberts',
            'email' => 'daniel.roberts@dreamserp.com',
            'phone' => '+1 202 555 0123',
            'gender' => 'Male',
            'department_id' => $deptInv->id,
            'designation_id' => $desigStore->id,
            'employment_type' => 'full_time',
            'shift' => 'Day',
            'join_date' => now()->subDays(12)->toDateString(),
            'probation_end_date' => now()->addDays(78)->toDateString(),
            'lifecycle_status' => 'onboarding',
            'basic_salary' => 3600,
            'allowances' => 450,
            'status' => 'active',
        ]);

        EmployeeLifecycleEvent::create([
            'employee_id' => $emp3->id,
            'event_type' => 'promotion',
            'effective_date' => '2026-04-01',
            'old_value' => 'Junior Technician',
            'new_value' => 'Senior Service Technician',
            'reason' => 'Annual performance review excellence',
            'approved_by' => $adminUser->id,
        ]);
        EmployeeLifecycleEvent::create([
            'employee_id' => $emp3->id,
            'event_type' => 'salary_revision',
            'effective_date' => '2026-04-01',
            'old_value' => '$3,800',
            'new_value' => '$4,200',
            'reason' => 'Promotion increment',
            'approved_by' => $adminUser->id,
        ]);

        $ltAnnual = LeaveType::create(['name' => 'Annual Leave', 'code' => 'AL', 'days_per_year' => 24, 'is_paid' => true, 'status' => 'active']);
        $ltSick = LeaveType::create(['name' => 'Sick Leave', 'code' => 'SL', 'days_per_year' => 12, 'is_paid' => true, 'status' => 'active']);
        LeaveType::create(['name' => 'Unpaid Leave', 'code' => 'UL', 'days_per_year' => 30, 'is_paid' => false, 'status' => 'active']);

        LeaveRequest::create([
            'employee_id' => $emp4->id,
            'leave_type_id' => $ltAnnual->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'total_days' => 3,
            'reason' => 'Family travel out of state',
            'status' => 'pending',
        ]);
        LeaveRequest::create([
            'employee_id' => $emp3->id,
            'leave_type_id' => $ltSick->id,
            'start_date' => now()->subDays(6)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'total_days' => 2,
            'reason' => 'Seasonal flu recovery',
            'status' => 'approved',
            'approved_by' => $managerUser->id,
            'approved_at' => now()->subDays(7),
        ]);

        foreach ([$emp1, $emp2, $emp3, $emp4, $emp5, $emp6] as $idx => $emp) {
            Attendance::create([
                'employee_id' => $emp->id,
                'attendance_date' => now()->toDateString(),
                'check_in' => $idx === 3 ? '09:35:00' : '09:00:00',
                'check_out' => '18:00:00',
                'work_hours' => $idx === 3 ? 8.4 : 9.0,
                'status' => $idx === 3 ? 'late' : 'present',
            ]);
        }

        $payrollRun = PayrollRun::create([
            'run_code' => 'PAY-2026-10',
            'month' => (int) now()->subMonth()->month,
            'year' => (int) now()->subMonth()->year,
            'period_label' => now()->subMonth()->format('F Y'),
            'pay_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'total_gross' => 30150,
            'total_deductions' => 1200,
            'total_net' => 28950,
            'status' => 'paid',
            'processed_by' => $adminUser->id,
            'processed_at' => now()->subDays(22),
            'paid_at' => now()->subDays(20),
        ]);
        foreach ([$emp1, $emp2, $emp3, $emp4, $emp5] as $emp) {
            $taxDed = 240;
            $net = (float) $emp->basic_salary + (float) $emp->allowances - $taxDed;
            PayrollItem::create([
                'payroll_run_id' => $payrollRun->id,
                'employee_id' => $emp->id,
                'basic_salary' => $emp->basic_salary,
                'allowances' => $emp->allowances,
                'bonus' => 0,
                'leave_deduction' => 0,
                'tax_deduction' => $taxDed,
                'other_deduction' => 0,
                'net_salary' => $net,
                'payment_status' => 'paid',
                'payment_method_id' => $pmBank->id,
                'bank_account_id' => $bankMain->id,
                'paid_at' => now()->subDays(20),
                'payment_reference' => 'SAL-'.now()->subMonth()->format('Ym').'-'.$emp->id,
            ]);
        }

        // 6. Inventory: Categories, Subcategories, Brands, Units, Warehouses, Suppliers, Products
        $catElec = Category::create(['name' => 'Electronics', 'code' => 'ELEC', 'status' => 'active']);
        $catParts = Category::create(['name' => 'Spare Parts', 'code' => 'PART', 'status' => 'active']);
        $catOffice = Category::create(['name' => 'Office Hardware', 'code' => 'OFFC', 'status' => 'active']);

        $subLaptops = Category::create(['parent_id' => $catElec->id, 'name' => 'Laptops & Workstations', 'code' => 'ELEC-LAP', 'status' => 'active']);
        $subPhones = Category::create(['parent_id' => $catElec->id, 'name' => 'Smartphones & Tablets', 'code' => 'ELEC-PHN', 'status' => 'active']);
        $subBatteries = Category::create(['parent_id' => $catParts->id, 'name' => 'Batteries & Power', 'code' => 'PART-BAT', 'status' => 'active']);
        $subDisplays = Category::create(['parent_id' => $catParts->id, 'name' => 'Displays & Panels', 'code' => 'PART-DSP', 'status' => 'active']);
        $subPrinters = Category::create(['parent_id' => $catOffice->id, 'name' => 'Printers & Scanners', 'code' => 'OFFC-PRT', 'status' => 'active']);

        $brandApple = Brand::create(['name' => 'Apple', 'status' => 'active']);
        $brandDell = Brand::create(['name' => 'Dell', 'status' => 'active']);
        $brandHp = Brand::create(['name' => 'HP', 'status' => 'active']);
        $brandSamsung = Brand::create(['name' => 'Samsung', 'status' => 'active']);

        $unitPcs = Unit::create(['name' => 'Piece', 'short_name' => 'pcs', 'status' => 'active']);
        $unitBox = Unit::create(['name' => 'Box', 'short_name' => 'box', 'status' => 'active']);
        Unit::create(['name' => 'Set', 'short_name' => 'set', 'status' => 'active']);

        $whMain = Warehouse::create(['name' => 'Main Distribution Hub', 'code' => 'WH-MAIN', 'location' => 'New York, NY', 'manager_name' => 'Daniel Roberts', 'is_default' => true, 'status' => 'active']);
        $whService = Warehouse::create(['name' => 'Service Center Store', 'code' => 'WH-SVC', 'location' => 'Brooklyn Workshop', 'manager_name' => 'Steven Schroer', 'is_default' => false, 'status' => 'active']);

        $supTech = Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Apex Global Distributors',
            'company_name' => 'Apex Global Inc.',
            'contact_person' => 'Marcus Vance',
            'email' => 'orders@apexdistributors.com',
            'phone' => '+1 312 555 0144',
            'tax_number' => 'TAX-APX-9910',
            'address' => '750 Logistics Pkwy',
            'city' => 'Chicago',
            'country' => 'United States',
            'status' => 'active',
        ]);

        $supParts = Supplier::create([
            'code' => 'SUP-0002',
            'name' => 'CoreComponents Wholesale',
            'company_name' => 'CoreComponents LLC',
            'contact_person' => 'Elena Rostova',
            'email' => 'supply@corecomponents.io',
            'phone' => '+1 408 555 0171',
            'tax_number' => 'TAX-CRC-4420',
            'address' => '210 Silicon Way',
            'city' => 'San Jose',
            'country' => 'United States',
            'status' => 'active',
        ]);

        $productsData = [
            ['code' => 'PRD-0001', 'name' => 'Dell XPS 13 9340 Laptop', 'sku' => 'DEL-XPS-9340', 'category_id' => $catElec->id, 'subcategory_id' => $subLaptops->id, 'brand_id' => $brandDell->id, 'unit_id' => $unitPcs->id, 'default_supplier_id' => $supTech->id, 'cost_price' => 1120, 'selling_price' => 1450, 'stock_quantity' => 18, 'reorder_level' => 5],
            ['code' => 'PRD-0002', 'name' => 'Apple MacBook Pro 14 M4', 'sku' => 'APL-MBP-14M4', 'category_id' => $catElec->id, 'subcategory_id' => $subLaptops->id, 'brand_id' => $brandApple->id, 'unit_id' => $unitPcs->id, 'default_supplier_id' => $supTech->id, 'cost_price' => 1620, 'selling_price' => 1999, 'stock_quantity' => 4, 'reorder_level' => 6],
            ['code' => 'PRD-0003', 'name' => 'Apple iPhone 16 Pro 256GB', 'sku' => 'APL-IP16P-256', 'category_id' => $catElec->id, 'subcategory_id' => $subPhones->id, 'brand_id' => $brandApple->id, 'unit_id' => $unitPcs->id, 'default_supplier_id' => $supTech->id, 'cost_price' => 980, 'selling_price' => 1199, 'stock_quantity' => 24, 'reorder_level' => 8],
            ['code' => 'PRD-0004', 'name' => 'OEM Laptop Battery 68Wh', 'sku' => 'PRT-BAT-68WH', 'category_id' => $catParts->id, 'subcategory_id' => $subBatteries->id, 'brand_id' => $brandDell->id, 'unit_id' => $unitPcs->id, 'default_supplier_id' => $supParts->id, 'cost_price' => 55, 'selling_price' => 95, 'stock_quantity' => 3, 'reorder_level' => 10],
            ['code' => 'PRD-0005', 'name' => 'Retina 14" Replacement Display Assembly', 'sku' => 'PRT-DSP-RET14', 'category_id' => $catParts->id, 'subcategory_id' => $subDisplays->id, 'brand_id' => $brandApple->id, 'unit_id' => $unitPcs->id, 'default_supplier_id' => $supParts->id, 'cost_price' => 210, 'selling_price' => 340, 'stock_quantity' => 14, 'reorder_level' => 4],
            ['code' => 'PRD-0006', 'name' => 'HP LaserJet Pro MFP Printer', 'sku' => 'HP-LJ-MFP410', 'category_id' => $catOffice->id, 'subcategory_id' => $subPrinters->id, 'brand_id' => $brandHp->id, 'unit_id' => $unitBox->id, 'default_supplier_id' => $supTech->id, 'cost_price' => 310, 'selling_price' => 425, 'stock_quantity' => 11, 'reorder_level' => 4],
            ['code' => 'PRD-0007', 'name' => 'Samsung NVMe SSD 1TB Service Kit', 'sku' => 'SAM-SSD-1TB', 'category_id' => $catParts->id, 'subcategory_id' => $subBatteries->id, 'brand_id' => $brandSamsung->id, 'unit_id' => $unitPcs->id, 'default_supplier_id' => $supParts->id, 'cost_price' => 78, 'selling_price' => 129, 'stock_quantity' => 0, 'reorder_level' => 5],
        ];

        $products = [];
        foreach ($productsData as $pd) {
            $product = Product::create(array_merge($pd, [
                'tax_rate_id' => $taxVat5->id,
                'barcode' => '890'.rand(100000000, 999999999),
                'status' => 'active',
            ]));
            $products[] = $product;

            $mainQty = $pd['stock_quantity'] > 4 ? $pd['stock_quantity'] - 2 : $pd['stock_quantity'];
            $svcQty = $pd['stock_quantity'] > 4 ? 2 : 0;

            ProductStock::create(['product_id' => $product->id, 'warehouse_id' => $whMain->id, 'quantity' => $mainQty]);
            ProductStock::create(['product_id' => $product->id, 'warehouse_id' => $whService->id, 'quantity' => $svcQty]);

            if ($pd['stock_quantity'] > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $whMain->id,
                    'movement_type' => 'in',
                    'quantity_change' => $pd['stock_quantity'],
                    'balance_after' => $pd['stock_quantity'],
                    'unit_cost' => $pd['cost_price'],
                    'reference_no' => 'OPENING-STOCK',
                    'notes' => 'Initial warehouse stock balance',
                    'created_by' => $adminUser->id,
                ]);
            }
        }

        // 7. Customers & Sales (Orders, Invoices, Payments)
        $cust1 = Customer::create([
            'code' => 'CUST-0001',
            'name' => 'Alexander Kenn',
            'company_name' => 'TechCorp Industries',
            'email' => 'alex@techcorp.io',
            'phone' => '+1 415 555 0109',
            'tax_number' => 'US-991045',
            'billing_address' => '500 Market St, San Francisco, CA',
            'city' => 'San Francisco',
            'country' => 'United States',
            'credit_limit' => 50000,
            'status' => 'active',
        ]);

        $cust2 = Customer::create([
            'code' => 'CUST-0002',
            'name' => 'Gabriella White',
            'company_name' => 'NorthStar Creative Studio',
            'email' => 'gabriella@northstar.co',
            'phone' => '+1 212 555 0175',
            'billing_address' => '88 Madison Ave, New York, NY',
            'city' => 'New York',
            'country' => 'United States',
            'credit_limit' => 25000,
            'status' => 'active',
        ]);

        $cust3 = Customer::create([
            'code' => 'CUST-0003',
            'name' => 'Noah Bennett',
            'company_name' => 'Summit Logistics Group',
            'email' => 'noah@summitlogistics.com',
            'phone' => '+1 305 555 0132',
            'billing_address' => '1400 Brickell Ave, Miami, FL',
            'city' => 'Miami',
            'country' => 'United States',
            'credit_limit' => 30000,
            'status' => 'active',
        ]);

        $so1 = SalesOrder::create([
            'order_no' => 'SO-0001',
            'customer_id' => $cust1->id,
            'warehouse_id' => $whMain->id,
            'order_date' => now()->subDays(10)->toDateString(),
            'delivery_date' => now()->subDays(6)->toDateString(),
            'status' => 'delivered',
            'subtotal' => 7250,
            'tax_amount' => 362.50,
            'discount_amount' => 0,
            'shipping_charge' => 50,
            'total_amount' => 7662.50,
            'created_by' => $adminUser->id,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $so1->id,
            'product_id' => $products[0]->id,
            'description' => $products[0]->name,
            'quantity' => 5,
            'unit_price' => 1450,
            'tax_rate' => 5,
            'tax_amount' => 362.50,
            'line_total' => 7612.50,
        ]);

        $inv1 = SalesInvoice::create([
            'invoice_no' => 'INV-0001',
            'sales_order_id' => $so1->id,
            'source' => 'sales',
            'customer_id' => $cust1->id,
            'warehouse_id' => $whMain->id,
            'invoice_date' => now()->subDays(9)->toDateString(),
            'due_date' => now()->addDays(6)->toDateString(),
            'status' => 'paid',
            'subtotal' => 7250,
            'tax_amount' => 362.50,
            'shipping_charge' => 50,
            'total_amount' => 7662.50,
            'paid_amount' => 7662.50,
            'balance_due' => 0,
            'created_by' => $adminUser->id,
        ]);
        SalesInvoiceItem::create([
            'sales_invoice_id' => $inv1->id,
            'product_id' => $products[0]->id,
            'description' => $products[0]->name,
            'quantity' => 5,
            'unit_price' => 1450,
            'tax_rate' => 5,
            'tax_amount' => 362.50,
            'line_total' => 7612.50,
        ]);

        CustomerPayment::create([
            'receipt_no' => 'RCP-0001',
            'customer_id' => $cust1->id,
            'sales_invoice_id' => $inv1->id,
            'payment_date' => now()->subDays(5)->toDateString(),
            'amount' => 7662.50,
            'payment_method_id' => $pmBank->id,
            'bank_account_id' => $bankMain->id,
            'reference' => 'WIRE-TECHCORP-01',
            'created_by' => $adminUser->id,
        ]);

        // Unpaid / partial sales invoice for Outstanding Balances testing
        $inv2 = SalesInvoice::create([
            'invoice_no' => 'INV-0002',
            'source' => 'sales',
            'customer_id' => $cust2->id,
            'warehouse_id' => $whMain->id,
            'invoice_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(12)->toDateString(),
            'status' => 'partial',
            'subtotal' => 3597,
            'tax_amount' => 179.85,
            'total_amount' => 3776.85,
            'paid_amount' => 1500,
            'balance_due' => 2276.85,
            'created_by' => $managerUser->id,
        ]);
        SalesInvoiceItem::create([
            'sales_invoice_id' => $inv2->id,
            'product_id' => $products[2]->id,
            'description' => $products[2]->name,
            'quantity' => 3,
            'unit_price' => 1199,
            'tax_rate' => 5,
            'tax_amount' => 179.85,
            'line_total' => 3776.85,
        ]);
        CustomerPayment::create([
            'receipt_no' => 'RCP-0002',
            'customer_id' => $cust2->id,
            'sales_invoice_id' => $inv2->id,
            'payment_date' => now()->subDays(2)->toDateString(),
            'amount' => 1500.00,
            'payment_method_id' => $pmCard->id,
            'bank_account_id' => $bankMain->id,
            'reference' => 'CARD-AUTH-7821',
            'created_by' => $managerUser->id,
        ]);

        // 8. Purchases (Orders, GRN, Invoices, Supplier Payments)
        $po1 = PurchaseOrder::create([
            'po_no' => 'PO-0001',
            'supplier_id' => $supTech->id,
            'warehouse_id' => $whMain->id,
            'order_date' => now()->subDays(14)->toDateString(),
            'expected_date' => now()->subDays(8)->toDateString(),
            'status' => 'received',
            'subtotal' => 5600,
            'tax_amount' => 280,
            'total_amount' => 5880,
            'created_by' => $adminUser->id,
        ]);
        $poItem1 = PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'product_id' => $products[0]->id,
            'description' => $products[0]->name,
            'quantity' => 5,
            'received_quantity' => 5,
            'unit_cost' => 1120,
            'tax_rate' => 5,
            'tax_amount' => 280,
            'line_total' => 5880,
        ]);

        $grn1 = GoodsReceipt::create([
            'grn_no' => 'GRN-0001',
            'purchase_order_id' => $po1->id,
            'supplier_id' => $supTech->id,
            'warehouse_id' => $whMain->id,
            'received_date' => now()->subDays(11)->toDateString(),
            'status' => 'received',
            'received_by' => $adminUser->id,
        ]);
        GoodsReceiptItem::create([
            'goods_receipt_id' => $grn1->id,
            'product_id' => $products[0]->id,
            'purchase_order_item_id' => $poItem1->id,
            'quantity_received' => 5,
            'unit_cost' => 1120,
        ]);

        $pinv1 = PurchaseInvoice::create([
            'bill_no' => 'PINV-0001',
            'vendor_invoice_no' => 'APX-2026-881',
            'purchase_order_id' => $po1->id,
            'goods_receipt_id' => $grn1->id,
            'supplier_id' => $supTech->id,
            'warehouse_id' => $whMain->id,
            'invoice_date' => now()->subDays(11)->toDateString(),
            'due_date' => now()->addDays(19)->toDateString(),
            'status' => 'partial',
            'subtotal' => 5600,
            'tax_amount' => 280,
            'total_amount' => 5880,
            'paid_amount' => 3000,
            'balance_due' => 2880,
            'created_by' => $adminUser->id,
        ]);
        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $pinv1->id,
            'product_id' => $products[0]->id,
            'description' => $products[0]->name,
            'quantity' => 5,
            'unit_cost' => 1120,
            'tax_rate' => 5,
            'tax_amount' => 280,
            'line_total' => 5880,
        ]);
        SupplierPayment::create([
            'payment_no' => 'PAY-0001',
            'supplier_id' => $supTech->id,
            'purchase_invoice_id' => $pinv1->id,
            'payment_date' => now()->subDays(9)->toDateString(),
            'amount' => 3000,
            'payment_method_id' => $pmBank->id,
            'bank_account_id' => $bankMain->id,
            'reference' => 'ACH-APX-3000',
            'created_by' => $adminUser->id,
        ]);

        // Open PO awaiting delivery
        $po2 = PurchaseOrder::create([
            'po_no' => 'PO-0002',
            'supplier_id' => $supParts->id,
            'warehouse_id' => $whService->id,
            'order_date' => now()->subDays(2)->toDateString(),
            'expected_date' => now()->addDays(4)->toDateString(),
            'status' => 'ordered',
            'subtotal' => 1490,
            'tax_amount' => 74.50,
            'total_amount' => 1564.50,
            'created_by' => $managerUser->id,
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'product_id' => $products[3]->id,
            'description' => $products[3]->name,
            'quantity' => 10,
            'received_quantity' => 0,
            'unit_cost' => 55,
            'tax_rate' => 5,
            'tax_amount' => 27.50,
            'line_total' => 577.50,
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'product_id' => $products[6]->id,
            'description' => $products[6]->name,
            'quantity' => 12,
            'received_quantity' => 0,
            'unit_cost' => 78,
            'tax_rate' => 5,
            'tax_amount' => 46.80,
            'line_total' => 982.80,
        ]);

        // 9. Finance: Expense Categories, Expenses, Incomes, Account Transactions (Cash & Bank Books)
        $expCatRent = ExpenseCategory::create(['name' => 'Office & Facility Rent', 'code' => 'RENT', 'status' => 'active']);
        $expCatUtil = ExpenseCategory::create(['name' => 'Utilities & Cloud Hosting', 'code' => 'UTIL', 'status' => 'active']);
        $expCatSupp = ExpenseCategory::create(['name' => 'Office & Workshop Supplies', 'code' => 'SUPP', 'status' => 'active']);

        $exp1 = Expense::create([
            'expense_no' => 'EXP-0001',
            'expense_category_id' => $expCatRent->id,
            'title' => 'Monthly Office Lease - Main Hub',
            'expense_date' => now()->subDays(8)->toDateString(),
            'amount' => 3200,
            'tax_amount' => 0,
            'total_amount' => 3200,
            'payment_method_id' => $pmBank->id,
            'bank_account_id' => $bankMain->id,
            'reference' => 'RENT-NOV-26',
            'status' => 'approved',
            'created_by' => $adminUser->id,
        ]);
        $exp2 = Expense::create([
            'expense_no' => 'EXP-0002',
            'expense_category_id' => $expCatSupp->id,
            'title' => 'ESD Workbench & Precision Tools',
            'expense_date' => now()->subDays(4)->toDateString(),
            'amount' => 420,
            'tax_amount' => 21,
            'total_amount' => 441,
            'payment_method_id' => $pmCash->id,
            'reference' => 'PETTY-441',
            'status' => 'approved',
            'created_by' => $managerUser->id,
        ]);

        $inc1 = Income::create([
            'income_no' => 'INC-0001',
            'category' => 'Consulting & Retainer',
            'title' => 'Annual IT Maintenance Retainer - TechCorp',
            'income_date' => now()->subDays(7)->toDateString(),
            'amount' => 2400,
            'tax_amount' => 120,
            'total_amount' => 2520,
            'payment_method_id' => $pmBank->id,
            'bank_account_id' => $bankMain->id,
            'reference' => 'RET-2026-TC',
            'status' => 'received',
            'created_by' => $adminUser->id,
        ]);

        // Seed matching Cash Book & Bank Book ledger entries
        AccountTransaction::create([
            'transaction_no' => 'TXN-00001',
            'transaction_date' => now()->subDays(15)->toDateString(),
            'book_type' => 'cash',
            'type' => 'inflow',
            'category' => 'Opening Cash Float',
            'party_name' => 'Internal Cash Drawer',
            'reference_no' => 'OPEN-CASH',
            'payment_method_id' => $pmCash->id,
            'amount' => 5000.00,
            'description' => 'Opening petty cash float',
            'created_by' => $adminUser->id,
        ]);
        AccountTransaction::create([
            'transaction_no' => 'TXN-00002',
            'transaction_date' => now()->subDays(9)->toDateString(),
            'book_type' => 'bank',
            'bank_account_id' => $bankMain->id,
            'type' => 'outflow',
            'category' => 'Supplier Payment',
            'party_type' => Supplier::class,
            'party_id' => $supTech->id,
            'party_name' => $supTech->name,
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $pinv1->id,
            'reference_no' => 'PAY-0001',
            'payment_method_id' => $pmBank->id,
            'amount' => 3000.00,
            'description' => 'Partial payment for Bill PINV-0001',
            'created_by' => $adminUser->id,
        ]);
        AccountTransaction::create([
            'transaction_no' => 'TXN-00003',
            'transaction_date' => now()->subDays(8)->toDateString(),
            'book_type' => 'bank',
            'bank_account_id' => $bankMain->id,
            'type' => 'outflow',
            'category' => 'Operating Expense',
            'party_name' => 'Hub Landlord LLC',
            'reference_type' => Expense::class,
            'reference_id' => $exp1->id,
            'reference_no' => 'EXP-0001',
            'payment_method_id' => $pmBank->id,
            'amount' => 3200.00,
            'description' => 'Monthly Office Lease - Main Hub',
            'created_by' => $adminUser->id,
        ]);
        AccountTransaction::create([
            'transaction_no' => 'TXN-00004',
            'transaction_date' => now()->subDays(7)->toDateString(),
            'book_type' => 'bank',
            'bank_account_id' => $bankMain->id,
            'type' => 'inflow',
            'category' => 'Other Income',
            'party_name' => 'TechCorp Industries',
            'reference_type' => Income::class,
            'reference_id' => $inc1->id,
            'reference_no' => 'INC-0001',
            'payment_method_id' => $pmBank->id,
            'amount' => 2520.00,
            'description' => 'Annual IT Maintenance Retainer',
            'created_by' => $adminUser->id,
        ]);
        AccountTransaction::create([
            'transaction_no' => 'TXN-00005',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'book_type' => 'bank',
            'bank_account_id' => $bankMain->id,
            'type' => 'inflow',
            'category' => 'Sales Receipt',
            'party_type' => Customer::class,
            'party_id' => $cust1->id,
            'party_name' => $cust1->name,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $inv1->id,
            'reference_no' => 'RCP-0001',
            'payment_method_id' => $pmBank->id,
            'amount' => 7662.50,
            'description' => 'Invoice INV-0001 settlement',
            'created_by' => $adminUser->id,
        ]);
        AccountTransaction::create([
            'transaction_no' => 'TXN-00006',
            'transaction_date' => now()->subDays(4)->toDateString(),
            'book_type' => 'cash',
            'type' => 'outflow',
            'category' => 'Operating Expense',
            'party_name' => 'ToolMart',
            'reference_type' => Expense::class,
            'reference_id' => $exp2->id,
            'reference_no' => 'EXP-0002',
            'payment_method_id' => $pmCash->id,
            'amount' => 441.00,
            'description' => 'ESD Workbench & Precision Tools',
            'created_by' => $managerUser->id,
        ]);
        AccountTransaction::create([
            'transaction_no' => 'TXN-00007',
            'transaction_date' => now()->subDays(2)->toDateString(),
            'book_type' => 'bank',
            'bank_account_id' => $bankMain->id,
            'type' => 'inflow',
            'category' => 'Sales Receipt',
            'party_type' => Customer::class,
            'party_id' => $cust2->id,
            'party_name' => $cust2->name,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $inv2->id,
            'reference_no' => 'RCP-0002',
            'payment_method_id' => $pmCard->id,
            'amount' => 1500.00,
            'description' => 'Partial payment for Invoice INV-0002',
            'created_by' => $managerUser->id,
        ]);

        // 10. Job Card / Service Management
        $jc1 = JobCard::create([
            'job_card_no' => 'JC-0001',
            'customer_id' => $cust2->id,
            'product_id' => $products[1]->id,
            'warehouse_id' => $whService->id,
            'item_name' => 'Apple MacBook Pro 14 M4',
            'brand_model' => 'Apple A2992',
            'serial_number' => 'C02YM490Q6L4',
            'accessories_received' => 'MagSafe Charger, Sleeve',
            'service_description' => 'Cracked Retina display assembly replacement and full thermal diagnostics.',
            'diagnosis_notes' => 'Display backlight flex damaged; logic board passed diagnostics.',
            'assigned_to' => $emp3->id,
            'priority' => 'high',
            'status' => 'quality_check',
            'progress_percent' => 85,
            'received_date' => now()->subDays(3)->toDateString(),
            'expected_delivery_date' => now()->addDays(1)->toDateString(),
            'estimated_cost' => 490,
            'labor_charges' => 140,
            'parts_total' => 340,
            'tax_amount' => 24,
            'discount_amount' => 0,
            'total_amount' => 504,
            'customer_approval_status' => 'approved',
            'customer_approved_at' => now()->subDays(2),
            'approval_notes' => 'Customer approved quote via email',
            'qc_status' => 'pending',
            'created_by' => $managerUser->id,
        ]);
        JobCardPart::create([
            'job_card_id' => $jc1->id,
            'product_id' => $products[4]->id,
            'part_name' => $products[4]->name,
            'quantity' => 1,
            'unit_price' => 340,
            'line_total' => 340,
            'stock_deducted' => true,
        ]);
        JobCardLog::create(['job_card_id' => $jc1->id, 'stage' => 'registered', 'status' => 'registered', 'notes' => 'Device checked in at Brooklyn Service Desk', 'logged_by' => $managerUser->id]);
        JobCardLog::create(['job_card_id' => $jc1->id, 'stage' => 'customer_approval', 'status' => 'awaiting_approval', 'notes' => 'Customer approved $480 + tax estimate', 'logged_by' => $managerUser->id]);
        JobCardLog::create(['job_card_id' => $jc1->id, 'stage' => 'in_progress', 'status' => 'quality_check', 'notes' => 'Display assembly replaced; moved to QC bench', 'logged_by' => $staffUser->id]);

        $jc2 = JobCard::create([
            'job_card_no' => 'JC-0002',
            'customer_id' => $cust3->id,
            'product_id' => $products[0]->id,
            'warehouse_id' => $whService->id,
            'item_name' => 'Dell XPS 13 9340 Laptop',
            'brand_model' => 'Dell XPS 9340',
            'serial_number' => 'DXPS-994012',
            'accessories_received' => '65W USB-C Adapter',
            'service_description' => 'Battery swelling replacement and BIOS power controller firmware update.',
            'assigned_to' => $emp3->id,
            'priority' => 'medium',
            'status' => 'in_progress',
            'progress_percent' => 55,
            'received_date' => now()->subDay()->toDateString(),
            'expected_delivery_date' => now()->addDays(2)->toDateString(),
            'estimated_cost' => 185,
            'labor_charges' => 85,
            'parts_total' => 95,
            'tax_amount' => 9,
            'total_amount' => 189,
            'customer_approval_status' => 'approved',
            'customer_approved_at' => Carbon::now()->subHours(12),
            'created_by' => $managerUser->id,
        ]);
        JobCardPart::create([
            'job_card_id' => $jc2->id,
            'product_id' => $products[3]->id,
            'part_name' => $products[3]->name,
            'quantity' => 1,
            'unit_price' => 95,
            'line_total' => 95,
            'stock_deducted' => true,
        ]);
        JobCardLog::create(['job_card_id' => $jc2->id, 'stage' => 'registered', 'status' => 'registered', 'notes' => 'Job card created and assigned to Steven Schroer', 'logged_by' => $managerUser->id]);

        // 11. Sample Activity Logs
        ActivityLog::create(['user_id' => $adminUser->id, 'module' => 'settings', 'action' => 'seeded', 'description' => 'Initial ERP company settings, bank accounts, and tax rules initialized.', 'ip_address' => '127.0.0.1']);
        ActivityLog::create(['user_id' => $managerUser->id, 'module' => 'job_cards', 'action' => 'created', 'description' => 'Registered Job Card JC-0001 for Gabriella White.', 'ip_address' => '127.0.0.1']);
    }
}
