<div>
    <!-- Greeting Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl max-lg:text-lg font-bold mb-1">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-default mb-0">Integrated view across HRM, Inventory, Sales, Purchase, Finance &amp; Service Job Cards.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            @canModule('sales', 'create')
            <a href="{{ route('sales.invoices') }}" class="btn-sm bg-white border border-border-color text-gray-900 inline-flex items-center gap-1.5 hover:bg-light">
                <i class="ph-duotone ph-receipt"></i> New Invoice
            </a>
            @endcanModule
            @canModule('purchase', 'create')
            <a href="{{ route('purchase.orders') }}" class="btn-sm bg-white border border-border-color text-gray-900 inline-flex items-center gap-1.5 hover:bg-light">
                <i class="ph-duotone ph-clipboard-text"></i> New PO
            </a>
            @endcanModule
            @canModule('job_cards', 'create')
            <a href="{{ route('job-cards.index') }}" class="btn-sm bg-dark text-white border border-dark inline-flex items-center gap-1.5 hover:bg-primary-hover">
                <i class="ph-duotone ph-plus-circle"></i> New Job Card
            </a>
            @endcanModule
        </div>
    </div>

    <!-- Primary KPI Strip (Sales, Cash/Bank, Inventory, Service, HRM) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-default">Total Sales Revenue</span>
                <div class="size-9 bg-primary-transparent text-primary rounded-md flex items-center justify-center">
                    <i class="ph-duotone ph-trend-up text-lg"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ format_currency($totalSalesRevenue) }}</h2>
            <p class="text-xs text-default mb-0">Receivable: <span class="text-warning font-semibold">{{ format_currency($receivables) }}</span></p>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-default">Cash &amp; Bank Balance</span>
                <div class="size-9 bg-success-transparent text-success rounded-md flex items-center justify-center">
                    <i class="ph-duotone ph-bank text-lg"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ format_currency($cashBalance + $bankBalance) }}</h2>
            <p class="text-xs text-default mb-0">Cash: {{ format_currency($cashBalance) }} · Bank: {{ format_currency($bankBalance) }}</p>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-default">Inventory Value ({{ $totalProducts }} SKUs)</span>
                <div class="size-9 bg-warning-transparent text-warning rounded-md flex items-center justify-center">
                    <i class="ph-duotone ph-package text-lg"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ format_currency($inventoryValue) }}</h2>
            <p class="text-xs text-default mb-0">
                <a href="{{ route('inventory.low-stock') }}" class="{{ $lowStockCount > 0 ? 'text-danger font-semibold' : 'text-success' }} hover:underline">
                    {{ $lowStockCount }} Low / Out of Stock alert(s)
                </a>
            </p>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-default">Active Service Job Cards</span>
                <div class="size-9 bg-info-transparent text-info rounded-md flex items-center justify-center">
                    <i class="ph-duotone ph-wrench text-lg"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ $openJobCards }}</h2>
            <p class="text-xs text-default mb-0">{{ $pendingQcJobs }} awaiting Quality Check · Payables: {{ format_currency($payables) }}</p>
        </div>
    </div>

    <!-- Workforce & Financial Analytics Row -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-3 mb-4">
        <!-- Financial Chart -->
        <div class="bg-white border border-border-color rounded-md p-4 xl:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-base font-bold text-title mb-0">Revenue vs Outflows (Last 6 Months)</h3>
                    <p class="text-xs text-default mb-0">Live aggregation from Sales Invoices, Purchase Bills &amp; Operating Expenses</p>
                </div>
                <a href="{{ route('finance.reports') }}" class="btn-sm bg-white border border-border-color text-title hover:bg-light">Financial Reports</a>
            </div>
            <div id="erp-revenue-chart" class="min-h-[260px]"></div>
        </div>

        <!-- HRM Snapshot -->
        <div class="bg-white border border-border-color rounded-md p-4 xl:col-span-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-bold text-title mb-0">Workforce Overview</h3>
                    <a href="{{ route('hrm.employees') }}" class="text-xs text-primary font-medium hover:underline">View All</a>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="border border-border-color bg-light rounded-md p-3">
                        <p class="text-xs text-default mb-1">Active Staff</p>
                        <h4 class="text-xl font-bold text-gray-900 mb-0">{{ $totalEmployees }}</h4>
                    </div>
                    <div class="border border-border-color bg-light rounded-md p-3">
                        <p class="text-xs text-default mb-1">Attendance Today</p>
                        <h4 class="text-xl font-bold text-gray-900 mb-0">{{ $attendanceRate }}%</h4>
                    </div>
                    <div class="border border-border-color bg-light rounded-md p-3">
                        <p class="text-xs text-default mb-1">On Leave Today</p>
                        <h4 class="text-xl font-bold text-gray-900 mb-0">{{ $onLeaveToday }}</h4>
                    </div>
                    <div class="border border-border-color bg-light rounded-md p-3">
                        <p class="text-xs text-default mb-1">Last Payroll</p>
                        <h4 class="text-base font-bold text-gray-900 mb-0">{{ format_currency($latestPayroll?->total_net ?? 0) }}</h4>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach ($departments as $dept)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-default">{{ $dept->name }}</span>
                            <span class="font-semibold text-title">{{ $dept->employees_count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="bg-primary-gradient rounded-md p-3.5 mt-4 flex items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-bold text-white mb-0">Payroll &amp; Attendance</p>
                    <p class="text-xs text-white/80 mb-0">Manage monthly salary runs</p>
                </div>
                <a href="{{ route('hrm.payroll') }}" class="btn-sm bg-white text-dark hover:bg-light shrink-0">Run Payroll</a>
            </div>
        </div>
    </div>

    <!-- Operational Tables Row -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-3">
        <!-- Recent Sales Invoices -->
        <div class="bg-white border border-border-color rounded-md p-4 xl:col-span-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-title mb-0">Recent Sales &amp; Service Invoices</h3>
                <a href="{{ route('sales.invoices') }}" class="text-xs text-primary hover:underline">Manage Invoices</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-default border-b border-border-color">
                            <th class="text-left py-2 px-2">Invoice</th>
                            <th class="text-left py-2 px-2">Customer</th>
                            <th class="text-right py-2 px-2">Total</th>
                            <th class="text-right py-2 px-2">Balance</th>
                            <th class="text-left py-2 px-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentInvoices as $inv)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-2 text-xs font-semibold text-primary">
                                    <a href="{{ route('sales.invoices.print', $inv->id) }}">{{ $inv->invoice_no }}</a>
                                </td>
                                <td class="py-2.5 px-2 text-xs text-title">{{ $inv->customer?->name }}</td>
                                <td class="py-2.5 px-2 text-xs text-right font-medium">{{ format_currency($inv->total_amount) }}</td>
                                <td class="py-2.5 px-2 text-xs text-right">{{ format_currency($inv->balance_due) }}</td>
                                <td class="py-2.5 px-2">
                                    <span class="text-[11px] px-2 py-0.5 rounded {{ $inv->status === 'paid' ? 'bg-success-transparent text-success' : ($inv->status === 'partial' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-xs text-default">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Active Job Cards & Low Stock -->
        <div class="bg-white border border-border-color rounded-md p-4 xl:col-span-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-title mb-0">Active Service Job Cards</h3>
                <a href="{{ route('job-cards.index') }}" class="text-xs text-primary hover:underline">All Job Cards</a>
            </div>
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-default border-b border-border-color">
                            <th class="text-left py-2 px-2">Job #</th>
                            <th class="text-left py-2 px-2">Customer / Item</th>
                            <th class="text-left py-2 px-2">Technician</th>
                            <th class="text-left py-2 px-2">Progress</th>
                            <th class="text-left py-2 px-2">Stage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentJobCards as $jc)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-2 text-xs font-semibold text-primary">
                                    <a href="{{ route('job-cards.show', $jc->id) }}">{{ $jc->job_card_no }}</a>
                                </td>
                                <td class="py-2 px-2 text-xs">
                                    <div class="font-semibold text-title">{{ $jc->customer?->name }}</div>
                                    <div class="text-default">{{ $jc->item_name }}</div>
                                </td>
                                <td class="py-2 px-2 text-xs text-default">{{ $jc->technician?->full_name ?? 'Unassigned' }}</td>
                                <td class="py-2 px-2 text-xs w-28">
                                    <div class="h-1.5 bg-light rounded-full overflow-hidden">
                                        <div class="h-full bg-primary rounded-full" style="width: {{ $jc->progress_percent }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-default">{{ $jc->progress_percent }}%</span>
                                </td>
                                <td class="py-2 px-2">
                                    <span class="text-[11px] bg-info-transparent text-info px-2 py-0.5 rounded">{{ ucwords(str_replace('_', ' ', $jc->status)) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-xs text-default">No job cards recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lowStockItems->isNotEmpty())
                <div class="border-t border-border-color pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-danger flex items-center gap-1"><i class="ph-duotone ph-warning"></i> Low Stock Alerts</span>
                        <a href="{{ route('inventory.low-stock') }}" class="text-xs text-primary hover:underline">Restock</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($lowStockItems as $ls)
                            <span class="text-xs border border-warning/40 bg-warning-transparent text-warning px-2.5 py-1 rounded">
                                {{ $ls->name }}: <strong>{{ $ls->stock_quantity }}</strong> left
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function renderDashboardChart() {
        const el = document.getElementById('erp-revenue-chart');
        if (!el || typeof ApexCharts === 'undefined') return;
        el.innerHTML = '';
        new ApexCharts(el, {
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            series: [
                { name: 'Sales Revenue', data: @json($revenueSeries) },
                { name: 'Purchases & Expenses', data: @json($expenseSeries) }
            ],
            xaxis: { categories: @json($chartMonths) },
            colors: ['#0f766e', '#d97706'],
            plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4, borderColor: '#e8e9ec' }
        }).render();
    }
    document.addEventListener('DOMContentLoaded', renderDashboardChart);
    document.addEventListener('livewire:navigated', renderDashboardChart);
</script>
@endpush
