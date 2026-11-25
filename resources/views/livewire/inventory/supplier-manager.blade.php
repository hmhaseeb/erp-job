<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Suppliers &amp; Vendors</h1>
            <p class="text-sm text-default mb-0">Manage product suppliers and procurement vendors with live payable balances.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white hover:bg-primary-hover">+ Add Supplier / Vendor</button>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="mb-3"><input type="text" wire:model.live.debounce.300ms="search" placeholder="Search supplier/vendor..." class="px-3 py-1.5 text-sm border border-border-color rounded-md w-64"></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2.5 px-3">Code</th><th class="text-left py-2.5 px-3">Name / Company</th><th class="text-left py-2.5 px-3">Contact</th><th class="text-left py-2.5 px-3">Tax No</th><th class="text-right py-2.5 px-3">Payable Balance</th><th class="text-right py-2.5 px-3">Actions</th></tr></thead>
                <tbody>
                    @forelse ($suppliers as $s)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary">{{ $s->code }}</td>
                            <td class="py-2.5 px-3"><a href="{{ route('purchase.vendors.show', $s->id) }}" class="font-semibold text-title hover:text-primary hover:underline block">{{ $s->name }}</a><div class="text-xs text-default">{{ $s->company_name }}</div></td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $s->contact_person }}<br>{{ $s->phone }} · {{ $s->email }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $s->tax_number ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold {{ $s->outstanding_balance > 0 ? 'text-danger' : 'text-success' }}">{{ format_currency($s->outstanding_balance) }}</td>
                            <td class="py-2.5 px-3 text-right space-x-1">
                                <a href="{{ route('purchase.vendors.show', $s->id) }}" class="btn-xsmall bg-dark text-white">Profile</a>
                                <button type="button" wire:click="openEdit({{ $s->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                <button type="button" wire:click="deleteSupplier({{ $s->id }})" wire:confirm="Delete {{ $s->name }}?" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-default">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $suppliers->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-2xl p-5">
            <h3 class="text-base font-bold text-title mb-3">{{ $editingId ? 'Edit Supplier / Vendor' : 'Add Supplier / Vendor' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-xs font-semibold block mb-1">Code *</label><input type="text" wire:model="code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Name *</label><input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Company Name</label><input type="text" wire:model="company_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Contact Person</label><input type="text" wire:model="contact_person" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Email</label><input type="email" wire:model="email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Phone</label><input type="text" wire:model="phone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Tax / VAT Number</label><input type="text" wire:model="tax_number" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Opening Balance</label><input type="number" step="0.01" wire:model="opening_balance" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Address</label><input type="text" wire:model="address" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            </div>
            <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-border-color">
                <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color">Cancel</button>
                <button type="button" wire:click="save" class="btn-sm bg-dark text-white">Save Supplier</button>
            </div>
        </div>
    </div>
    @endif
</div>
