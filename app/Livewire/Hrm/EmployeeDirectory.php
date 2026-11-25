<?php

namespace App\Livewire\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Employees Directory')]
class EmployeeDirectory extends Component
{
    use WithFileUploads, WithPagination;

    public string $viewMode = 'list'; // list | grid
    public string $search = '';
    public string $departmentFilter = '';
    public string $lifecycleFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $emp_code = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $gender = 'Male';
    public ?string $dob = null;
    public string $address = '';
    public string $emergency_contact = '';
    public ?int $department_id = null;
    public ?int $designation_id = null;
    public string $employment_type = 'full_time';
    public string $shift = 'Day';
    public ?string $join_date = null;
    public ?string $probation_end_date = null;
    public string $lifecycle_status = 'onboarding';
    public float $basic_salary = 0;
    public float $allowances = 0;
    public string $bank_name = '';
    public string $bank_account_no = '';
    public string $status = 'active';
    public $avatarUpload;

    public bool $createUserLogin = false;
    public ?int $login_role_id = null;
    public string $login_password = 'password';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDepartmentFilter(): void { $this->resetPage(); }
    public function updatingLifecycleFilter(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'create'), 403);
        $this->resetForm();
        $this->emp_code = erp_next_number('employee_prefix', 'employees', 'emp_code', 'EMP-', 4);
        $this->join_date = now()->toDateString();
        $this->probation_end_date = now()->addMonths(3)->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        $emp = Employee::findOrFail($id);
        $this->editingId = $emp->id;
        $this->emp_code = $emp->emp_code;
        $this->first_name = $emp->first_name;
        $this->last_name = $emp->last_name;
        $this->email = $emp->email;
        $this->phone = (string) ($emp->phone ?? '');
        $this->gender = (string) ($emp->gender ?? 'Male');
        $this->dob = $emp->dob?->format('Y-m-d');
        $this->address = (string) ($emp->address ?? '');
        $this->emergency_contact = (string) ($emp->emergency_contact ?? '');
        $this->department_id = $emp->department_id;
        $this->designation_id = $emp->designation_id;
        $this->employment_type = $emp->employment_type;
        $this->shift = $emp->shift;
        $this->join_date = $emp->join_date?->format('Y-m-d');
        $this->probation_end_date = $emp->probation_end_date?->format('Y-m-d');
        $this->lifecycle_status = $emp->lifecycle_status;
        $this->basic_salary = (float) $emp->basic_salary;
        $this->allowances = (float) $emp->allowances;
        $this->bank_name = (string) ($emp->bank_name ?? '');
        $this->bank_account_no = (string) ($emp->bank_account_no ?? '');
        $this->status = $emp->status;
        $this->createUserLogin = false;
        $this->showModal = true;
    }

    public function save(): void
    {
        $action = $this->editingId ? 'edit' : 'create';
        abort_unless(auth()->user()->hasPermission('hrm', $action), 403);

        $this->validate([
            'emp_code' => ['required', 'string', 'max:40', 'unique:employees,emp_code'.($this->editingId ? ','.$this->editingId : '')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:employees,email'.($this->editingId ? ','.$this->editingId : '')],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['required', 'numeric', 'min:0'],
            'avatarUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function () {
            $data = [
                'emp_code' => $this->emp_code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'gender' => $this->gender,
                'dob' => $this->dob ?: null,
                'address' => $this->address,
                'emergency_contact' => $this->emergency_contact,
                'department_id' => $this->department_id ?: null,
                'designation_id' => $this->designation_id ?: null,
                'employment_type' => $this->employment_type,
                'shift' => $this->shift,
                'join_date' => $this->join_date ?: now()->toDateString(),
                'probation_end_date' => $this->probation_end_date ?: null,
                'lifecycle_status' => $this->lifecycle_status,
                'basic_salary' => $this->basic_salary,
                'allowances' => $this->allowances,
                'bank_name' => $this->bank_name,
                'bank_account_no' => $this->bank_account_no,
                'status' => $this->status,
            ];

            if ($this->avatarUpload) {
                $data['avatar'] = $this->avatarUpload->store('employees/avatars', 'public');
            }

            if ($this->editingId) {
                $emp = Employee::findOrFail($this->editingId);
                $emp->update($data);
                log_activity('hrm', 'employee_updated', "Updated employee {$emp->emp_code} ({$emp->full_name})", $emp);
            } else {
                if ($this->createUserLogin && ! User::where('email', $this->email)->exists()) {
                    $user = User::create([
                        'name' => trim($this->first_name.' '.$this->last_name),
                        'email' => $this->email,
                        'phone' => $this->phone,
                        'role_id' => $this->login_role_id ?: Role::where('slug', 'staff')->value('id'),
                        'department_id' => $this->department_id ?: null,
                        'user_type' => 'staff',
                        'status' => $this->status,
                        'password' => Hash::make($this->login_password ?: 'password'),
                    ]);
                    $data['user_id'] = $user->id;
                }

                $emp = Employee::create($data);
                EmployeeLifecycleEvent::create([
                    'employee_id' => $emp->id,
                    'event_type' => 'onboarding',
                    'effective_date' => $emp->join_date ?? now()->toDateString(),
                    'new_value' => ucfirst($emp->lifecycle_status),
                    'reason' => 'Employee registered & onboarded',
                    'approved_by' => auth()->id(),
                ]);
                log_activity('hrm', 'employee_registered', "Registered employee {$emp->emp_code} ({$emp->full_name})", $emp);
            }
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Employee saved successfully.', type: 'success');
    }

    public function deleteEmployee(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'delete'), 403);
        $emp = Employee::findOrFail($id);
        $code = $emp->emp_code;
        $emp->delete();
        log_activity('hrm', 'employee_deleted', "Deleted employee {$code}");
        $this->dispatch('toast', message: 'Employee removed.', type: 'success');
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = Employee::with(['department', 'designation'])->orderBy('emp_code')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Emp Code', 'Name', 'Email', 'Department', 'Designation', 'Lifecycle', 'Basic Salary', 'Allowances', 'Status']);
            foreach ($rows as $e) {
                fputcsv($out, [
                    $e->emp_code,
                    $e->full_name,
                    $e->email,
                    $e->department?->name,
                    $e->designation?->name,
                    ucwords(str_replace('_', ' ', $e->lifecycle_status)),
                    $e->basic_salary,
                    $e->allowances,
                    ucfirst($e->status),
                ]);
            }
            fclose($out);
        }, 'employees.csv');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'emp_code', 'first_name', 'last_name', 'email', 'phone', 'dob',
            'address', 'emergency_contact', 'department_id', 'designation_id',
            'join_date', 'probation_end_date', 'bank_name', 'bank_account_no', 'avatarUpload',
        ]);
        $this->gender = 'Male';
        $this->employment_type = 'full_time';
        $this->shift = 'Day';
        $this->lifecycle_status = 'onboarding';
        $this->basic_salary = 0;
        $this->allowances = 0;
        $this->status = 'active';
        $this->createUserLogin = false;
        $this->login_role_id = Role::where('slug', 'staff')->value('id');
        $this->login_password = 'password';
    }

    public function render()
    {
        $employees = Employee::with(['department', 'designation', 'user'])
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('emp_code', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->lifecycleFilter, fn ($q) => $q->where('lifecycle_status', $this->lifecycleFilter))
            ->orderByDesc('id')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.hrm.employee-directory', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
