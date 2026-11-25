<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Purchase Invoices &amp; Bills</h1>
            <p class="text-sm text-default mb-0">Record vendor bills, track payables, pay suppliers, and print purchase vouchers.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('purchase.payments') }}" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-money"></i> Supplier Payments</a>
            @canModule('purchase', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> New Purchase Bill</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search bill #, vendor invoice #..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="statusFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Statuses</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Bill #</th>
                        <th class="text-left py-2.5 px-3">Ref / Vendor Inv</th>
                        <th class="text-left py-2.5 px-3">Vendor</th>
                        <th class="text-left py-2.5 px-3">Bill Date</th>
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
                            <td class="py-2.5 px-3 text-xs text-default">
                                {{ $b->vendor_invoice_no ?: ($b->purchaseOrder?->po_no ?? ($b->goodsReceipt?->grn_no ?? 'Direct Bill')) }}
                            </td>
                            <td class="py-2.5 px-3 font-medium text-title">{{ $b->supplier?->name }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($b->invoice_date) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($b->total_amount) }}</td>
                            <td class="py-2.5 px-3 text-right text-success">{{ format_currency($b->paid_amount) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold {{ $b->balance_due > 0 ? 'text-danger' : 'text-default' }}">{{ format_currency($b->balance_due) }}</td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded capitalize {{ $b->status === 'paid' ? 'bg-success-transparent text-success' : ($b->status === 'partial' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">{{ $b->status }}</span>
                            </td>
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
                        <tr><td colspan="9" class="py-6 text-center text-default">No purchase bills found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $bills->links() }}</div>
    </div>

    @if ($showCreateModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-4xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Create Purchase Bill</h2>
                <button type="button" wire:click="$set('showCreateModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveBill" class="p-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold block mb-1">Vendor *</label>
                        <select wire:model="supplier_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="">Select Vendor</option>
                            @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        @error('supplier_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div><label class="text-xs font-semibold block mb-1">Vendor Invoice #</label><input type="text" wire:model="vendor_invoice_no" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Bill Date *</label><input type="date" wire:model="invoice_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Due Date</label><input type="date" wire:model="due_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Discount</label><input type="number" step="0.01" wire:model.live="discount_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Shipping</label><input type="number" step="0.01" wire:model.live="shipping_charge" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>

                <label class="inline-flex items-center gap-2 text-xs font-semibold text-title">
                    <input type="checkbox" wire:model="receive_into_stock" class="rounded"> Automatically add purchased items to inventory stock
                </label>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-bold uppercase text-default mb-0">Bill Items</h3>
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
                                    <th class="text-right p-2 w-28">Unit Cost</th>
                                    <th class="text-right p-2 w-20">Tax %</th>
                                    <th class="text-right p-2 w-28">Total</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $it)
                                    @php
                                        $lSub = ((float)($it['quantity'] ?? 0)) * ((float)($it['unit_cost'] ?? 0));
                                        $lTax = erp_setting('tax_enabled', true) ? $lSub * ((float)($it['tax_rate'] ?? 0) / 100) : 0;
                                        $usedPids = collect($items)->except($i)->pluck('product_id')->filter()->map(fn ($v) => (int) $v)->all();
                                    @endphp
                                    <tr class="border-b border-border-color last:border-0">
                                        <td class="p-1.5">
                                            <select wire:model.live="items.{{ $i }}.product_id" class="w-full px-2 py-1 border border-border-color rounded">
                                                <option value="">Expense / Non-Stock</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" @disabled(in_array($p->id, $usedPids))>{{ $p->name }}{{ in_array($p->id, $usedPids) ? ' — Added' : '' }}</option>
                                                @endforeach
                                            </select>
                                            @error("items.{$i}.product_id")<div class="text-[10px] text-danger mt-0.5">{{ $message }}</div>@enderror
                                        </td>
                                        <td class="p-1.5"><input type="text" wire:model="items.{{ $i }}.description" class="w-full px-2 py-1 border border-border-color rounded"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.quantity" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.unit_cost" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.tax_rate" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5 text-right font-semibold">{{ format_currency($lSub + $lTax) }}</td>
                                        <td class="p-1.5 text-center">@if(count($items)>1)<button type="button" wire:click="removeItem({{ $i }})" class="text-danger"><i class="ph ph-trash"></i></button>@endif</td>
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
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save Purchase Bill</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($showPayModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Pay Supplier Bill</h2>
                <button type="button" wire:click="$set('showPayModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="paySupplier" class="p-4 space-y-3">
                <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="pay_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('pay_amount')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Payment Date *</label><input type="date" wire:model="pay_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Payment Method</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Pay From Bank Account</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Account</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_number }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference</label><input type="text" wire:model="pay_reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showPayModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-primary text-white">Pay Supplier</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
