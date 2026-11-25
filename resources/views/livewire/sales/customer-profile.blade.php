<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.customers') }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left"></i></a>
            <div>
                <div class="flex items-center flex-wrap gap-2">
                    <h1 class="text-gray-900 text-xl font-bold mb-0">{{ $customer->name }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded bg-light border border-border-color font-semibold text-primary">{{ $customer->code }}</span>
                    <span class="text-xs px-2 py-0.5 rounded capitalize {{ $customer->status === 'active' ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">{{ $customer->status }}</span>
                </div>
                <p class="text-sm text-default mb-0">{{ $customer->company_name ?: 'Individual Customer' }} · {{ $customer->email ?: 'No email' }} · {{ $customer->phone ?: 'No phone' }}</p>
            </div>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5"><i class="ph-duotone ph-printer"></i> Print Statement</button>
            @canModule('sales', 'create')
            <button type="button" wire:click="openCollectPayment()" class="btn-sm bg-success text-white inline-flex items-center gap-1.5"><i class="ph ph-currency-dollar"></i> Collect Payment</button>
            @endcanModule
        </div>
    </div>

    <!-- KPI Row -->
    @php
        $outstanding = $customer->outstanding_balance;
        $availableCredit = max(0, (float) $customer->credit_limit - max(0, $outstanding));
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Total Sales &amp; Service Billed</p>
            <h3 class="text-xl font-bold text-gray-900 mb-0">{{ format_currency($totalInvoiced) }}</h3>
            <span class="text-[11px] text-default">{{ $invoices->count() }} invoice(s) · {{ $jobCards->count() }} job card(s)</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Total Payments Collected</p>
            <h3 class="text-xl font-bold text-success mb-0">{{ format_currency($totalPaid) }}</h3>
            <span class="text-[11px] text-default">Returns Credit: {{ format_currency($totalReturned) }}</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Outstanding Receivable</p>
            <h3 class="text-xl font-bold {{ $outstanding > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ format_currency($outstanding) }}</h3>
            <span class="text-[11px] text-default">Opening Balance: {{ format_currency($customer->opening_balance) }}</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Credit Limit &amp; Headroom</p>
            <h3 class="text-xl font-bold text-primary mb-0">{{ format_currency($customer->credit_limit) }}</h3>
            <span class="text-[11px] text-default">Available Credit: {{ format_currency($availableCredit) }}</span>
        </div>
    </div>

    <!-- Customer Details Card -->
    <div class="bg-white border border-border-color rounded-md p-4 mb-4">
        <h2 class="text-base font-bold text-title mb-3">Customer Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div><span class="text-xs text-default block">Customer / Contact Name</span><strong class="text-title">{{ $customer->name }}</strong></div>
            <div><span class="text-xs text-default block">Company</span><strong class="text-title">{{ $customer->company_name ?: '—' }}</strong></div>
            <div><span class="text-xs text-default block">Tax / VAT Number</span><strong class="text-title">{{ $customer->tax_number ?: '—' }}</strong></div>
            <div><span class="text-xs text-default block">Contact Info</span><strong class="text-title">{{ $customer->phone ?: '—' }} / {{ $customer->email ?: '—' }}</strong></div>
            <div class="sm:col-span-2 lg:col-span-4"><span class="text-xs text-default block">Billing Address</span><strong class="text-title">{{ collect([$customer->billing_address, $customer->city, $customer->country])->filter()->implode(', ') ?: '—' }}</strong></div>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap gap-1.5 mb-4 no-print">
            <button type="button" wire:click="$set('activeTab', 'invoices')" class="btn-sm {{ $activeTab === 'invoices' ? 'bg-dark text-white' : 'bg-light text-title' }}">Sales Bills / Invoices ({{ $invoices->count() }})</button>
            <button type="button" wire:click="$set('activeTab', 'job_cards')" class="btn-sm {{ $activeTab === 'job_cards' ? 'bg-dark text-white' : 'bg-light text-title' }}">Service Job Cards ({{ $jobCards->count() }})</button>
            <button type="button" wire:click="$set('activeTab', 'payments')" class="btn-sm {{ $activeTab === 'payments' ? 'bg-dark text-white' : 'bg-light text-title' }}">Payment History ({{ $payments->count() }})</button>
            <button type="button" wire:click="$set('activeTab', 'orders_returns')" class="btn-sm {{ $activeTab === 'orders_returns' ? 'bg-dark text-white' : 'bg-light text-title' }}">Orders &amp; Returns ({{ $orders->count() + $returns->count() }})</button>
        </div>

        @if ($activeTab === 'invoices')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Invoice #</th>
                            <th class="text-left py-2.5 px-3">Source</th>
                            <th class="text-left py-2.5 px-3">Date / Due</th>
                            <th class="text-right py-2.5 px-3">Total</th>
                            <th class="text-right py-2.5 px-3">Paid</th>
                            <th class="text-right py-2.5 px-3">Balance Due</th>
                            <th class="text-left py-2.5 px-3">Status</th>
                            <th class="text-right py-2.5 px-3 no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $inv)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-3 font-semibold text-primary">{{ $inv->invoice_no }}</td>
                                <td class="py-2.5 px-3 text-xs">
                                    @if ($inv->source === 'job_card' && $inv->jobCard)
                                        <a href="{{ route('job-cards.show', $inv->jobCard->id) }}" class="px-2 py-0.5 rounded bg-secondary-transparent text-secondary hover:underline">{{ $inv->jobCard->job_card_no }}</a>
                                    @elseif ($inv->salesOrder)
                                        <span class="px-2 py-0.5 rounded bg-info-transparent text-info">{{ $inv->salesOrder->order_no }}</span>
                                    @else
                                        Direct Sale
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ format_date($inv->invoice_date) }} @if($inv->due_date)· Due {{ format_date($inv->due_date) }}@endif</td>
                                <td class="py-2.5 px-3 text-right font-semibold">{{ format_currency($inv->total_amount) }}</td>
                                <td class="py-2.5 px-3 text-right text-success">{{ format_currency($inv->paid_amount) }}</td>
                                <td class="py-2.5 px-3 text-right font-bold {{ $inv->balance_due > 0 ? 'text-danger' : 'text-default' }}">{{ format_currency($inv->balance_due) }}</td>
                                <td class="py-2.5 px-3"><span class="text-[11px] px-2 py-0.5 rounded capitalize {{ $inv->status === 'paid' ? 'bg-success-transparent text-success' : ($inv->status === 'partial' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">{{ $inv->status }}</span></td>
                                <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                    <a href="{{ route('sales.invoices.print', $inv->id) }}" target="_blank" class="btn-xsmall bg-light border border-border-color text-title">Print</a>
                                    @if ($inv->balance_due > 0)
                                        @canModule('sales', 'create')
                                        <button type="button" wire:click="openCollectPayment({{ $inv->id }})" class="btn-xsmall bg-success text-white">Collect</button>
                                        @endcanModule
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-default">No sales invoices found for this customer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($activeTab === 'job_cards')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Job Card #</th>
                            <th class="text-left py-2.5 px-3">Device / Product</th>
                            <th class="text-left py-2.5 px-3">Technician</th>
                            <th class="text-left py-2.5 px-3">Progress</th>
                            <th class="text-left py-2.5 px-3">Status</th>
                            <th class="text-right py-2.5 px-3">Total Billing</th>
                            <th class="text-right py-2.5 px-3 no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobCards as $jc)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-3 font-semibold text-primary">
                                    <a href="{{ route('job-cards.show', $jc->id) }}" class="hover:underline">{{ $jc->job_card_no }}</a>
                                    <div class="text-[11px] text-default">{{ format_date($jc->received_date) }}</div>
                                </td>
                                <td class="py-2.5 px-3"><div class="font-medium text-title">{{ $jc->item_name }}</div><div class="text-xs text-default">{{ $jc->brand_model }} @if($jc->serial_number) · SN: {{ $jc->serial_number }}@endif</div></td>
                                <td class="py-2.5 px-3 text-xs">{{ $jc->technician?->full_name ?? 'Unassigned' }}</td>
                                <td class="py-2.5 px-3 text-xs font-semibold">{{ $jc->progress_percent }}%</td>
                                <td class="py-2.5 px-3"><span class="text-[11px] px-2 py-0.5 rounded capitalize bg-info-transparent text-info">{{ str_replace('_', ' ', $jc->status) }}</span></td>
                                <td class="py-2.5 px-3 text-right font-semibold">{{ format_currency($jc->total_amount ?: $jc->estimated_cost) }}</td>
                                <td class="py-2.5 px-3 text-right no-print">
                                    <a href="{{ route('job-cards.show', $jc->id) }}" class="btn-xsmall bg-dark text-white">Open Job Card</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-default">No service job cards found for this customer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($activeTab === 'payments')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Receipt #</th>
                            <th class="text-left py-2.5 px-3">Date</th>
                            <th class="text-left py-2.5 px-3">Applied Invoice</th>
                            <th class="text-left py-2.5 px-3">Method / Account</th>
                            <th class="text-left py-2.5 px-3">Reference</th>
                            <th class="text-right py-2.5 px-3">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $p)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-3 font-semibold text-primary">{{ $p->receipt_no }}</td>
                                <td class="py-2.5 px-3 text-xs">{{ format_date($p->payment_date) }}</td>
                                <td class="py-2.5 px-3 text-xs">@if($p->salesInvoice)<a href="{{ route('sales.invoices.print', $p->salesInvoice->id) }}" target="_blank" class="text-primary hover:underline">{{ $p->salesInvoice->invoice_no }}</a>@else On Account @endif</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ $p->paymentMethod?->name ?? '—' }} @if($p->bankAccount) ({{ $p->bankAccount->bank_name }}) @endif</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ $p->reference ?: '—' }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-success">{{ format_currency($p->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No payment history recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-bold text-title mb-2">Sales Orders</h3>
                    <table class="w-full text-xs">
                        <thead><tr class="bg-light border-b border-border-color"><th class="text-left p-2">Order #</th><th class="text-left p-2">Date</th><th class="text-left p-2">Status</th><th class="text-right p-2">Total</th></tr></thead>
                        <tbody>
                            @forelse ($orders as $o)
                                <tr class="border-b border-border-color last:border-0"><td class="p-2 font-semibold text-primary">{{ $o->order_no }}</td><td class="p-2">{{ format_date($o->order_date) }}</td><td class="p-2 capitalize">{{ $o->status }}</td><td class="p-2 text-right font-semibold">{{ format_currency($o->total_amount) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="p-4 text-center text-default">No sales orders.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-title mb-2">Sales Returns</h3>
                    <table class="w-full text-xs">
                        <thead><tr class="bg-light border-b border-border-color"><th class="text-left p-2">Return #</th><th class="text-left p-2">Date</th><th class="text-left p-2">Reason</th><th class="text-right p-2">Total</th></tr></thead>
                        <tbody>
                            @forelse ($returns as $r)
                                <tr class="border-b border-border-color last:border-0"><td class="p-2 font-semibold text-primary">{{ $r->return_no }}</td><td class="p-2">{{ format_date($r->return_date) }}</td><td class="p-2">{{ $r->reason }}</td><td class="p-2 text-right font-semibold">{{ format_currency($r->total_amount) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="p-4 text-center text-default">No sales returns.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($showPayModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Collect Customer Payment</h2>
                <button type="button" wire:click="$set('showPayModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="collectPayment" class="p-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Apply to Invoice (Optional)</label>
                    <select wire:model.live="pay_invoice_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">General Customer Account Receipt</option>
                        @foreach($openInvoices as $oi)<option value="{{ $oi->id }}">{{ $oi->invoice_no }} (Due: {{ format_currency($oi->balance_due) }})</option>@endforeach
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
                    <label class="text-xs font-semibold block mb-1">Deposit To Account</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference</label><input type="text" wire:model="pay_reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showPayModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-success text-white">Record Receipt</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
