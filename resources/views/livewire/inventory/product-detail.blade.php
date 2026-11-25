<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.products') }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left"></i></a>
            <div>
                <div class="flex items-center flex-wrap gap-2">
                    <h1 class="text-gray-900 text-xl font-bold mb-0">{{ $product->name }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded bg-light border border-border-color font-semibold text-primary">{{ $product->code }}</span>
                    <span class="text-xs px-2 py-0.5 rounded {{ $product->stock_status === 'In Stock' ? 'bg-success-transparent text-success' : ($product->stock_status === 'Low Stock' ? 'bg-warning-transparent text-warning' : ($product->stock_status === 'Service' ? 'bg-info-transparent text-info' : 'bg-danger-transparent text-danger')) }}">
                        {{ $product->stock_status }}
                    </span>
                </div>
                <p class="text-sm text-default mb-0">SKU: <strong>{{ $product->sku }}</strong> @if($product->barcode)· Barcode: <strong>{{ $product->barcode }}</strong>@endif · {{ $product->category?->name ?? 'Uncategorized' }} @if($product->subcategory)› {{ $product->subcategory->name }}@endif</p>
            </div>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5"><i class="ph-duotone ph-printer"></i> Print Sheet</button>
            <a href="{{ route('inventory.stock') }}" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph-duotone ph-arrows-left-right"></i> Adjust / Transfer Stock</a>
        </div>
    </div>

    <!-- KPI Row -->
    @php
        $valuation = (float) $product->stock_quantity * (float) $product->cost_price;
        $marginPct = (float) $product->selling_price > 0
            ? round((((float) $product->selling_price - (float) $product->cost_price) / (float) $product->selling_price) * 100, 1)
            : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Total Available Stock</p>
            <h3 class="text-xl font-bold text-gray-900 mb-0">{{ $product->is_service ? 'Service Item' : $product->stock_quantity.' '.$product->unit?->short_name }}</h3>
            <span class="text-[11px] text-default">Reorder Alert Level: {{ $product->reorder_level }}</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Inventory Valuation (Cost)</p>
            <h3 class="text-xl font-bold text-primary mb-0">{{ format_currency($valuation) }}</h3>
            <span class="text-[11px] text-default">Unit Cost: {{ format_currency($product->cost_price) }}</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Selling Price &amp; Margin</p>
            <h3 class="text-xl font-bold text-success mb-0">{{ format_currency($product->selling_price) }}</h3>
            <span class="text-[11px] text-default">Gross Margin: {{ $marginPct }}%</span>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <p class="text-xs text-default mb-1">Stock Movements Recorded</p>
            <h3 class="text-xl font-bold text-info mb-0">{{ $movements->total() }}</h3>
            <span class="text-[11px] text-default">Sales: {{ $salesItems->count() }} · Purchases: {{ $purchaseItems->count() }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Product Images Gallery & Multi-Upload -->
        <div class="bg-white border border-border-color rounded-md p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-title mb-0">Product Images</h2>
                <span class="text-xs text-default">{{ $product->images->count() }} image(s)</span>
            </div>

            @php
                $primarySrc = $product->image ? asset('storage/'.$product->image) : ($product->images->first()?->url);
            @endphp
            <div class="border border-border-color rounded-md bg-light h-52 flex items-center justify-center overflow-hidden">
                @if ($primarySrc)
                    <img src="{{ $primarySrc }}" alt="{{ $product->name }}" class="max-h-full max-w-full object-contain cursor-pointer" wire:click="$set('previewImageUrl', '{{ $primarySrc }}')">
                @else
                    <div class="text-center text-default text-xs p-4">
                        <i class="ph-duotone ph-image text-3xl block mb-1"></i>
                        No product image uploaded yet.
                    </div>
                @endif
            </div>

            @if ($product->images->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($product->images as $img)
                        <div class="border {{ $img->is_primary ? 'border-primary ring-1 ring-primary' : 'border-border-color' }} rounded p-1 bg-light/50 relative">
                            <img src="{{ $img->url }}" alt="{{ $img->caption ?? $product->name }}" class="w-full h-16 object-cover rounded cursor-pointer" wire:click="$set('previewImageUrl', '{{ $img->url }}')">
                            @if ($img->caption)<div class="text-[10px] text-default truncate mt-0.5">{{ $img->caption }}</div>@endif
                            @canModule('inventory', 'edit')
                            <div class="flex justify-between items-center mt-1 gap-1 no-print">
                                @if (! $img->is_primary)
                                    <button type="button" wire:click="setPrimaryImage({{ $img->id }})" class="text-[10px] text-primary hover:underline">Primary</button>
                                @else
                                    <span class="text-[10px] font-semibold text-success">Primary</span>
                                @endif
                                <button type="button" wire:click="deleteGalleryImage({{ $img->id }})" wire:confirm="Remove this image?" class="text-[10px] text-danger"><i class="ph ph-trash"></i></button>
                            </div>
                            @endcanModule
                        </div>
                    @endforeach
                </div>
            @endif

            @canModule('inventory', 'edit')
            <form wire:submit="uploadImages" class="pt-3 border-t border-border-color space-y-2 no-print">
                <label class="text-xs font-semibold block mb-0">Upload Product Images (Multiple)</label>
                <input type="file" wire:model="photos" multiple accept="image/*" class="w-full text-xs">
                @error('photos')<span class="text-xs text-danger block">{{ $message }}</span>@enderror
                @error('photos.*')<span class="text-xs text-danger block">{{ $message }}</span>@enderror
                <div class="flex gap-2">
                    <input type="text" wire:model="photoCaption" placeholder="Optional caption..." class="flex-1 px-2.5 py-1.5 text-xs border border-border-color rounded-md">
                    <button type="submit" class="btn-xsmall bg-dark text-white">Upload</button>
                </div>
            </form>
            @endcanModule
        </div>

        <!-- Complete Product Information -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Complete Product Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Product Code</span><strong class="text-title">{{ $product->code }}</strong></div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">SKU</span><strong class="text-title">{{ $product->sku }}</strong></div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Barcode</span><strong class="text-title">{{ $product->barcode ?: '—' }}</strong></div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Category / Subcategory</span><strong class="text-title">{{ $product->category?->name ?? '—' }} @if($product->subcategory) › {{ $product->subcategory->name }} @endif</strong></div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Brand</span><strong class="text-title">{{ $product->brand?->name ?? '—' }}</strong></div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Unit &amp; Type</span><strong class="text-title">{{ $product->unit?->name ?? '—' }} ({{ $product->is_service ? 'Service' : 'Physical Stock' }})</strong></div>
                    <div class="p-2.5 rounded bg-light/60">
                        <span class="text-xs text-default block">Default Supplier / Vendor</span>
                        @if ($product->defaultSupplier)
                            <a href="{{ route('purchase.vendors.show', $product->defaultSupplier->id) }}" class="font-bold text-primary hover:underline">{{ $product->defaultSupplier->name }}</a>
                        @else
                            <strong class="text-title">—</strong>
                        @endif
                    </div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Tax Rate</span><strong class="text-title">{{ $product->taxRate ? $product->taxRate->name.' ('.$product->taxRate->rate.'%)' : 'Default Tax' }}</strong></div>
                    <div class="p-2.5 rounded bg-light/60"><span class="text-xs text-default block">Status</span><strong class="capitalize text-title">{{ $product->status }}</strong></div>
                </div>
                @if ($product->description)
                    <div class="mt-3 pt-3 border-t border-border-color text-sm text-default">
                        <strong class="text-title block text-xs uppercase mb-1">Description</strong>
                        {{ $product->description }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Product Transaction History -->
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 no-print">
            <div class="flex flex-wrap gap-1.5">
                <button type="button" wire:click="switchTab('movements')" class="btn-sm {{ $activeTab === 'movements' ? 'bg-dark text-white' : 'bg-light text-title' }}">Stock Movement Ledger</button>
                <button type="button" wire:click="switchTab('sales')" class="btn-sm {{ $activeTab === 'sales' ? 'bg-dark text-white' : 'bg-light text-title' }}">Sales History ({{ $salesItems->count() }})</button>
                <button type="button" wire:click="switchTab('purchases')" class="btn-sm {{ $activeTab === 'purchases' ? 'bg-dark text-white' : 'bg-light text-title' }}">Purchase History ({{ $purchaseItems->count() }})</button>
                <button type="button" wire:click="switchTab('job_cards')" class="btn-sm {{ $activeTab === 'job_cards' ? 'bg-dark text-white' : 'bg-light text-title' }}">Service Job Card Usage ({{ $jobCardParts->count() }})</button>
            </div>
            @if ($activeTab === 'movements')
                <select wire:model.live="movementFilter" class="px-3 py-1.5 text-xs border border-border-color rounded-md bg-white">
                    <option value="">All Movement Types</option>
                    <option value="in">Stock In</option>
                    <option value="out">Stock Out</option>
                    <option value="sale">Sale</option>
                    <option value="purchase">Purchase</option>
                    <option value="adjustment">Adjustment</option>
                    <option value="sales_return">Sales Return</option>
                    <option value="purchase_return">Purchase Return</option>
                    <option value="job_card_use">Job Card Spare Use</option>
                </select>
            @endif
        </div>

        @if ($activeTab === 'movements')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Type</th>
                            <th class="text-left py-2 px-3">Ref #</th>
                            <th class="text-right py-2 px-3">Qty Change</th>
                            <th class="text-right py-2 px-3">Balance After</th>
                            <th class="text-right py-2 px-3">Unit Cost</th>
                            <th class="text-left py-2 px-3">Notes / User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 text-xs text-default">{{ $m->created_at?->format('d M Y H:i') }}</td>
                                <td class="py-2 px-3"><span class="text-[11px] px-2 py-0.5 rounded bg-light border border-border-color uppercase">{{ str_replace('_', ' ', $m->movement_type) }}</span></td>
                                <td class="py-2 px-3 text-xs font-semibold text-primary">{{ $m->reference_no ?? '—' }}</td>
                                <td class="py-2 px-3 text-right font-bold {{ $m->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity_change >= 0 ? '+' : '' }}{{ $m->quantity_change }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ $m->balance_after }}</td>
                                <td class="py-2 px-3 text-right text-xs">{{ format_currency($m->unit_cost) }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $m->notes }} @if($m->creator) <span class="text-[10px]">({{ $m->creator->name }})</span> @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-default">No stock transactions recorded for this product.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $movements->links() }}</div>

        @elseif ($activeTab === 'sales')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Invoice #</th><th class="text-left py-2 px-3">Date</th><th class="text-left py-2 px-3">Customer</th><th class="text-right py-2 px-3">Qty</th><th class="text-right py-2 px-3">Unit Price</th><th class="text-right py-2 px-3">Line Total</th></tr></thead>
                    <tbody>
                        @forelse ($salesItems as $si)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">@if($si->salesInvoice)<a href="{{ route('sales.invoices.print', $si->salesInvoice->id) }}" target="_blank" class="hover:underline">{{ $si->salesInvoice->invoice_no }}</a>@else — @endif</td>
                                <td class="py-2 px-3 text-xs">{{ format_date($si->salesInvoice?->invoice_date) }}</td>
                                <td class="py-2 px-3">@if($si->salesInvoice?->customer)<a href="{{ route('sales.customers.show', $si->salesInvoice->customer->id) }}" class="text-title hover:underline font-medium">{{ $si->salesInvoice->customer->name }}</a>@else — @endif</td>
                                <td class="py-2 px-3 text-right">{{ $si->quantity }}</td>
                                <td class="py-2 px-3 text-right">{{ format_currency($si->unit_price) }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ format_currency($si->line_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No sales invoices reference this product yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($activeTab === 'purchases')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Bill #</th><th class="text-left py-2 px-3">Date</th><th class="text-left py-2 px-3">Vendor</th><th class="text-right py-2 px-3">Qty</th><th class="text-right py-2 px-3">Unit Cost</th><th class="text-right py-2 px-3">Line Total</th></tr></thead>
                    <tbody>
                        @forelse ($purchaseItems as $pi)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">@if($pi->purchaseInvoice)<a href="{{ route('purchase.invoices.print', $pi->purchaseInvoice->id) }}" target="_blank" class="hover:underline">{{ $pi->purchaseInvoice->bill_no }}</a>@else — @endif</td>
                                <td class="py-2 px-3 text-xs">{{ format_date($pi->purchaseInvoice?->invoice_date) }}</td>
                                <td class="py-2 px-3">@if($pi->purchaseInvoice?->supplier)<a href="{{ route('purchase.vendors.show', $pi->purchaseInvoice->supplier->id) }}" class="text-title hover:underline font-medium">{{ $pi->purchaseInvoice->supplier->name }}</a>@else — @endif</td>
                                <td class="py-2 px-3 text-right">{{ $pi->quantity }}</td>
                                <td class="py-2 px-3 text-right">{{ format_currency($pi->unit_cost) }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ format_currency($pi->line_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No purchase bills reference this product yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Job Card #</th><th class="text-left py-2 px-3">Customer</th><th class="text-left py-2 px-3">Device / Status</th><th class="text-right py-2 px-3">Qty Used</th><th class="text-right py-2 px-3">Total</th></tr></thead>
                    <tbody>
                        @forelse ($jobCardParts as $jp)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary">@if($jp->jobCard)<a href="{{ route('job-cards.show', $jp->jobCard->id) }}" class="hover:underline">{{ $jp->jobCard->job_card_no }}</a>@else — @endif</td>
                                <td class="py-2 px-3">{{ $jp->jobCard?->customer?->name ?? '—' }}</td>
                                <td class="py-2 px-3 text-xs">{{ $jp->jobCard?->item_name }} ({{ str_replace('_', ' ', $jp->jobCard?->status ?? '') }})</td>
                                <td class="py-2 px-3 text-right">{{ $jp->quantity }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ format_currency($jp->line_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-default">Not used as spare part on any job card yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($previewImageUrl)
    <div class="fixed inset-0 z-[999] bg-black/75 flex items-center justify-center p-4" wire:click="$set('previewImageUrl', null)">
        <div class="bg-white rounded-md p-2 max-w-3xl max-h-[90vh] relative" wire:click.stop>
            <button type="button" wire:click="$set('previewImageUrl', null)" class="absolute top-3 right-3 size-8 rounded-full bg-dark text-white flex items-center justify-center"><i class="ph ph-x"></i></button>
            <img src="{{ $previewImageUrl }}" alt="Product preview" class="max-h-[82vh] max-w-full object-contain rounded">
        </div>
    </div>
    @endif
</div>
