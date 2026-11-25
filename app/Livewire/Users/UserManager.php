<?php

namespace App\Livewire\Users;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('User Management')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public ?int $role_id = null;
    public ?int $department_id = null;
    public string $user_type = 'staff';
    public string $status = 'active';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingRoleFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'create'), 403);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) ($user->phone ?? '');
        $this->role_id = $user->role_id;
        $this->department_id = $user->department_id;
        $this->user_type = $user->user_type;
        $this->status = $user->status;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $action = $this->editingId ? 'edit' : 'create';
        abort_unless(auth()->user()->hasPermission('users', $action), 403);

        $rules = [
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'.($this->editingId ? ','.$this->editingId : '')],
            'phone' => ['nullable', 'string', 'max:40'],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'user_type' => ['required', 'in:admin,staff'],
            'status' => ['required', 'in:active,inactive'],
        ];

        if (! $this->editingId || $this->password !== '') {
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        $validated = $this->validate($rules);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role_id' => $validated['role_id'],
            'department_id' => $validated['department_id'] ?: null,
            'user_type' => $validated['user_type'],
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
            log_activity('users', 'updated', "Updated user account {$user->email}", $user);
            $msg = 'User updated successfully.';
        } else {
            $user = User::create($data);
            log_activity('users', 'created', "Created user account {$user->email}", $user);
            $msg = 'User created successfully.';
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'edit'), 403);
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('toast', message: 'You cannot deactivate your own active session.', type: 'error');
            return;
        }
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        log_activity('users', 'status_toggled', "Changed status for {$user->email} to {$user->status}", $user);
        $this->dispatch('toast', message: 'User status updated.', type: 'success');
    }

    public function deleteUser(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'delete'), 403);
        if ($id === auth()->id()) {
            $this->dispatch('toast', message: 'Cannot delete the signed-in user.', type: 'error');
            return;
        }
        $user = User::findOrFail($id);
        $email = $user->email;
        $user->delete();
        log_activity('users', 'deleted', "Deleted user account {$email}");
        $this->dispatch('toast', message: 'User deleted.', type: 'success');
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = User::with(['role', 'department'])->orderBy('name')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Phone', 'Role', 'Department', 'Account Type', 'Status', 'Last Login']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->name,
                    $row->email,
                    $row->phone,
                    $row->role?->name,
                    $row->department?->name,
                    ucfirst($row->user_type),
                    ucfirst($row->status),
                    $row->last_login_at?->toDateTimeString() ?? 'Never',
                ]);
            }
            fclose($out);
        }, 'erp-users.csv');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->role_id = Role::where('slug', 'staff')->value('id');
        $this->department_id = null;
        $this->user_type = 'staff';
        $this->status = 'active';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function render()
    {
        $users = User::with(['role', 'department'])
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->roleFilter, fn ($q) => $q->where('role_id', $this->roleFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.users.user-manager', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}
