<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">
                @switch($mode)
                    @case('adjustments') Stock Adjustments @break
                    @case('transfers') Stock Transfers @break
                    @case('history') Stock History Ledger @break
                    @case('low-stock') Low Stock Alerts @break
                    @case('reports') Inventory Reports @break
                    @default Stock Management (Stock In &amp; Out)
                @endswitch
            </h1>
            <p class="text-sm text-default mb-0">Real-time inventory stock operations, adjustments, movement history &amp; valuation reports.</p>
        </div>
        <div class="flex flex-wrap gap-1.5 no-print">
            @foreach ([
                'inventory.stock' => 'Stock In/Out',
                'inventory.stock-adjustments' => 'Adjustments',
                'inventory.stock-history' => 'Stock History',
                'inventory.low-stock' => 'Low Stock Alerts',
                'inventory.reports' => 'Reports',
            ] as $route => $label)
                <a href="{{ route($route) }}" class="btn-sm {{ request()->routeIs($route) ? 'bg-dark text-white' : 'bg-white border border-border-color text-title hover:bg-light' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if ($mode === 'stock')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-4 bg-white border border-border-color rounded-md p-5 space-y-3">
            <h2 class="text-base font-bold text-title mb-0">Record Stock In / Stock Out</h2>
            <div>
                <label class="text-xs font-semibold block mb-1">Operation Type</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="$set('stock_action', 'in')" class="btn-sm {{ $stock_action === 'in' ? 'bg-success text-white' : 'bg-light border border-border-color text-title' }}">Stock In (+)</button>
                    <button type="button" wire:click="$set('stock_action', 'out')" class="btn-sm {{ $stock_action === 'out' ? 'bg-danger text-white' : 'bg-light border border-border-color text-title' }}">Stock Out (-)</button>
                </div>
            </div>
            <div><label class="text-xs font-semibold block mb-1">Product *</label><select wire:model.live="product_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select product...</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Qty: {{ $p->stock_quantity }})</option>@endforeach</select>@error('product_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-xs font-semibold block mb-1">Quantity *</label><input type="number" step="0.01" wire:model="quantity" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Unit Cost</label><input type="number" step="0.01" wire:model="unit_cost" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            </div>
            <div><label class="text-xs font-semibold block mb-1">Reference No</label><input type="text" wire:model="reference_no" placeholder="Optional Ref" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <div><label class="text-xs font-semibold block mb-1">Notes</label><input type="text" wire:model="notes" placeholder="Reason or notes" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <button type="button" wire:click="recordStockInOut" class="btn-sm w-full bg-dark text-white hover:bg-primary-hover">Submit Stock {{ strtoupper($stock_action) }}</button>
        </div>

        <div class="lg:col-span-8 bg-white border border-border-color rounded-md p-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-base font-bold text-title mb-0">Current Stock Balances</h2>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Filter product..." class="px-3 py-1.5 text-sm border border-border-color rounded-md w-56">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">SKU</th><th class="text-left py-2 px-3">Product</th><th class="text-left py-2 px-3">Category</th><th class="text-right py-2 px-3">Qty On Hand</th><th class="text-left py-2 px-3">Status</th></tr></thead>
                    <tbody>
                        @foreach ($productBalances as $pb)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 text-xs text-primary font-semibold">{{ $pb->sku }}</td>
                                <td class="py-2 px-3"><a href="{{ route('inventory.products.show', $pb->id) }}" class="font-medium text-title hover:text-primary hover:underline">{{ $pb->name }}</a></td>
                                <td class="py-2 px-3 text-xs text-default">{{ $pb->category?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-right font-bold text-title">{{ $pb->stock_quantity }} <span class="text-xs text-default font-normal">{{ $pb->unit?->short_name }}</span></td>
                                <td class="py-2 px-3"><span class="text-[11px] px-2 py-0.5 rounded {{ $pb->stock_status === 'In Stock' ? 'bg-success-transparent text-success' : ($pb->stock_status === 'Low Stock' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">{{ $pb->stock_status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $productBalances->links() }}</div>
        </div>
    </div>
    @endif

    @if ($mode === 'adjustments')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-4 bg-white border border-border-color rounded-md p-5 space-y-3">
            <h2 class="text-base font-bold text-title mb-0">New Stock Adjustment</h2>
            <div><label class="text-xs font-semibold block mb-1">Product *</label><select wire:model="product_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select...</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->stock_quantity }})</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-xs font-semibold block mb-1">Adjustment</label><select wire:model="adj_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="increase">Increase (+)</option><option value="decrease">Decrease (-)</option></select></div>
                <div><label class="text-xs font-semibold block mb-1">Quantity *</label><input type="number" step="0.01" wire:model="quantity" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            </div>
            <div><label class="text-xs font-semibold block mb-1">Reason *</label><input type="text" wire:model="adj_reason" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <div><label class="text-xs font-semibold block mb-1">Date</label><input type="date" wire:model="adj_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <button type="button" wire:click="recordAdjustment" class="btn-sm w-full bg-dark text-white">Save Adjustment</button>
        </div>

        <div class="lg:col-span-8 bg-white border border-border-color rounded-md p-4 overflow-x-auto">
            <h2 class="text-base font-bold text-title mb-3">Adjustment History</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Adj #</th><th class="text-left py-2 px-3">Date</th><th class="text-left py-2 px-3">Product</th><th class="text-right py-2 px-3">Qty</th><th class="text-left py-2 px-3">Reason</th></tr></thead>
                <tbody>
                    @forelse ($adjustments as $adj)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-3 font-semibold text-primary">{{ $adj->adjustment_no }}</td>
                            <td class="py-2 px-3 text-xs">{{ format_date($adj->adjustment_date) }}</td>
                            <td class="py-2 px-3 font-medium text-title">{{ $adj->product?->name }}</td>
                            <td class="py-2 px-3 text-right font-semibold {{ $adj->adjustment_type === 'increase' ? 'text-success' : 'text-danger' }}">{{ $adj->adjustment_type === 'increase' ? '+' : '-' }}{{ $adj->quantity }}</td>
                            <td class="py-2 px-3 text-xs text-default">{{ $adj->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-default">No stock adjustments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if ($mode === 'history')
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search product or SKU..." class="px-3 py-1.5 text-sm border border-border-color rounded-md w-64">
            <select wire:model.live="movementTypeFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                <option value="">All Movement Types</option>
                @foreach (['in', 'out', 'adjustment', 'sale', 'sales_return', 'purchase', 'purchase_return', 'job_card_use'] as $mt)
                    <option value="{{ $mt }}">{{ ucwords(str_replace('_', ' ', $mt)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Timestamp</th><th class="text-left py-2 px-3">Product</th><th class="text-left py-2 px-3">Type</th><th class="text-right py-2 px-3">Change</th><th class="text-right py-2 px-3">Balance</th><th class="text-left py-2 px-3">Ref / Notes</th></tr></thead>
                <tbody>
                    @foreach ($movements as $mv)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-3 text-xs text-default">{{ format_date($mv->created_at, true) }}</td>
                            <td class="py-2 px-3 font-medium text-title">{{ $mv->product?->name }}</td>
                            <td class="py-2 px-3"><span class="text-[11px] bg-light border border-border-color px-2 py-0.5 rounded">{{ ucwords(str_replace('_', ' ', $mv->movement_type)) }}</span></td>
                            <td class="py-2 px-3 text-right font-bold {{ $mv->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $mv->quantity_change >= 0 ? '+' : '' }}{{ $mv->quantity_change }}</td>
                            <td class="py-2 px-3 text-right font-semibold">{{ $mv->balance_after }}</td>
                            <td class="py-2 px-3 text-xs text-default">{{ $mv->reference_no }} — {{ $mv->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $movements->links() }}</div>
    </div>
    @endif

    @if ($mode === 'low-stock')
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold text-danger mb-0">Products At or Below Reorder Level ({{ $lowStockProducts->count() }})</h2>
            <a href="{{ route('purchase.orders') }}" class="btn-sm bg-dark text-white">Create Purchase Order</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">SKU</th><th class="text-left py-2 px-3">Product</th><th class="text-left py-2 px-3">Category</th><th class="text-left py-2 px-3">Supplier</th><th class="text-right py-2 px-3">On Hand</th><th class="text-right py-2 px-3">Reorder Level</th><th class="text-left py-2 px-3">Alert</th></tr></thead>
                <tbody>
                    @forelse ($lowStockProducts as $lp)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary">{{ $lp->sku }}</td>
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $lp->name }}</td>
                            <td class="py-2.5 px-3 text-default">{{ $lp->category?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-default">{{ $lp->defaultSupplier?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-bold text-danger">{{ $lp->stock_quantity }}</td>
                            <td class="py-2.5 px-3 text-right text-default">{{ $lp->reorder_level }}</td>
                            <td class="py-2.5 px-3"><span class="text-[11px] px-2 py-0.5 rounded {{ $lp->stock_quantity <= 0 ? 'bg-danger-transparent text-danger' : 'bg-warning-transparent text-warning' }}">{{ $lp->stock_status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-success">All products are sufficiently stocked!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if ($mode === 'reports')
    <div class="space-y-4">
        <div class="flex justify-end gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title">Print Report</button>
            <button type="button" wire:click="exportInventoryReportCsv" class="btn-sm bg-dark text-white">Export Valuation CSV</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Cost Valuation</p><h3 class="text-xl font-bold text-title mb-0">{{ format_currency($totalCostValue) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Retail Valuation</p><h3 class="text-xl font-bold text-primary mb-0">{{ format_currency($totalRetailValue) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Potential Gross Margin</p><h3 class="text-xl font-bold text-success mb-0">{{ format_currency($totalRetailValue - $totalCostValue) }}</h3></div>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4 overflow-x-auto">
            <h2 class="text-base font-bold text-title mb-3">Stock Valuation by Product</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">SKU</th><th class="text-left py-2 px-3">Product</th><th class="text-right py-2 px-3">Qty</th><th class="text-right py-2 px-3">Cost Price</th><th class="text-right py-2 px-3">Cost Value</th><th class="text-right py-2 px-3">Retail Value</th></tr></thead>
                <tbody>
                    @foreach ($products as $p)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-3 text-xs text-primary font-semibold">{{ $p->sku }}</td>
                            <td class="py-2 px-3 font-medium text-title">{{ $p->name }}</td>
                            <td class="py-2 px-3 text-right">{{ $p->stock_quantity }}</td>
                            <td class="py-2 px-3 text-right">{{ format_currency($p->cost_price) }}</td>
                            <td class="py-2 px-3 text-right font-semibold">{{ format_currency($p->stock_quantity * $p->cost_price) }}</td>
                            <td class="py-2 px-3 text-right font-semibold text-primary">{{ format_currency($p->stock_quantity * $p->selling_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
