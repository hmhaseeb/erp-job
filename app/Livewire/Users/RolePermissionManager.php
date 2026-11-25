<?php

namespace App\Livewire\Users;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Roles & Permissions')]
class RolePermissionManager extends Component
{
    public bool $showRoleModal = false;
    public bool $showPermissionModal = false;

    public ?int $editingRoleId = null;
    public string $name = '';
    public string $description = '';
    public string $status = 'active';

    public ?int $permissionRoleId = null;
    public string $permissionRoleName = '';
    public array $matrix = [];

    public function openCreateRole(): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'create'), 403);
        $this->editingRoleId = null;
        $this->name = '';
        $this->description = '';
        $this->status = 'active';
        $this->showRoleModal = true;
    }

    public function openEditRole(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);
        $role = Role::findOrFail($id);
        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->description = (string) ($role->description ?? '');
        $this->status = $role->status;
        $this->showRoleModal = true;
    }

    public function saveRole(): void
    {
        $action = $this->editingRoleId ? 'edit' : 'create';
        abort_unless(auth()->user()->hasPermission('users', $action), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'.($this->editingRoleId ? ','.$this->editingRoleId : '')],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'status' => $validated['status'],
            ]);
            log_activity('users', 'role_updated', "Updated role {$role->name}", $role);
        } else {
            $role = Role::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'],
                'status' => $validated['status'],
            ]);
            foreach (Permission::all() as $perm) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'permission_id' => $perm->id,
                    'can_view' => $perm->module === 'dashboard',
                    'can_create' => false,
                    'can_edit' => false,
                    'can_delete' => false,
                ]);
            }
            log_activity('users', 'role_created', "Created role {$role->name}", $role);
        }

        $this->showRoleModal = false;
        $this->dispatch('toast', message: 'Role saved successfully.', type: 'success');
    }

    public function openPermissions(int $roleId): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);
        $role = Role::with('rolePermissions')->findOrFail($roleId);
        $this->permissionRoleId = $role->id;
        $this->permissionRoleName = $role->name;

        $existing = $role->rolePermissions->keyBy('permission_id');
        $this->matrix = [];

        foreach (Permission::orderBy('module')->orderBy('id')->get() as $perm) {
            $rp = $existing->get($perm->id);
            $this->matrix[$perm->id] = [
                'view' => (bool) ($rp?->can_view ?? false),
                'create' => (bool) ($rp?->can_create ?? false),
                'edit' => (bool) ($rp?->can_edit ?? false),
                'delete' => (bool) ($rp?->can_delete ?? false),
            ];
        }

        $this->showPermissionModal = true;
    }

    public function toggleRowAll(int $permId): void
    {
        $current = $this->matrix[$permId] ?? ['view' => false, 'create' => false, 'edit' => false, 'delete' => false];
        $allChecked = $current['view'] && $current['create'] && $current['edit'] && $current['delete'];
        $next = ! $allChecked;
        $this->matrix[$permId] = [
            'view' => $next,
            'create' => $next,
            'edit' => $next,
            'delete' => $next,
        ];
    }

    public function savePermissions(): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);
        if (! $this->permissionRoleId) {
            return;
        }

        foreach ($this->matrix as $permId => $flags) {
            RolePermission::updateOrCreate(
                ['role_id' => $this->permissionRoleId, 'permission_id' => $permId],
                [
                    'can_view' => (bool) ($flags['view'] ?? false),
                    'can_create' => (bool) ($flags['create'] ?? false),
                    'can_edit' => (bool) ($flags['edit'] ?? false),
                    'can_delete' => (bool) ($flags['delete'] ?? false),
                ]
            );
        }

        log_activity('users', 'permissions_updated', "Updated module permissions for role {$this->permissionRoleName}");
        $this->showPermissionModal = false;
        $this->dispatch('toast', message: 'Module-wise permissions updated.', type: 'success');
    }

    public function deleteRole(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'delete'), 403);
        $role = Role::withCount('users')->findOrFail($id);
        if ($role->slug === 'admin' || $role->users_count > 0) {
            $this->dispatch('toast', message: 'Cannot delete Administrator role or roles assigned to active users.', type: 'error');
            return;
        }
        $name = $role->name;
        $role->delete();
        log_activity('users', 'role_deleted', "Deleted role {$name}");
        $this->dispatch('toast', message: 'Role deleted.', type: 'success');
    }

    public function render()
    {
        return view('livewire.users.role-permission-manager', [
            'roles' => Role::withCount('users')->orderBy('id')->get(),
            'groupedPermissions' => Permission::orderBy('id')->get()->groupBy('module'),
        ]);
    }
}
