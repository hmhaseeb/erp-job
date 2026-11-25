<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Goods Received (GRN)</h1>
            <p class="text-sm text-default mb-0">Inspect incoming vendor shipments into inventory and generate supplier bills.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('purchase.orders') }}" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-clipboard-text"></i> Purchase Orders</a>
            @canModule('purchase', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Record GRN</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search GRN # or vendor..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-72">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">GRN #</th>
                        <th class="text-left py-2.5 px-3">PO Reference</th>
                        <th class="text-left py-2.5 px-3">Vendor</th>
                        <th class="text-left py-2.5 px-3">Received Date</th>
                        <th class="text-right py-2.5 px-3">Total Qty</th>
                        <th class="text-left py-2.5 px-3">Status</th>
                        <th class="text-right py-2.5 px-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receipts as $grn)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary">{{ $grn->grn_no }}</td>
                            <td class="py-2.5 px-3 text-xs">{{ $grn->purchaseOrder?->po_no ?? 'Direct GRN' }}</td>
                            <td class="py-2.5 px-3 font-medium text-title">{{ $grn->supplier?->name }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($grn->received_date) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold">{{ $grn->items->sum('quantity_received') }}</td>
                            <td class="py-2.5 px-3"><span class="text-[11px] px-2 py-0.5 rounded bg-success-transparent text-success capitalize">{{ $grn->status }}</span></td>
                            <td class="py-2.5 px-3 text-right no-print">
                                @canModule('purchase', 'create')
                                <button type="button" wire:click="generateBill({{ $grn->id }})" class="btn-xsmall bg-dark text-white">Generate Bill</button>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-default">No goods receipts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $receipts->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-3xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Record Goods Receipt (GRN)</h2>
                <button type="button" wire:click="$set('showModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveGrn" class="p-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Link to Open Purchase Order (Optional)</label>
                        <select wire:model.live="purchase_order_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="">Direct Receipt (No PO)</option>
                            @foreach($openPos as $po)<option value="{{ $po->id }}">{{ $po->po_no }} - {{ $po->supplier?->name }}</option>@endforeach
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
                    <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Received Date *</label><input type="date" wire:model="received_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-bold uppercase text-default mb-0">Received Items</h3>
                        <button type="button" wire:click="addItem" class="btn-xsmall bg-light border border-border-color text-title"><i class="ph ph-plus"></i> Add Item</button>
                    </div>
                    @error('items')<div class="text-xs text-danger mb-2">{{ $message }}</div>@enderror
                    <div class="border border-border-color rounded-md overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-light border-b border-border-color">
                                <tr>
                                    <th class="text-left p-2">Product *</th>
                                    <th class="text-right p-2 w-28">Qty Received *</th>
                                    <th class="text-right p-2 w-28">Unit Cost</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $it)
                                    @php $usedPids = collect($items)->except($i)->pluck('product_id')->filter()->map(fn ($v) => (int) $v)->all(); @endphp
                                    <tr class="border-b border-border-color last:border-0">
                                        <td class="p-1.5">
                                            <select wire:model.live="items.{{ $i }}.product_id" class="w-full px-2 py-1 border border-border-color rounded">
                                                <option value="">Select Product</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" @disabled(in_array($p->id, $usedPids))>{{ $p->name }} ({{ $p->code }}){{ in_array($p->id, $usedPids) ? ' — Added' : '' }}</option>
                                                @endforeach
                                            </select>
                                            @error("items.{$i}.product_id")<div class="text-[10px] text-danger mt-0.5">{{ $message }}</div>@enderror
                                        </td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model="items.{{ $i }}.quantity_received" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5"><input type="number" step="0.01" wire:model="items.{{ $i }}.unit_cost" class="w-full px-2 py-1 border border-border-color rounded text-right"></td>
                                        <td class="p-1.5 text-center">@if(count($items)>1)<button type="button" wire:click="removeItem({{ $i }})" class="text-danger"><i class="ph ph-trash"></i></button>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div><label class="text-xs font-semibold block mb-1">Inspection Notes</label><input type="text" wire:model="notes" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>

                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save GRN &amp; Increment Stock</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
