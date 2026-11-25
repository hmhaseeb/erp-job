<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Roles &amp; Module-wise Permissions</h1>
            <p class="text-sm text-default mb-0">Configure role access controls across all ERP modules.</p>
        </div>
        @canModule('users', 'create')
        <button type="button" wire:click="openCreateRole" class="btn-sm bg-dark text-white border border-dark inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Add Role</button>
        @endcanModule
    </div>

    <div class="bg-white border border-border-color rounded-md overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-default bg-light border-b border-border-color">
                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Role Name</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Description</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Assigned Users</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-900">Status</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-900">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr class="border-b border-border-color last:border-0">
                        <td class="py-3 px-4 font-semibold text-title">{{ $role->name }}</td>
                        <td class="py-3 px-4 text-default">{{ $role->description ?? '—' }}</td>
                        <td class="py-3 px-4 text-default">{{ $role->users_count }}</td>
                        <td class="py-3 px-4">
                            <span class="text-[11px] px-2 py-0.5 rounded {{ $role->status === 'active' ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">
                                {{ ucfirst($role->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right space-x-1">
                            @canModule('users', 'edit')
                            <button type="button" wire:click="openPermissions({{ $role->id }})" class="btn-xsmall bg-primary text-white hover:bg-primary-hover">Module Permissions</button>
                            <button type="button" wire:click="openEditRole({{ $role->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                            @endcanModule
                            @canModule('users', 'delete')
                            @if ($role->slug !== 'admin' && $role->users_count === 0)
                            <button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="Delete role {{ $role->name }}?" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                            @endif
                            @endcanModule
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Role Modal -->
    @if ($showRoleModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">{{ $editingRoleId ? 'Edit Role' : 'Add Role' }}</h2>
                <button type="button" wire:click="$set('showRoleModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveRole" class="p-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Role Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    @error('name')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Description</label>
                    <input type="text" wire:model="description" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Status</label>
                    <select wire:model="status" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showRoleModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white border border-dark">Save Role</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Module-wise Permissions Matrix Modal -->
    @if ($showPermissionModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-3xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Module Permissions — {{ $permissionRoleName }}</h2>
                <button type="button" wire:click="$set('showPermissionModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <div class="p-4 overflow-y-auto space-y-4">
                @foreach ($groupedPermissions as $module => $perms)
                    <div>
                        <h3 class="text-sm font-bold text-title uppercase mb-2">{{ str_replace('_', ' ', $module) }}</h3>
                        <div class="overflow-x-auto border border-border-color rounded">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-default bg-light border-b border-border-color">
                                        <th class="text-left py-2 px-3">Feature</th>
                                        <th class="text-center py-2 px-3">View</th>
                                        <th class="text-center py-2 px-3">Create</th>
                                        <th class="text-center py-2 px-3">Edit</th>
                                        <th class="text-center py-2 px-3">Delete</th>
                                        <th class="text-center py-2 px-3">Toggle Row</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($perms as $perm)
                                        <tr class="border-b border-border-color last:border-0">
                                            <td class="py-2 px-3 text-title font-medium">{{ $perm->feature }}</td>
                                            <td class="py-2 px-3 text-center"><input type="checkbox" wire:model="matrix.{{ $perm->id }}.view" class="size-4 rounded border-border-color text-primary"></td>
                                            <td class="py-2 px-3 text-center"><input type="checkbox" wire:model="matrix.{{ $perm->id }}.create" class="size-4 rounded border-border-color text-primary"></td>
                                            <td class="py-2 px-3 text-center"><input type="checkbox" wire:model="matrix.{{ $perm->id }}.edit" class="size-4 rounded border-border-color text-primary"></td>
                                            <td class="py-2 px-3 text-center"><input type="checkbox" wire:model="matrix.{{ $perm->id }}.delete" class="size-4 rounded border-border-color text-primary"></td>
                                            <td class="py-2 px-3 text-center">
                                                <button type="button" wire:click="toggleRowAll({{ $perm->id }})" class="btn-xsmall bg-light border border-border-color text-title">All</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-2 p-4 border-t border-border-color">
                <button type="button" wire:click="$set('showPermissionModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                <button type="button" wire:click="savePermissions" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Permissions</button>
            </div>
        </div>
    </div>
    @endif
</div>
