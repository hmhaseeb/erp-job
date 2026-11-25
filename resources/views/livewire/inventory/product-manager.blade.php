<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Products Management</h1>
            <p class="text-sm text-default mb-0">Catalog, SKU/barcodes, pricing, categories &amp; subcategories, and stock levels.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-printer"></i> Print</button>
            <button type="button" wire:click="exportCsv" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="icon-download"></i> Export CSV</button>
            @canModule('inventory', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Add Product</button>
            @endcanModule
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-4 flex items-center justify-between">
            <div><p class="text-xs text-default mb-1">Total Products</p><h3 class="text-xl font-bold text-gray-900 mb-0">{{ $stats['total'] }}</h3></div>
            <div class="size-9 rounded-md bg-success-transparent text-success flex items-center justify-center"><i class="ph-duotone ph-package text-lg"></i></div>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4 flex items-center justify-between">
            <div><p class="text-xs text-default mb-1">In Stock</p><h3 class="text-xl font-bold text-gray-900 mb-0">{{ $stats['in_stock'] }}</h3></div>
            <div class="size-9 rounded-md bg-info-transparent text-info flex items-center justify-center"><i class="ph-duotone ph-check-circle text-lg"></i></div>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4 flex items-center justify-between">
            <div><p class="text-xs text-default mb-1">Low Stock</p><h3 class="text-xl font-bold text-warning mb-0">{{ $stats['low_stock'] }}</h3></div>
            <div class="size-9 rounded-md bg-warning-transparent text-warning flex items-center justify-center"><i class="ph-duotone ph-warning text-lg"></i></div>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4 flex items-center justify-between">
            <div><p class="text-xs text-default mb-1">Out of Stock</p><h3 class="text-xl font-bold text-danger mb-0">{{ $stats['out_of_stock'] }}</h3></div>
            <div class="size-9 rounded-md bg-danger-transparent text-danger flex items-center justify-center"><i class="ph-duotone ph-archive text-lg"></i></div>
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, SKU, code..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="categoryFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
            <select wire:model.live="stockFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Stock States</option>
                <option value="in">In Stock</option>
                <option value="low">Low Stock</option>
                <option value="out">Out of Stock</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Code / SKU</th>
                        <th class="text-left py-2.5 px-3">Product</th>
                        <th class="text-left py-2.5 px-3">Category / Subcategory</th>
                        <th class="text-left py-2.5 px-3">Brand</th>
                        <th class="text-right py-2.5 px-3">Qty</th>
                        <th class="text-right py-2.5 px-3">Cost</th>
                        <th class="text-right py-2.5 px-3">Selling</th>
                        <th class="text-left py-2.5 px-3">Status</th>
                        <th class="text-right py-2.5 px-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 text-xs">
                                <div class="font-semibold text-primary">{{ $p->code }}</div>
                                <div class="text-default">{{ $p->sku }}</div>
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-title">
                                <a href="{{ route('inventory.products.show', $p->id) }}" class="hover:text-primary hover:underline flex items-center gap-2">
                                    @if ($p->image)
                                        <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->name }}" class="size-7 rounded object-cover border border-border-color shrink-0">
                                    @endif
                                    <span>{{ $p->name }}</span>
                                </a>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-default">
                                {{ $p->category?->name ?? '—' }}
                                @if ($p->subcategory) <span class="text-primary">› {{ $p->subcategory->name }}</span> @endif
                            </td>
                            <td class="py-2.5 px-3 text-default">{{ $p->brand?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ $p->stock_quantity }} <span class="text-xs text-default font-normal">{{ $p->unit?->short_name }}</span></td>
                            <td class="py-2.5 px-3 text-right text-default">{{ format_currency($p->cost_price) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($p->selling_price) }}</td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded {{ $p->stock_status === 'In Stock' ? 'bg-success-transparent text-success' : ($p->stock_status === 'Low Stock' ? 'bg-warning-transparent text-warning' : 'bg-danger-transparent text-danger') }}">
                                    {{ $p->stock_status }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                <a href="{{ route('inventory.products.show', $p->id) }}" class="btn-xsmall bg-dark text-white">View</a>
                                @canModule('inventory', 'edit')
                                <button type="button" wire:click="openEdit({{ $p->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                @endcanModule
                                @canModule('inventory', 'delete')
                                <button type="button" wire:click="deleteProduct({{ $p->id }})" wire:confirm="Delete {{ $p->name }}?" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-6 text-center text-default">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-3xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">{{ $editingId ? 'Edit Product' : 'Add Product' }}</h2>
                <button type="button" wire:click="$set('showModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="save" class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div><label class="text-xs font-semibold block mb-1">Product Code *</label><input type="text" wire:model="code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Product Name *</label><input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('name')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">SKU *</label><input type="text" wire:model="sku" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('sku')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Barcode</label><input type="text" wire:model="barcode" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Default Supplier</label><select wire:model="default_supplier_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>

                <div><label class="text-xs font-semibold block mb-1">Category</label><select wire:model.live="category_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="text-xs font-semibold block mb-1">Subcategory</label><select wire:model="subcategory_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">None</option>@foreach($subcategories as $sc)<option value="{{ $sc->id }}">{{ $sc->name }}</option>@endforeach</select></div>
                <div><label class="text-xs font-semibold block mb-1">Brand</label><select wire:model="brand_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>

                <div><label class="text-xs font-semibold block mb-1">Unit</label><select wire:model="unit_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->short_name }})</option>@endforeach</select></div>
                <div><label class="text-xs font-semibold block mb-1">Cost Price *</label><input type="number" step="0.01" wire:model="cost_price" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Selling Price *</label><input type="number" step="0.01" wire:model="selling_price" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>

                @if (! $editingId)
                <div><label class="text-xs font-semibold block mb-1">Opening Stock</label><input type="number" step="0.01" wire:model="stock_quantity" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                @endif
                <div><label class="text-xs font-semibold block mb-1">Low Stock Reorder Level *</label><input type="number" step="0.01" wire:model="reorder_level" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Tax Rate</label><select wire:model="tax_rate_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Default</option>@foreach($taxRates as $tr)<option value="{{ $tr->id }}">{{ $tr->name }} ({{ $tr->rate }}%)</option>@endforeach</select></div>

                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Description</label><input type="text" wire:model="description" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Primary Image</label><input type="file" wire:model="imageUpload" class="text-xs" accept="image/*"></div>
                <div class="sm:col-span-3"><label class="text-xs font-semibold block mb-1">Additional Gallery Images (Multiple)</label><input type="file" wire:model="galleryUploads" multiple class="text-xs" accept="image/*"></div>

                <div class="sm:col-span-3 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save Product</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
