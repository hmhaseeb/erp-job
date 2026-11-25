<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">
                @if ($mode === 'payments') Customer Payment Collection
                @elseif ($mode === 'balances') Customer Outstanding Balances
                @elseif ($mode === 'returns') Sales Returns &amp; Refunds
                @else Sales Analytics &amp; Reports @endif
            </h1>
            <p class="text-sm text-default mb-0">Integrated sales collections, receivable balances, inventory returns, and revenue summaries.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <a href="{{ route('sales.payments') }}" class="btn-sm {{ $mode === 'payments' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Payments</a>
            <a href="{{ route('sales.balances') }}" class="btn-sm {{ $mode === 'balances' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Balances</a>
            <a href="{{ route('sales.returns') }}" class="btn-sm {{ $mode === 'returns' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Returns</a>
            <a href="{{ route('sales.reports') }}" class="btn-sm {{ $mode === 'reports' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Reports</a>
        </div>
    </div>

    @if ($mode === 'payments')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Record Payment Collection</h2>
            <form wire:submit="collectPayment" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Open Invoice (Optional)</label>
                    <select wire:model.live="sales_invoice_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">General Customer Receipt</option>
                        @foreach($openInvoices as $inv)<option value="{{ $inv->id }}">{{ $inv->invoice_no }} - {{ $inv->customer?->name }} (Due: {{ format_currency($inv->balance_due) }})</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Customer *</label>
                    <select wire:model="customer_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    @error('customer_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('amount')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                    <div><label class="text-xs font-semibold block mb-1">Date *</label><input type="date" wire:model="date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Payment Method</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Bank Account (optional)</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference</label><input type="text" wire:model="reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <button type="submit" class="w-full btn-sm bg-dark text-white hover:bg-primary-hover">Collect Payment &amp; Post to Ledger</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Customer Receipts History</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Receipt #</th>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Customer</th>
                            <th class="text-left py-2 px-3">Invoice</th>
                            <th class="text-left py-2 px-3">Method</th>
                            <th class="text-right py-2 px-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $p)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">{{ $p->receipt_no }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ format_date($p->payment_date) }}</td>
                                <td class="py-2 px-3 font-medium text-title">{{ $p->customer?->name }}</td>
                                <td class="py-2 px-3 text-xs">{{ $p->salesInvoice?->invoice_no ?? 'On Account' }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $p->paymentMethod?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-right font-semibold text-success">{{ format_currency($p->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No customer payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $payments->links() }}</div>
        </div>
    </div>

    @elseif ($mode === 'balances')
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Code</th>
                        <th class="text-left py-2.5 px-3">Customer</th>
                        <th class="text-left py-2.5 px-3">Phone</th>
                        <th class="text-right py-2.5 px-3">Opening Bal</th>
                        <th class="text-right py-2.5 px-3">Credit Limit</th>
                        <th class="text-right py-2.5 px-3">Outstanding Receivable</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalOutstanding = 0; @endphp
                    @foreach ($customers as $c)
                        @php $bal = $c->outstanding_balance; $totalOutstanding += $bal; @endphp
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 text-xs font-semibold text-primary">{{ $c->code }}</td>
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $c->name }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $c->phone ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-right text-default">{{ format_currency($c->opening_balance) }}</td>
                            <td class="py-2.5 px-3 text-right text-default">{{ format_currency($c->credit_limit) }}</td>
                            <td class="py-2.5 px-3 text-right font-bold {{ $bal > 0 ? 'text-danger' : 'text-success' }}">{{ format_currency($bal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-light font-bold">
                        <td colspan="5" class="py-2.5 px-3 text-right">Total Accounts Receivable:</td>
                        <td class="py-2.5 px-3 text-right text-danger">{{ format_currency($totalOutstanding) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @elseif ($mode === 'returns')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Process Sales Return</h2>
            <form wire:submit="recordSalesReturn" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Customer *</label>
                    <select wire:model="customer_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Product Returned *</label>
                    <select wire:model.live="return_product_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Select Product</option>
                        @foreach($products as $pr)<option value="{{ $pr->id }}">{{ $pr->name }} ({{ $pr->code }})</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-xs font-semibold block mb-1">Qty *</label><input type="number" step="0.01" wire:model="return_qty" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Unit Price *</label><input type="number" step="0.01" wire:model="return_price" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Refund Method</label>
                    <select wire:model="refund_method" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="credit">Customer Credit Note</option>
                        <option value="cash">Cash Refund (Post to Cash Book)</option>
                        <option value="bank">Bank Refund (Post to Bank Book)</option>
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reason</label><input type="text" wire:model="return_reason" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <button type="submit" class="w-full btn-sm bg-dark text-white hover:bg-primary-hover">Record Sales Return</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Sales Return Register</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Return #</th>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Customer</th>
                            <th class="text-left py-2 px-3">Reason</th>
                            <th class="text-left py-2 px-3">Refund</th>
                            <th class="text-right py-2 px-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $r)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">{{ $r->return_no }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ format_date($r->return_date) }}</td>
                                <td class="py-2 px-3 font-medium text-title">{{ $r->customer?->name }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $r->reason }}</td>
                                <td class="py-2 px-3 text-xs capitalize">{{ $r->refund_method }}</td>
                                <td class="py-2 px-3 text-right font-semibold text-title">{{ format_currency($r->total_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No sales returns recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @else
    <div class="space-y-4">
        <div class="bg-white border border-border-color rounded-md p-4 flex flex-wrap items-end justify-between gap-3 no-print">
            <div class="flex flex-wrap items-end gap-3">
                <div><label class="text-xs font-semibold block mb-1">From Date</label><input type="date" wire:model.live="fromDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">To Date</label><input type="date" wire:model.live="toDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1"><i class="ph-duotone ph-printer"></i> Print</button>
                <button type="button" wire:click="exportSalesReportCsv" class="btn-sm bg-dark text-white inline-flex items-center gap-1"><i class="icon-download"></i> Export CSV</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Sales</p><h3 class="text-xl font-bold text-gray-900 mb-0">{{ format_currency($reportInvoices->sum('total_amount')) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Tax Collected</p><h3 class="text-xl font-bold text-info mb-0">{{ format_currency($reportInvoices->sum('tax_amount')) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Collected</p><h3 class="text-xl font-bold text-success mb-0">{{ format_currency($reportInvoices->sum('paid_amount')) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Pending Receivables</p><h3 class="text-xl font-bold text-danger mb-0">{{ format_currency($reportInvoices->sum('balance_due')) }}</h3></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Sales Register ({{ $fromDate }} to {{ $toDate }})</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-3">Invoice #</th>
                                <th class="text-left py-2 px-3">Date</th>
                                <th class="text-left py-2 px-3">Customer</th>
                                <th class="text-right py-2 px-3">Total</th>
                                <th class="text-right py-2 px-3">Paid</th>
                                <th class="text-right py-2 px-3">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportInvoices as $inv)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-3 font-semibold text-primary">{{ $inv->invoice_no }}</td>
                                    <td class="py-2 px-3 text-xs">{{ format_date($inv->invoice_date) }}</td>
                                    <td class="py-2 px-3">{{ $inv->customer?->name }}</td>
                                    <td class="py-2 px-3 text-right font-semibold">{{ format_currency($inv->total_amount) }}</td>
                                    <td class="py-2 px-3 text-right text-success">{{ format_currency($inv->paid_amount) }}</td>
                                    <td class="py-2 px-3 text-right text-danger">{{ format_currency($inv->balance_due) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-default">No invoices in selected period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Top Selling Items</h2>
                <div class="space-y-2">
                    @forelse ($topProducts as $tp)
                        <div class="flex items-center justify-between border-b border-border-color pb-2 last:border-0">
                            <div><div class="font-semibold text-title text-sm">{{ $tp->description }}</div><div class="text-xs text-default">Qty Sold: {{ $tp->qty_sold }}</div></div>
                            <div class="font-bold text-primary text-sm">{{ format_currency($tp->total_sales) }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-default">No sales item data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
