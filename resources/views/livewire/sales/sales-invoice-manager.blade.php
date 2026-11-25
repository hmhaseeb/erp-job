<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Sales Invoices &amp; Bills</h1>
            <p class="text-sm text-default mb-0">Direct product &amp; service billing, stock deduction, payment collection, and printable invoices.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('sales.payments') }}" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-money"></i> Receipts Ledger</a>
            @canModule('sales', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> New Invoice</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search invoice # or customer..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="statusFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Statuses</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Invoice #</th>
                        <th class="text-left py-2.5 px-3">Source / Ref</th>
                        <th class="text-left py-2.5 px-3">Customer</th>
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
                                @if ($inv->source === 'job_card')
                                    <span class="px-2 py-0.5 rounded bg-secondary-transparent text-secondary">Job Card {{ $inv->jobCard?->job_card_no }}</span>
                                @elseif ($inv->salesOrder)
                                    <span class="px-2 py-0.5 rounded bg-info-transparent text-info">{{ $inv->salesOrder->order_no }}</span>
                                @else
                                    <span class="text-default">Direct Sale</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 font-medium text-title">{{ $inv->customer?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">
                                <div>{{ format_date($inv->invoice_date) }}</div>
                                @if($inv->due_date)<div class="text-[11px]">Due: {{ format_date($inv->due_date) }}</div>@endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($inv->total_amount) }}</td>
                            <td class="py-2.5 px-3 text-right text-success">{{ format_currency($inv->paid_amount) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold {{ $inv->balance_due > 0 ? 'text-danger' : 'text-default' }}">{{ format_currency($inv->balance_due) }}</td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded capitalize {{ $inv->status === 'paid' ? 'bg-success-transparent text-success' : ($inv->status === 'partial' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
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
                        <tr><td colspan="9" class="py-6 text-center text-default">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>

    @if ($showCreateModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-4xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Create Sales Invoice</h2>
                <button type="button" wire:click="$set('showCreateModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveInvoice" class="p-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold block mb-1">Customer *</label>
                        <select wire:model="customer_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                        @error('customer_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div><label class="text-xs font-semibold block mb-1">Invoice Date *</label><input type="date" wire:model="invoice_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Due Date</label><input type="date" wire:model="due_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Discount</label><input type="number" step="0.01" wire:model.live="discount_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Shipping</label><input type="number" step="0.01" wire:model.live="shipping_charge" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-bold uppercase text-default mb-0">Invoice Items</h3>
                        <button type="button" wire:click="addItem" class="btn-xsmall bg-light border border-border-color text-title"><i class="ph ph-plus"></i> Add Line</button>
                    </div>
                    @error('items')<div class="text-xs text-danger mb-2">{{ $message }}</div>@enderror
                    <div class="border border-border-color rounded-md overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-light border-b border-border-color">
                                <tr>
                                    <th class="text-left p-2">Product</th>
                                    <th class="text-left p-2">Description *</th>
                                    <th class="text-right p-2 w-24">Qty *</th>
                                    <th class="text-right p-2 w-28">Unit Price</th>
                                    <th class="text-right p-2 w-20">Tax %</th>
                                    <th class="text-right p-2 w-28">Total</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $it)
                                    @php
                                        $lSub = ((float)($it['quantity'] ?? 0)) * ((float)($it['unit_price'] ?? 0));
                                        $lTax = erp_setting('tax_enabled', true) ? $lSub * ((float)($it['tax_rate'] ?? 0) / 100) : 0;
                                        $usedPids = collect($items)->except($i)->pluck('product_id')->filter()->map(fn ($v) => (int) $v)->all();
                                    @endphp
                                    <tr class="border-b border-border-color last:border-0">
                                        <td class="p-1.5">
                                            <select wire:model.live="items.{{ $i }}.product_id" class="w-full px-2 py-1 border border-border-color rounded">
                                                <option value="">Custom Item</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" @disabled(in_array($p->id, $usedPids))>{{ $p->name }} (Qty: {{ $p->stock_quantity }}){{ in_array($p->id, $usedPids) ? ' — Added' : '' }}</option>
                                                @endforeach
                                            </select>
                                            @error("items.{$i}.product_id")<div class="text-[10px] text-danger mt-0.5">{{ $message }}</div>@enderror
                                        </td>
                                        <td class="p-1.5"><input type="text" wire:model="items.{{ $i }}.description" class="w-full px-2 py-1 border border-border-color rounded"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.quantity" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.unit_price" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.tax_rate" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5 text-right font-semibold">{{ format_currency($lSub + $lTax) }}</td>
                                        <td class="p-1.5 text-center">
                                            @if(count($items) > 1)
                                            <button type="button" wire:click="removeItem({{ $i }})" class="text-danger"><i class="ph ph-trash"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div><label class="text-xs font-semibold block mb-1">Notes</label><textarea wire:model="notes" rows="2" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></textarea></div>
                    <div class="bg-light p-3 rounded-md text-sm space-y-1">
                        <div class="flex justify-between"><span class="text-default">Subtotal:</span><span class="font-semibold">{{ format_currency($this->totals['subtotal']) }}</span></div>
                        <div class="flex justify-between"><span class="text-default">Tax:</span><span class="font-semibold">{{ format_currency($this->totals['tax']) }}</span></div>
                        <div class="flex justify-between border-t border-border-color pt-1 text-base font-bold text-title"><span>Grand Total:</span><span>{{ format_currency($this->totals['total']) }}</span></div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save &amp; Deduct Stock</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($showPayModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Collect Invoice Payment</h2>
                <button type="button" wire:click="$set('showPayModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="collectPayment" class="p-4 space-y-3">
                <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="pay_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('pay_amount')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Payment Date *</label><input type="date" wire:model="pay_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Payment Method</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Deposit To Bank / Account (if Bank/Card)</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_number }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference / Cheque #</label><input type="text" wire:model="pay_reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showPayModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-success text-white">Record Receipt</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
