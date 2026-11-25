<div>
    <div class="mb-4">
        <h1 class="text-gray-900 text-xl font-bold mb-1">Product Categories, Subcategories, Brands &amp; Units</h1>
        <p class="text-sm text-default mb-0">Organize inventory into parent categories, nested subcategories, brands, and units of measure.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-7 bg-white border border-border-color rounded-md p-5 space-y-4">
            <h2 class="text-base font-bold text-title mb-0">Categories &amp; Subcategories</h2>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                <select wire:model="parent_id" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <option value="">Top-Level Category</option>
                    @foreach ($parentCategories as $pc)<option value="{{ $pc->id }}">Sub of: {{ $pc->name }}</option>@endforeach
                </select>
                <input type="text" wire:model="cat_name" placeholder="Name" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                <input type="text" wire:model="cat_code" placeholder="Code" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                <button type="button" wire:click="saveCategory" class="btn-sm bg-dark text-white">{{ $editingCatId ? 'Update' : 'Save Category' }}</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Category / Subcategory</th><th class="text-left py-2 px-3">Parent</th><th class="text-left py-2 px-3">Code</th><th class="text-right py-2 px-3">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($allCategories as $c)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-medium text-title">{{ $c->parent_id ? '↳ '.$c->name : $c->name }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $c->parent?->name ?? 'Root Category' }}</td>
                                <td class="py-2 px-3 text-xs text-default">{{ $c->code }}</td>
                                <td class="py-2 px-3 text-right space-x-1">
                                    <button type="button" wire:click="editCategory({{ $c->id }})" class="btn-xsmall bg-light border border-border-color">Edit</button>
                                    <button type="button" wire:click="deleteCategory({{ $c->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white border border-border-color rounded-md p-5 space-y-3">
                <h2 class="text-base font-bold text-title mb-0">Brands</h2>
                <div class="flex gap-2">
                    <input type="text" wire:model="brand_name" placeholder="Brand Name" class="flex-1 px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <button type="button" wire:click="addBrand" class="btn-sm bg-dark text-white">Add</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($brands as $b)
                        <span class="inline-flex items-center gap-1.5 text-xs bg-light border border-border-color px-2.5 py-1 rounded">
                            {{ $b->name }}
                            <button type="button" wire:click="deleteBrand({{ $b->id }})" class="text-danger hover:underline">×</button>
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="bg-white border border-border-color rounded-md p-5 space-y-3">
                <h2 class="text-base font-bold text-title mb-0">Units</h2>
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" wire:model="unit_name" placeholder="Unit (Piece)" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <input type="text" wire:model="unit_short" placeholder="Short (pcs)" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <button type="button" wire:click="addUnit" class="btn-sm bg-dark text-white">Add Unit</button>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($units as $u)
                        <span class="inline-flex items-center gap-1.5 text-xs bg-light border border-border-color px-2.5 py-1 rounded">
                            {{ $u->name }} ({{ $u->short_name }})
                            <button type="button" wire:click="deleteUnit({{ $u->id }})" class="text-danger hover:underline">×</button>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
