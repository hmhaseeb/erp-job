<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.vendors') }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left"></i></a>
            <div>
                <div class="flex items-center flex-wrap gap-2">
                    <h1 class="text-gray-900 text-xl font-bold mb-0">{{ $supplier->name }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded bg-light border border-border-color font-semibold text-primary">{{ $supplier->code }}</span>
                    <span class="text-xs px-2 py-0.5 rounded capitalize {{ $supplier->status === 'active' ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">{{ $supplier->status }}</span>
                </div>
                <p class="text-sm text-default mb-0">{{ $supplier->company_name ?: 'Vendor / Supplier' }} · Contact: {{ $supplier->contact_person ?: '—' }} · {{ $supplier->phone ?: $supplier->email ?: 'No contact' }}</p>
            </div>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5"><i class="ph-duotone ph-printer"></i> Print Statement</button>
            @canModule('purchase', 'create')
            <button type="button" wire:click="openPayModal()" class="btn-sm bg-primary text-white inline-flex items-center gap-1.5"><i class="ph ph-money"></i> Pay Vendor</button>
            @endcanModule
        </div>
    </div>

    <!-- KPI Row -->
    @php $outstanding = $supplier->outstanding_balance; @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Total Purchase Bills</p>
            <h3 class="text-xl font-bold text-gray-900 mb-0">{{ format_currency($totalBilled) }}</h3>
            <span class="text-[11px] text-default">{{ $bills->count() }} bill(s) · {{ $orders->count() }} PO(s)</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Total Paid to Vendor</p>
            <h3 class="text-xl font-bold text-success mb-0">{{ format_currency($totalPaid) }}</h3>
            <span class="text-[11px] text-default">Purchase Returns: {{ format_currency($totalReturned) }}</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Outstanding Payable Balance</p>
            <h3 class="text-xl font-bold {{ $outstanding > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ format_currency($outstanding) }}</h3>
            <span class="text-[11px] text-default">Opening Balance: {{ format_currency($supplier->opening_balance) }}</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Goods Receipts &amp; Catalog Items</p>
            <h3 class="text-xl font-bold text-info mb-0">{{ $grns->count() }} GRN(s)</h3>
            <span class="text-[11px] text-default">{{ $products->count() }} linked product(s)</span>
        </div>
    </div>

    <!-- Vendor Details Card -->
    <div class="bg-white border border-border-color rounded-md p-4 mb-4">
        <h2 class="text-base font-bold text-title mb-3">Vendor Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div><span class="text-xs text-default block">Vendor / Company</span><strong class="text-title">{{ $supplier->company_name ?: $supplier->name }}</strong></div>
            <div><span class="text-xs text-default block">Primary Contact</span><strong class="text-title">{{ $supplier->contact_person ?: '—' }}</strong></div>
            <div><span class="text-xs text-default block">Tax / VAT Number</span><strong class="text-title">{{ $supplier->tax_number ?: '—' }}</strong></div>
            <div><span class="text-xs text-default block">Phone / Email</span><strong class="text-title">{{ $supplier->phone ?: '—' }} / {{ $supplier->email ?: '—' }}</strong></div>
            <div class="sm:col-span-2 lg:col-span-4"><span class="text-xs text-default block">Address</span><strong class="text-title">{{ collect([$supplier->address, $supplier->city, $supplier->country])->filter()->implode(', ') ?: '—' }}</strong></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap gap-1.5 mb-4 no-print">
            <button type="button" wire:click="$set('activeTab', 'bills')" class="btn-sm {{ $activeTab === 'bills' ? 'bg-dark text-white' : 'bg-light text-title' }}">Purchase Bills ({{ $bills->count() }})</button>
            <button type="button" wire:click="$set('activeTab', 'history')" class="btn-sm {{ $activeTab === 'history' ? 'bg-dark text-white' : 'bg-light text-title' }}">Purchase History (PO / GRN / Returns)</button>
            <button type="button" wire:click="$set('activeTab', 'payments')" class="btn-sm {{ $activeTab === 'payments' ? 'bg-dark text-white' : 'bg-light text-title' }}">Payment History ({{ $payments->count() }})</button>
            <button type="button" wire:click="$set('activeTab', 'products')" class="btn-sm {{ $activeTab === 'products' ? 'bg-dark text-white' : 'bg-light text-title' }}">Supplied Products ({{ $products->count() }})</button>
        </div>

        @if ($activeTab === 'bills')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Bill #</th>
                            <th class="text-left py-2.5 px-3">Vendor Inv / PO</th>
                            <th class="text-left py-2.5 px-3">Bill Date / Due</th>
                            <th class="text-right py-2.5 px-3">Total</th>
                            <th class="text-right py-2.5 px-3">Paid</th>
                            <th class="text-right py-2.5 px-3">Balance Due</th>
                            <th class="text-left py-2.5 px-3">Status</th>
                            <th class="text-right py-2.5 px-3 no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bills as $b)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-3 font-semibold text-primary">{{ $b->bill_no }}</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ $b->vendor_invoice_no ?: ($b->purchaseOrder?->po_no ?? 'Direct') }}</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ format_date($b->invoice_date) }} @if($b->due_date)· Due {{ format_date($b->due_date) }}@endif</td>
                                <td class="py-2.5 px-3 text-right font-semibold">{{ format_currency($b->total_amount) }}</td>
                                <td class="py-2.5 px-3 text-right text-success">{{ format_currency($b->paid_amount) }}</td>
                                <td class="py-2.5 px-3 text-right font-bold {{ $b->balance_due > 0 ? 'text-danger' : 'text-default' }}">{{ format_currency($b->balance_due) }}</td>
                                <td class="py-2.5 px-3"><span class="text-[11px] px-2 py-0.5 rounded capitalize {{ $b->status === 'paid' ? 'bg-success-transparent text-success' : ($b->status === 'partial' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">{{ $b->status }}</span></td>
                                <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                    <a href="{{ route('purchase.invoices.print', $b->id) }}" target="_blank" class="btn-xsmall bg-light border border-border-color text-title">Print</a>
                                    @if ($b->balance_due > 0)
                                        @canModule('purchase', 'create')
                                        <button type="button" wire:click="openPayModal({{ $b->id }})" class="btn-xsmall bg-primary text-white">Pay</button>
                                        @endcanModule
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-default">No purchase bills found for this vendor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($activeTab === 'history')
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-title mb-2">Purchase Orders ({{ $orders->count() }})</h3>
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left p-2">PO #</th><th class="text-left p-2">Date</th><th class="text-left p-2">Status</th><th class="text-right p-2">Total</th></tr></thead>
                        <tbody>
                            @forelse ($orders as $po)
                                <tr class="border-b border-border-color last:border-0"><td class="p-2 font-semibold text-primary">{{ $po->po_no }}</td><td class="p-2 text-xs">{{ format_date($po->order_date) }}</td><td class="p-2 text-xs capitalize">{{ str_replace('_', ' ', $po->status) }}</td><td class="p-2 text-right font-semibold">{{ format_currency($po->total_amount) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="p-4 text-center text-default text-xs">No purchase orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-title mb-2">Goods Receipts (GRNs)</h3>
                        <table class="w-full text-xs">
                            <thead><tr class="bg-light border-b border-border-color"><th class="text-left p-2">GRN #</th><th class="text-left p-2">Date</th><th class="text-left p-2">PO #</th><th class="text-right p-2">Qty</th></tr></thead>
                            <tbody>
                                @forelse ($grns as $g)
                                    <tr class="border-b border-border-color last:border-0"><td class="p-2 font-semibold text-primary">{{ $g->grn_no }}</td><td class="p-2">{{ format_date($g->received_date) }}</td><td class="p-2">{{ $g->purchaseOrder?->po_no ?? 'Direct' }}</td><td class="p-2 text-right font-semibold">{{ $g->items->sum('quantity_received') }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="p-4 text-center text-default">No GRNs recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-title mb-2">Purchase Returns</h3>
                        <table class="w-full text-xs">
                            <thead><tr class="bg-light border-b border-border-color"><th class="text-left p-2">Return #</th><th class="text-left p-2">Date</th><th class="text-left p-2">Reason</th><th class="text-right p-2">Amount</th></tr></thead>
                            <tbody>
                                @forelse ($returns as $r)
                                    <tr class="border-b border-border-color last:border-0"><td class="p-2 font-semibold text-primary">{{ $r->return_no }}</td><td class="p-2">{{ format_date($r->return_date) }}</td><td class="p-2">{{ $r->reason }}</td><td class="p-2 text-right font-semibold">{{ format_currency($r->total_amount) }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="p-4 text-center text-default">No purchase returns.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @elseif ($activeTab === 'payments')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Payment #</th>
                            <th class="text-left py-2.5 px-3">Date</th>
                            <th class="text-left py-2.5 px-3">Applied Bill</th>
                            <th class="text-left py-2.5 px-3">Method / Account</th>
                            <th class="text-left py-2.5 px-3">Reference</th>
                            <th class="text-right py-2.5 px-3">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $p)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-3 font-semibold text-primary">{{ $p->payment_no }}</td>
                                <td class="py-2.5 px-3 text-xs">{{ format_date($p->payment_date) }}</td>
                                <td class="py-2.5 px-3 text-xs">@if($p->purchaseInvoice)<a href="{{ route('purchase.invoices.print', $p->purchaseInvoice->id) }}" target="_blank" class="text-primary hover:underline">{{ $p->purchaseInvoice->bill_no }}</a>@else On Account @endif</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ $p->paymentMethod?->name ?? '—' }} @if($p->bankAccount) ({{ $p->bankAccount->bank_name }}) @endif</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ $p->reference ?: '—' }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-danger">{{ format_currency($p->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No vendor payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Code</th><th class="text-left py-2 px-3">Product</th><th class="text-left py-2 px-3">Category</th><th class="text-right py-2 px-3">Cost Price</th><th class="text-right py-2 px-3">In Stock</th></tr></thead>
                    <tbody>
                        @forelse ($products as $pr)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 text-xs font-semibold text-primary">{{ $pr->code }}</td>
                                <td class="py-2 px-3"><a href="{{ route('inventory.products.show', $pr->id) }}" class="font-semibold text-title hover:text-primary hover:underline">{{ $pr->name }}</a></td>
                                <td class="py-2 px-3 text-xs">{{ $pr->category?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-right">{{ format_currency($pr->cost_price) }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ $pr->stock_quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-default">No catalog products default to this vendor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($showPayModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Pay Vendor</h2>
                <button type="button" wire:click="$set('showPayModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="makePayment" class="p-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Apply to Bill (Optional)</label>
                    <select wire:model.live="pay_bill_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">General Vendor Payment / Advance</option>
                        @foreach($openBills as $ob)<option value="{{ $ob->id }}">{{ $ob->bill_no }} (Due: {{ format_currency($ob->balance_due) }})</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="pay_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('pay_amount')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                    <div><label class="text-xs font-semibold block mb-1">Date *</label><input type="date" wire:model="pay_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
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
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference</label><input type="text" wire:model="pay_reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showPayModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-primary text-white">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
