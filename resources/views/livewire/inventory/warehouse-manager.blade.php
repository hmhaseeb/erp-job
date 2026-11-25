<div>
    <div class="mb-4">
        <h1 class="text-gray-900 text-xl font-bold mb-1">Warehouses</h1>
        <p class="text-sm text-default mb-0">Manage storage locations and track stock distribution across warehouses.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-4 bg-white border border-border-color rounded-md p-5 space-y-3">
            <h2 class="text-base font-bold text-title mb-0">{{ $editingId ? 'Edit Warehouse' : 'Add Warehouse' }}</h2>
            <div><label class="text-xs font-semibold block mb-1">Name *</label><input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <div><label class="text-xs font-semibold block mb-1">Code *</label><input type="text" wire:model="code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <div><label class="text-xs font-semibold block mb-1">Location</label><input type="text" wire:model="location" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <div><label class="text-xs font-semibold block mb-1">Manager</label><input type="text" wire:model="manager_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="is_default" class="size-4"> Default Warehouse</label>
            <button type="button" wire:click="save" class="btn-sm w-full bg-dark text-white">Save Warehouse</button>
        </div>

        <div class="lg:col-span-8 bg-white border border-border-color rounded-md p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Code</th><th class="text-left py-2 px-3">Warehouse</th><th class="text-left py-2 px-3">Location</th><th class="text-left py-2 px-3">Manager</th><th class="text-right py-2 px-3">Total Units</th><th class="text-right py-2 px-3">Action</th></tr></thead>
                <tbody>
                    @foreach ($warehouses as $w)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary">{{ $w->code }}</td>
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $w->name }} @if($w->is_default)<span class="text-[10px] bg-success-transparent text-success px-1.5 py-0.5 rounded">Default</span>@endif</td>
                            <td class="py-2.5 px-3 text-default">{{ $w->location ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-default">{{ $w->manager_name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold">{{ number_format($w->stocks_sum_quantity ?? 0, 2) }}</td>
                            <td class="py-2.5 px-3 text-right"><button type="button" wire:click="edit({{ $w->id }})" class="btn-xsmall bg-light border border-border-color">Edit</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
