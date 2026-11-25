<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Purchase Orders</h1>
            <p class="text-sm text-default mb-0">Create vendor purchase orders, receive goods (GRN), and convert orders into purchase bills.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('purchase.grn') }}" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-truck"></i> Goods Received (GRN)</a>
            @canModule('purchase', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> New Purchase Order</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search PO # or vendor..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="statusFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="ordered">Ordered</option>
                <option value="partially_received">Partially Received</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">PO #</th>
                        <th class="text-left py-2.5 px-3">Vendor</th>
                        <th class="text-left py-2.5 px-3">Order Date</th>
                        <th class="text-left py-2.5 px-3">Expected</th>
                        <th class="text-right py-2.5 px-3">Total</th>
                        <th class="text-left py-2.5 px-3">Status</th>
                        <th class="text-right py-2.5 px-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $o)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary">{{ $o->po_no }}</td>
                            <td class="py-2.5 px-3 font-medium text-title">{{ $o->supplier?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($o->order_date) }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $o->expected_date ? format_date($o->expected_date) : '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($o->total_amount) }}</td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded capitalize {{ $o->status === 'received' ? 'bg-success-transparent text-success' : ($o->status === 'partially_received' ? 'bg-warning-transparent text-warning' : 'bg-info-transparent text-info') }}">
                                    {{ str_replace('_', ' ', $o->status) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                @if ($o->status !== 'received' && $o->status !== 'cancelled')
                                    @canModule('purchase', 'create')
                                    <button type="button" wire:click="openReceiveGrn({{ $o->id }})" class="btn-xsmall bg-primary text-white">Receive (GRN)</button>
                                    @endcanModule
                                @endif
                                @if ($o->purchaseInvoices->isNotEmpty())
                                    <a href="{{ route('purchase.invoices.print', $o->purchaseInvoices->first()->id) }}" target="_blank" class="btn-xsmall bg-success-transparent text-success">{{ $o->purchaseInvoices->first()->bill_no }}</a>
                                @else
                                    @canModule('purchase', 'create')
                                    <button type="button" wire:click="createBillFromPo({{ $o->id }})" class="btn-xsmall bg-light border border-border-color text-title">Create Bill</button>
                                    @endcanModule
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-default">No purchase orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-4xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Create Purchase Order</h2>
                <button type="button" wire:click="$set('showModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveOrder" class="p-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-3">
                        <label class="text-xs font-semibold block mb-1">Vendor *</label>
                        <select wire:model="supplier_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="">Select Vendor</option>
                            @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        @error('supplier_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Status</label>
                        <select wire:model="status" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="draft">Draft</option>
                            <option value="ordered">Ordered</option>
                        </select>
                    </div>
                    <div><label class="text-xs font-semibold block mb-1">Order Date *</label><input type="date" wire:model="order_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Expected Delivery</label><input type="date" wire:model="expected_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Discount</label><input type="number" step="0.01" wire:model.live="discount_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Shipping</label><input type="number" step="0.01" wire:model.live="shipping_charge" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-bold uppercase text-default mb-0">Purchase Items</h3>
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
                                                <option value="">Custom Item</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" @disabled(in_array($p->id, $usedPids))>{{ $p->name }} ({{ $p->sku }}){{ in_array($p->id, $usedPids) ? ' — Added' : '' }}</option>
                                                @endforeach
                                            </select>
                                            @error("items.{$i}.product_id")<div class="text-[10px] text-danger mt-0.5">{{ $message }}</div>@enderror
                                        </td>
                                        <td class="p-1.5"><input type="text" wire:model="items.{{ $i }}.description" class="w-full px-2 py-1 border border-border-color rounded"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.quantity" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model.live="items.{{ $i }}.unit_cost" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
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
                    <div><label class="text-xs font-semibold block mb-1">PO Notes</label><textarea wire:model="notes" rows="2" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></textarea></div>
                    <div class="bg-light p-3 rounded-md text-sm space-y-1">
                        <div class="flex justify-between"><span class="text-default">Subtotal:</span><span class="font-semibold">{{ format_currency($this->totals['subtotal']) }}</span></div>
                        <div class="flex justify-between"><span class="text-default">Tax:</span><span class="font-semibold">{{ format_currency($this->totals['tax']) }}</span></div>
                        <div class="flex justify-between border-t border-border-color pt-1 text-base font-bold text-title"><span>Grand Total:</span><span>{{ format_currency($this->totals['total']) }}</span></div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Create Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($showGrnModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-2xl">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Receive Goods (GRN)</h2>
                <button type="button" wire:click="$set('showGrnModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="receiveGoods" class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-xs font-semibold block mb-1">Received Date *</label><input type="date" wire:model="grn_received_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Notes</label><input type="text" wire:model="grn_notes" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>
                <div class="border border-border-color rounded-md overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-light border-b border-border-color">
                            <tr>
                                <th class="text-left p-2">Product</th>
                                <th class="text-right p-2">Ordered</th>
                                <th class="text-right p-2">Received</th>
                                <th class="text-right p-2 w-28">Receive Now</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grnItems as $gi => $item)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="p-2 font-medium text-title">{{ $item['description'] }}</td>
                                    <td class="p-2 text-right">{{ $item['ordered_qty'] }}</td>
                                    <td class="p-2 text-right text-default">{{ $item['already_received'] }}</td>
                                    <td class="p-1.5"><input type="number" step="0.01" wire:model="grnItems.{{ $gi }}.qty_to_receive" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showGrnModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-success text-white">Confirm Receipt &amp; Add Stock</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
