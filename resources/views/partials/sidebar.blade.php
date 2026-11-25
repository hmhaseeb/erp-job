<!-- Sidenav Menu Start -->
<aside class="sidebar no-print" id="sidebar">
    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div class="flex items-center">
            <a href="{{ route('dashboard') }}" class="logo logo-normal">
                <img src="{{ erp_logo('light') }}" alt="Logo" class="max-h-9">
            </a>
            <a href="{{ route('dashboard') }}" class="logo-small">
                <img src="{{ erp_logo('small') }}" alt="Logo" class="max-h-8">
            </a>
            <a href="{{ route('dashboard') }}" class="dark-logo">
                <img src="{{ erp_logo('dark') }}" alt="Logo" class="max-h-9">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn" aria-label="toggle">
            <i class="icon icon-panel-right-open align-middle"></i>
        </button>
        <button type="button" class="sidebar-close" aria-label="close">
            <i class="icon icon-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul role="menu" aria-label="Main navigation menu">
                <li class="menu-title" aria-disabled="true"><span>Main</span></li>
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ph-duotone ph-squares-four"></i><span>Dashboard</span>
                    </a>
                </li>

                @canModule('hrm', 'view')
                <li class="menu-title" aria-disabled="true"><span>HRM</span></li>
                <li class="submenu">
                    <a href="#" class="{{ request()->routeIs('hrm.*') ? 'active subdrop' : '' }}">
                        <i class="ph-duotone ph-users-three"></i><span>Human Resources</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ request()->routeIs('hrm.*') ? 'display:block' : '' }}">
                        <li><a href="{{ route('hrm.employees') }}" class="{{ request()->routeIs('hrm.employees*') ? 'active' : '' }}">Employees Directory</a></li>
                        <li><a href="{{ route('hrm.lifecycle') }}" class="{{ request()->routeIs('hrm.lifecycle') ? 'active' : '' }}">Employee Lifecycle</a></li>
                        <li><a href="{{ route('hrm.departments') }}" class="{{ request()->routeIs('hrm.departments') ? 'active' : '' }}">Departments & Designations</a></li>
                        <li><a href="{{ route('hrm.attendance') }}" class="{{ request()->routeIs('hrm.attendance') ? 'active' : '' }}">Attendance</a></li>
                        <li><a href="{{ route('hrm.leaves') }}" class="{{ request()->routeIs('hrm.leaves') ? 'active' : '' }}">Leave Management</a></li>
                        <li><a href="{{ route('hrm.payroll') }}" class="{{ request()->routeIs('hrm.payroll*') ? 'active' : '' }}">Payroll & Salary</a></li>
                    </ul>
                </li>
                @endcanModule

                @canModule('inventory', 'view')
                <li class="menu-title" aria-disabled="true"><span>Inventory</span></li>
                <li>
                    <a href="{{ route('inventory.products') }}" class="{{ request()->routeIs('inventory.products') ? 'active' : '' }}">
                        <i class="ph-duotone ph-cube"></i><span>Products</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inventory.categories') }}" class="{{ request()->routeIs('inventory.categories') ? 'active' : '' }}">
                        <i class="ph-duotone ph-folders"></i><span>Categories & Units</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inventory.suppliers') }}" class="{{ request()->routeIs('inventory.suppliers') ? 'active' : '' }}">
                        <i class="ph-duotone ph-truck"></i><span>Suppliers</span>
                    </a>
                </li>
                <li class="submenu">
                    <a href="#" class="{{ request()->routeIs('inventory.stock*', 'inventory.low-stock', 'inventory.reports') ? 'active subdrop' : '' }}">
                        <i class="ph-duotone ph-arrows-left-right"></i><span>Stock &amp; Reports</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ request()->routeIs('inventory.stock*', 'inventory.low-stock', 'inventory.reports') ? 'display:block' : '' }}">
                        <li><a href="{{ route('inventory.stock') }}" class="{{ request()->routeIs('inventory.stock') ? 'active' : '' }}">Stock In / Out</a></li>
                        <li><a href="{{ route('inventory.stock-adjustments') }}" class="{{ request()->routeIs('inventory.stock-adjustments') ? 'active' : '' }}">Stock Adjustments</a></li>
                        <li><a href="{{ route('inventory.stock-history') }}" class="{{ request()->routeIs('inventory.stock-history') ? 'active' : '' }}">Stock History</a></li>
                        <li><a href="{{ route('inventory.low-stock') }}" class="{{ request()->routeIs('inventory.low-stock') ? 'active' : '' }}">Low Stock Alerts</a></li>
                        <li><a href="{{ route('inventory.reports') }}" class="{{ request()->routeIs('inventory.reports') ? 'active' : '' }}">Inventory Reports</a></li>
                    </ul>
                </li>
                @endcanModule

                @canModule('sales', 'view')
                <li class="menu-title" aria-disabled="true"><span>Sales</span></li>
                <li>
                    <a href="{{ route('sales.customers') }}" class="{{ request()->routeIs('sales.customers') ? 'active' : '' }}">
                        <i class="ph-duotone ph-address-book"></i><span>Customers</span>
                    </a>
                </li>
                <li class="submenu">
                    <a href="#" class="{{ request()->routeIs('sales.orders', 'sales.invoices*', 'sales.payments', 'sales.balances', 'sales.returns', 'sales.reports') ? 'active subdrop' : '' }}">
                        <i class="ph-duotone ph-shopping-cart"></i><span>Sales & Billing</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ request()->routeIs('sales.orders', 'sales.invoices*', 'sales.payments', 'sales.balances', 'sales.returns', 'sales.reports') ? 'display:block' : '' }}">
                        <li><a href="{{ route('sales.orders') }}" class="{{ request()->routeIs('sales.orders') ? 'active' : '' }}">Sales Orders</a></li>
                        <li><a href="{{ route('sales.invoices') }}" class="{{ request()->routeIs('sales.invoices*') ? 'active' : '' }}">Sales Invoices / Bills</a></li>
                        <li><a href="{{ route('sales.payments') }}" class="{{ request()->routeIs('sales.payments') ? 'active' : '' }}">Payment Collection</a></li>
                        <li><a href="{{ route('sales.balances') }}" class="{{ request()->routeIs('sales.balances') ? 'active' : '' }}">Customer Balances</a></li>
                        <li><a href="{{ route('sales.returns') }}" class="{{ request()->routeIs('sales.returns') ? 'active' : '' }}">Sales Returns</a></li>
                        <li><a href="{{ route('sales.reports') }}" class="{{ request()->routeIs('sales.reports') ? 'active' : '' }}">Sales Reports</a></li>
                    </ul>
                </li>
                @endcanModule

                @canModule('purchase', 'view')
                <li class="menu-title" aria-disabled="true"><span>Purchase</span></li>
                <li class="submenu">
                    <a href="#" class="{{ request()->routeIs('purchase.*') ? 'active subdrop' : '' }}">
                        <i class="ph-duotone ph-clipboard-text"></i><span>Procurement</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ request()->routeIs('purchase.*') ? 'display:block' : '' }}">
                        <li><a href="{{ route('purchase.vendors') }}" class="{{ request()->routeIs('purchase.vendors') ? 'active' : '' }}">Vendors</a></li>
                        <li><a href="{{ route('purchase.orders') }}" class="{{ request()->routeIs('purchase.orders') ? 'active' : '' }}">Purchase Orders</a></li>
                        <li><a href="{{ route('purchase.grn') }}" class="{{ request()->routeIs('purchase.grn') ? 'active' : '' }}">Goods Received</a></li>
                        <li><a href="{{ route('purchase.invoices') }}" class="{{ request()->routeIs('purchase.invoices*') ? 'active' : '' }}">Purchase Invoices</a></li>
                        <li><a href="{{ route('purchase.payments') }}" class="{{ request()->routeIs('purchase.payments') ? 'active' : '' }}">Supplier Payments</a></li>
                        <li><a href="{{ route('purchase.returns') }}" class="{{ request()->routeIs('purchase.returns') ? 'active' : '' }}">Purchase Returns</a></li>
                        <li><a href="{{ route('purchase.balances') }}" class="{{ request()->routeIs('purchase.balances') ? 'active' : '' }}">Vendor Balances</a></li>
                        <li><a href="{{ route('purchase.reports') }}" class="{{ request()->routeIs('purchase.reports') ? 'active' : '' }}">Purchase Reports</a></li>
                    </ul>
                </li>
                @endcanModule

                @canModule('finance', 'view')
                <li class="menu-title" aria-disabled="true"><span>Finance & Accounts</span></li>
                <li class="submenu">
                    <a href="#" class="{{ request()->routeIs('finance.*') ? 'active subdrop' : '' }}">
                        <i class="ph-duotone ph-currency-circle-dollar"></i><span>Accounting & Books</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ request()->routeIs('finance.*') ? 'display:block' : '' }}">
                        <li><a href="{{ route('finance.expenses') }}" class="{{ request()->routeIs('finance.expenses') ? 'active' : '' }}">Expenses & Categories</a></li>
                        <li><a href="{{ route('finance.incomes') }}" class="{{ request()->routeIs('finance.incomes') ? 'active' : '' }}">Income Management</a></li>
                        <li><a href="{{ route('finance.daybook') }}" class="{{ request()->routeIs('finance.daybook') ? 'active' : '' }}">Daybook</a></li>
                        <li><a href="{{ route('finance.cashbook') }}" class="{{ request()->routeIs('finance.cashbook') ? 'active' : '' }}">Cash Book</a></li>
                        <li><a href="{{ route('finance.bankbook') }}" class="{{ request()->routeIs('finance.bankbook') ? 'active' : '' }}">Bank Book</a></li>
                        <li><a href="{{ route('finance.transactions') }}" class="{{ request()->routeIs('finance.transactions') ? 'active' : '' }}">Account Transactions</a></li>
                        <li><a href="{{ route('finance.receipts-payments') }}" class="{{ request()->routeIs('finance.receipts-payments') ? 'active' : '' }}">Payments & Receipts</a></li>
                        <li><a href="{{ route('finance.reports') }}" class="{{ request()->routeIs('finance.reports') ? 'active' : '' }}">Financial Reports</a></li>
                    </ul>
                </li>
                @endcanModule

                @canModule('job_cards', 'view')
                <li class="menu-title" aria-disabled="true"><span>Service / Job Card</span></li>
                <li class="submenu">
                    <a href="#" class="{{ request()->routeIs('job-cards.*') ? 'active subdrop' : '' }}">
                        <i class="ph-duotone ph-wrench"></i><span>Job Card Service</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ request()->routeIs('job-cards.*') ? 'display:block' : '' }}">
                        <li><a href="{{ route('job-cards.index') }}" class="{{ request()->routeIs('job-cards.index') ? 'active' : '' }}">All Job Cards</a></li>
                        <li><a href="{{ route('job-cards.board') }}" class="{{ request()->routeIs('job-cards.board') ? 'active' : '' }}">Progress & QC Board</a></li>
                        <li><a href="{{ route('job-cards.reports') }}" class="{{ request()->routeIs('job-cards.reports') ? 'active' : '' }}">Service Reports</a></li>
                    </ul>
                </li>
                @endcanModule

                @if(auth()->user()?->hasPermission('users', 'view') || auth()->user()?->hasPermission('settings', 'view'))
                <li class="menu-title" aria-disabled="true"><span>System & Admin</span></li>
                @endif

                @canModule('users', 'view')
                <li>
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <i class="ph-duotone ph-user-gear"></i><span>User Management</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('users.roles') }}" class="{{ request()->routeIs('users.roles') ? 'active' : '' }}">
                        <i class="ph-duotone ph-shield-check"></i><span>Roles & Permissions</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('users.logs') }}" class="{{ request()->routeIs('users.logs') ? 'active' : '' }}">
                        <i class="ph-duotone ph-clock-counter-clockwise"></i><span>User Activity Logs</span>
                    </a>
                </li>
                @endcanModule

                @canModule('settings', 'view')
                <li>
                    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="ph-duotone ph-gear-six"></i><span>Company Settings</span>
                    </a>
                </li>
                @endcanModule
            </ul>
        </div>
    </div>
</aside>
<!-- Sidenav Menu End -->
