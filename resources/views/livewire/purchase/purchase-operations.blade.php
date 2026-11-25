<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">
                @if ($mode === 'payments') Supplier Payments
                @elseif ($mode === 'balances') Vendor Outstanding Balances
                @elseif ($mode === 'returns') Purchase Returns
                @else Purchase Reports &amp; Summary @endif
            </h1>
            <p class="text-sm text-default mb-0">Vendor payment disbursements, accounts payable ledger, purchase returns, and cost reports.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <a href="{{ route('purchase.payments') }}" class="btn-sm {{ $mode === 'payments' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Payments</a>
            <a href="{{ route('purchase.balances') }}" class="btn-sm {{ $mode === 'balances' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Payables</a>
            <a href="{{ route('purchase.returns') }}" class="btn-sm {{ $mode === 'returns' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Returns</a>
            <a href="{{ route('purchase.reports') }}" class="btn-sm {{ $mode === 'reports' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Reports</a>
        </div>
    </div>

    @if ($mode === 'payments')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Record Supplier Payment</h2>
            <form wire:submit="recordPayment" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Open Purchase Bill (Optional)</label>
                    <select wire:model.live="purchase_invoice_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">General Vendor Advance / Payment</option>
                        @foreach($openBills as $b)<option value="{{ $b->id }}">{{ $b->bill_no }} - {{ $b->supplier?->name }} (Due: {{ format_currency($b->balance_due) }})</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Vendor *</label>
                    <select wire:model="supplier_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Select Vendor</option>
                        @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                    @error('supplier_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
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
                    <label class="text-xs font-semibold block mb-1">Pay From Account</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Account</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference</label><input type="text" wire:model="reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <button type="submit" class="w-full btn-sm bg-dark text-white hover:bg-primary-hover">Record Payment &amp; Post to Ledger</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Supplier Payments History</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Payment #</th>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Vendor</th>
                            <th class="text-left py-2 px-3">Bill</th>
                            <th class="text-left py-2 px-3">Method</th>
                            <th class="text-right py-2 px-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $p)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">{{ $p->payment_no }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ format_date($p->payment_date) }}</td>
                                <td class="py-2 px-3 font-medium text-title">{{ $p->supplier?->name }}</td>
                                <td class="py-2 px-3 text-xs">{{ $p->purchaseInvoice?->bill_no ?? 'On Account' }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $p->paymentMethod?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-right font-semibold text-danger">{{ format_currency($p->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No supplier payments recorded yet.</td></tr>
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
                        <th class="text-left py-2.5 px-3">Vendor / Supplier</th>
                        <th class="text-left py-2.5 px-3">Contact</th>
                        <th class="text-right py-2.5 px-3">Opening Bal</th>
                        <th class="text-right py-2.5 px-3">Outstanding Payable</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalPayable = 0; @endphp
                    @foreach ($suppliers as $s)
                        @php $bal = $s->outstanding_balance; $totalPayable += $bal; @endphp
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 text-xs font-semibold text-primary">{{ $s->code }}</td>
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $s->name }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $s->phone ?: $s->email ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-right text-default">{{ format_currency($s->opening_balance) }}</td>
                            <td class="py-2.5 px-3 text-right font-bold {{ $bal > 0 ? 'text-danger' : 'text-success' }}">{{ format_currency($bal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-light font-bold">
                        <td colspan="4" class="py-2.5 px-3 text-right">Total Accounts Payable:</td>
                        <td class="py-2.5 px-3 text-right text-danger">{{ format_currency($totalPayable) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @elseif ($mode === 'returns')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Record Purchase Return</h2>
            <form wire:submit="recordPurchaseReturn" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Vendor *</label>
                    <select wire:model="supplier_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Select Vendor</option>
                        @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Product Returned *</label>
                    <select wire:model.live="return_product_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Select Product</option>
                        @foreach($products as $pr)<option value="{{ $pr->id }}">{{ $pr->name }} (Stock: {{ $pr->stock_quantity }})</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-xs font-semibold block mb-1">Qty *</label><input type="number" step="0.01" wire:model="return_qty" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Unit Cost *</label><input type="number" step="0.01" wire:model="return_cost" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reason</label><input type="text" wire:model="return_reason" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <button type="submit" class="w-full btn-sm bg-dark text-white hover:bg-primary-hover">Record Purchase Return</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Purchase Return Register</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Return #</th>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Vendor</th>
                            <th class="text-left py-2 px-3">Reason</th>
                            <th class="text-right py-2 px-3">Credit Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $r)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">{{ $r->return_no }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ format_date($r->return_date) }}</td>
                                <td class="py-2 px-3 font-medium text-title">{{ $r->supplier?->name }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $r->reason }}</td>
                                <td class="py-2 px-3 text-right font-semibold text-title">{{ format_currency($r->total_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-default">No purchase returns recorded.</td></tr>
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
                <button type="button" wire:click="exportPurchaseReportCsv" class="btn-sm bg-dark text-white inline-flex items-center gap-1"><i class="icon-download"></i> Export CSV</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Purchases</p><h3 class="text-xl font-bold text-gray-900 mb-0">{{ format_currency($reportBills->sum('total_amount')) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Input Tax</p><h3 class="text-xl font-bold text-info mb-0">{{ format_currency($reportBills->sum('tax_amount')) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Paid to Vendors</p><h3 class="text-xl font-bold text-success mb-0">{{ format_currency($reportBills->sum('paid_amount')) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Pending Payables</p><h3 class="text-xl font-bold text-danger mb-0">{{ format_currency($reportBills->sum('balance_due')) }}</h3></div>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Purchase Register ({{ $fromDate }} to {{ $toDate }})</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Bill #</th>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Vendor</th>
                            <th class="text-right py-2 px-3">Total</th>
                            <th class="text-right py-2 px-3">Paid</th>
                            <th class="text-right py-2 px-3">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportBills as $b)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">{{ $b->bill_no }}</td>
                                <td class="py-2 px-3 text-xs">{{ format_date($b->invoice_date) }}</td>
                                <td class="py-2 px-3">{{ $b->supplier?->name }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ format_currency($b->total_amount) }}</td>
                                <td class="py-2 px-3 text-right text-success">{{ format_currency($b->paid_amount) }}</td>
                                <td class="py-2 px-3 text-right text-danger">{{ format_currency($b->balance_due) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No purchase bills in selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
