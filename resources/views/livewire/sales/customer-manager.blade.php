<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Customer Management</h1>
            <p class="text-sm text-default mb-0">Customer directory, credit limits, tax numbers, and outstanding balances.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('sales.balances') }}" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-scales"></i> Outstanding Balances</a>
            @canModule('sales', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Add Customer</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search code, name, company..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-72">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Code</th>
                        <th class="text-left py-2.5 px-3">Customer / Company</th>
                        <th class="text-left py-2.5 px-3">Contact</th>
                        <th class="text-left py-2.5 px-3">City / Country</th>
                        <th class="text-right py-2.5 px-3">Credit Limit</th>
                        <th class="text-right py-2.5 px-3">Outstanding</th>
                        <th class="text-right py-2.5 px-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $c)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary text-xs">{{ $c->code }}</td>
                            <td class="py-2.5 px-3">
                                <a href="{{ route('sales.customers.show', $c->id) }}" class="font-semibold text-title hover:text-primary hover:underline">{{ $c->name }}</a>
                                @if ($c->company_name)<div class="text-xs text-default">{{ $c->company_name }}</div>@endif
                            </td>
                            <td class="py-2.5 px-3 text-xs text-default">
                                <div>{{ $c->email ?: '—' }}</div>
                                <div>{{ $c->phone ?: '—' }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-default text-xs">{{ collect([$c->city, $c->country])->filter()->implode(', ') ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-right text-default">{{ format_currency($c->credit_limit) }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold {{ $c->outstanding_balance > 0 ? 'text-danger' : 'text-success' }}">{{ format_currency($c->outstanding_balance) }}</td>
                            <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                <a href="{{ route('sales.customers.show', $c->id) }}" class="btn-xsmall bg-dark text-white">Profile</a>
                                @canModule('sales', 'edit')
                                <button type="button" wire:click="openEdit({{ $c->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                @endcanModule
                                @canModule('sales', 'delete')
                                <button type="button" wire:click="deleteCustomer({{ $c->id }})" wire:confirm="Remove customer {{ $c->name }}?" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-default">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-2xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">{{ $editingId ? 'Edit Customer' : 'Add Customer' }}</h2>
                <button type="button" wire:click="$set('showModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="save" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-xs font-semibold block mb-1">Customer Code *</label><input type="text" wire:model="code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('code')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Customer Name *</label><input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('name')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Company Name</label><input type="text" wire:model="company_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Tax / VAT Number</label><input type="text" wire:model="tax_number" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Email</label><input type="email" wire:model="email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Phone</label><input type="text" wire:model="phone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Billing Address</label><input type="text" wire:model="billing_address" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">City</label><input type="text" wire:model="city" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Country</label><input type="text" wire:model="country" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Credit Limit</label><input type="number" step="0.01" wire:model="credit_limit" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Opening Balance</label><input type="number" step="0.01" wire:model="opening_balance" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>

                <div class="sm:col-span-2 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
