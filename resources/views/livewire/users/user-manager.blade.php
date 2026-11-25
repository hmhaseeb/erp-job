<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">User Management</h1>
            <p class="text-sm text-default mb-0">Manage Admin and Staff user accounts, roles, and access status.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-gray-900 inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-printer"></i> Print</button>
            <button type="button" wire:click="exportCsv" class="btn-sm bg-white border border-border-color text-gray-900 inline-flex items-center gap-1.5 hover:bg-light"><i class="icon-download"></i> Export CSV</button>
            @canModule('users', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white border border-dark inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Add User</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or email..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
                <select wire:model.live="roleFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                    <option value="">All Roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-default border-b border-border-color bg-light">
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">User</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Email</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Role</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Department</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Type</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Last Login</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Status</th>
                        <th class="text-right py-2.5 px-3 font-semibold text-gray-900 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-title">{{ $u->name }}</div>
                                <div class="text-xs text-default">{{ $u->phone ?? '—' }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-default">{{ $u->email }}</td>
                            <td class="py-2.5 px-3"><span class="text-xs bg-primary-transparent text-primary px-2 py-0.5 rounded">{{ $u->role?->name ?? 'Unassigned' }}</span></td>
                            <td class="py-2.5 px-3 text-default">{{ $u->department?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-default">{{ ucfirst($u->user_type) }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $u->last_login_at ? format_date($u->last_login_at, true) : 'Never' }}</td>
                            <td class="py-2.5 px-3">
                                <button type="button" wire:click="toggleStatus({{ $u->id }})" class="text-[11px] px-2 py-0.5 rounded cursor-pointer {{ $u->status === 'active' ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">
                                    {{ ucfirst($u->status) }}
                                </button>
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                @canModule('users', 'edit')
                                <button type="button" wire:click="openEdit({{ $u->id }})" class="btn-xsmall bg-light border border-border-color text-title hover:bg-primary hover:text-white">Edit</button>
                                @endcanModule
                                @canModule('users', 'delete')
                                @if ($u->id !== auth()->id())
                                <button type="button" wire:click="deleteUser({{ $u->id }})" wire:confirm="Delete user {{ $u->email }}?" class="btn-xsmall bg-danger-transparent text-danger hover:bg-danger hover:text-white">Delete</button>
                                @endif
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-default">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-xl">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">{{ $editingId ? 'Edit User' : 'Add New User' }}</h2>
                <button type="button" wire:click="$set('showModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="save" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-title mb-1 block">Full Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('name')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Email *</label>
                    <input type="email" wire:model="email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('email')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Phone</label>
                    <input type="text" wire:model="phone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Role *</label>
                    <select wire:model="role_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Department</label>
                    <select wire:model="department_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        <option value="">None</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Account Type *</label>
                    <select wire:model="user_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Status *</label>
                    <select wire:model="status" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Password {{ $editingId ? '(leave blank to keep)' : '*' }}</label>
                    <input type="password" wire:model="password" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('password')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Confirm Password</label>
                    <input type="password" wire:model="password_confirmation" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save User</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
