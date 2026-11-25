<?php

namespace App\Livewire\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeLifecycleEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Employee Profile & Lifecycle')]
class EmployeeProfile extends Component
{
    use WithFileUploads;

    public Employee $employee;
    public string $activeTab = 'overview';

    // Document Upload
    public string $doc_title = '';
    public string $doc_type = 'ID / Passport';
    public ?string $doc_issue_date = null;
    public ?string $doc_expiry_date = null;
    public $doc_file;

    // Lifecycle Action
    public string $event_type = 'confirmation';
    public ?string $effective_date = null;
    public ?int $new_department_id = null;
    public ?int $new_designation_id = null;
    public float $new_basic_salary = 0;
    public float $new_allowances = 0;
    public string $event_reason = '';
    public string $event_notes = '';

    // User Account Linking
    public ?int $user_role_id = null;
    public string $user_password = '';

    public function mount(int $id): void
    {
        $this->employee = Employee::with([
            'department', 'designation', 'user.role', 'documents',
            'lifecycleEvents.approver', 'leaveRequests.leaveType',
            'attendances', 'payrollItems.payrollRun',
        ])->findOrFail($id);

        $this->effective_date = now()->toDateString();
        $this->new_department_id = $this->employee->department_id;
        $this->new_designation_id = $this->employee->designation_id;
        $this->new_basic_salary = (float) $this->employee->basic_salary;
        $this->new_allowances = (float) $this->employee->allowances;
        $this->user_role_id = $this->employee->user?->role_id ?? Role::where('slug', 'staff')->value('id');
    }

    public function uploadDocument(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        $this->validate([
            'doc_title' => ['required', 'string', 'max:150'],
            'doc_type' => ['required', 'string', 'max:60'],
            'doc_file' => ['required', 'file', 'max:5120'],
        ]);

        $path = $this->doc_file->store('employees/documents', 'public');
        EmployeeDocument::create([
            'employee_id' => $this->employee->id,
            'title' => $this->doc_title,
            'document_type' => $this->doc_type,
            'file_path' => $path,
            'issue_date' => $this->doc_issue_date ?: null,
            'expiry_date' => $this->doc_expiry_date ?: null,
            'is_verified' => true,
        ]);

        $this->reset(['doc_title', 'doc_issue_date', 'doc_expiry_date', 'doc_file']);
        $this->refreshEmployee();
        log_activity('hrm', 'document_uploaded', "Uploaded document for {$this->employee->emp_code}", $this->employee);
        $this->dispatch('toast', message: 'Employee document uploaded.', type: 'success');
    }

    public function deleteDocument(int $docId): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        EmployeeDocument::where('employee_id', $this->employee->id)->where('id', $docId)->delete();
        $this->refreshEmployee();
        $this->dispatch('toast', message: 'Document removed.', type: 'success');
    }

    public function applyLifecycleAction(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        $this->validate([
            'event_type' => ['required', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $emp = $this->employee;
        $oldVal = '';
        $newVal = '';

        switch ($this->event_type) {
            case 'onboarding':
                $oldVal = $emp->lifecycle_status;
                $newVal = 'onboarding';
                $emp->update(['lifecycle_status' => 'onboarding', 'status' => 'active']);
                break;

            case 'probation':
                $oldVal = $emp->lifecycle_status;
                $newVal = 'probation';
                $emp->update(['lifecycle_status' => 'probation', 'probation_end_date' => now()->parse($this->effective_date)->addMonths(3)->toDateString(), 'status' => 'active']);
                break;

            case 'confirmation':
                $oldVal = $emp->lifecycle_status;
                $newVal = 'confirmed';
                $emp->update(['lifecycle_status' => 'confirmed', 'confirmation_date' => $this->effective_date, 'status' => 'active']);
                break;

            case 'assignment':
            case 'transfer':
                $oldVal = ($emp->department?->name ?? 'None').' / '.($emp->designation?->name ?? 'None');
                $dept = Department::find($this->new_department_id);
                $desig = Designation::find($this->new_designation_id);
                $newVal = ($dept?->name ?? 'None').' / '.($desig?->name ?? 'None');
                $emp->update([
                    'department_id' => $this->new_department_id ?: $emp->department_id,
                    'designation_id' => $this->new_designation_id ?: $emp->designation_id,
                ]);
                break;

            case 'promotion':
                $oldVal = $emp->designation?->name ?? 'Current Role';
                $desig = Designation::find($this->new_designation_id);
                $newVal = $desig?->name ?? 'Promoted';
                $emp->update([
                    'designation_id' => $this->new_designation_id ?: $emp->designation_id,
                    'basic_salary' => $this->new_basic_salary > 0 ? $this->new_basic_salary : $emp->basic_salary,
                    'allowances' => $this->new_allowances >= 0 ? $this->new_allowances : $emp->allowances,
                ]);
                break;

            case 'salary_revision':
                $oldVal = format_currency($emp->gross_salary);
                $emp->update([
                    'basic_salary' => $this->new_basic_salary,
                    'allowances' => $this->new_allowances,
                ]);
                $newVal = format_currency($this->new_basic_salary + $this->new_allowances);
                break;

            case 'resignation':
                $oldVal = $emp->lifecycle_status;
                $newVal = 'resigned';
                $emp->update(['lifecycle_status' => 'resigned', 'resignation_date' => $this->effective_date]);
                break;

            case 'termination':
                $oldVal = $emp->lifecycle_status;
                $newVal = 'terminated';
                $emp->update(['lifecycle_status' => 'terminated', 'exit_date' => $this->effective_date, 'status' => 'inactive']);
                if ($emp->user) {
                    $emp->user->update(['status' => 'inactive']);
                }
                break;

            case 'exit':
                $oldVal = $emp->lifecycle_status;
                $newVal = 'exited';
                $emp->update(['lifecycle_status' => 'exited', 'exit_date' => $this->effective_date, 'status' => 'inactive']);
                if ($emp->user) {
                    $emp->user->update(['status' => 'inactive']);
                }
                break;
        }

        EmployeeLifecycleEvent::create([
            'employee_id' => $emp->id,
            'event_type' => $this->event_type,
            'effective_date' => $this->effective_date,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'reason' => $this->event_reason,
            'notes' => $this->event_notes,
            'approved_by' => auth()->id(),
        ]);

        $this->reset(['event_reason', 'event_notes']);
        $this->refreshEmployee();
        log_activity('hrm', 'lifecycle_'.$this->event_type, "Lifecycle event {$this->event_type} applied to {$emp->emp_code}", $emp);
        $this->dispatch('toast', message: 'Employee lifecycle action recorded.', type: 'success');
    }

    public function syncUserAccount(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);

        $emp = $this->employee;
        if ($emp->user) {
            $updateData = [
                'role_id' => $this->user_role_id,
                'department_id' => $emp->department_id,
            ];
            if ($this->user_password !== '') {
                $updateData['password'] = Hash::make($this->user_password);
            }
            $emp->user->update($updateData);
            $msg = 'Employee user login updated.';
        } else {
            $existing = User::where('email', $emp->email)->first();
            if ($existing) {
                $emp->update(['user_id' => $existing->id]);
                $msg = 'Linked existing user account to employee.';
            } else {
                $user = User::create([
                    'name' => $emp->full_name,
                    'email' => $emp->email,
                    'phone' => $emp->phone,
                    'role_id' => $this->user_role_id ?: Role::where('slug', 'staff')->value('id'),
                    'department_id' => $emp->department_id,
                    'user_type' => 'staff',
                    'status' => $emp->status,
                    'password' => Hash::make($this->user_password ?: 'password'),
                ]);
                $emp->update(['user_id' => $user->id]);
                $msg = 'Created ERP user login for employee.';
            }
        }

        $this->user_password = '';
        $this->refreshEmployee();
        log_activity('hrm', 'user_account_synced', "{$msg} ({$emp->emp_code})", $emp);
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleUserLoginStatus(): void
    {
        if (! $this->employee->user) {
            return;
        }
        $u = $this->employee->user;
        $u->update(['status' => $u->status === 'active' ? 'inactive' : 'active']);
        $this->refreshEmployee();
        $this->dispatch('toast', message: 'User login status toggled.', type: 'success');
    }

    private function refreshEmployee(): void
    {
        $this->employee = Employee::with([
            'department', 'designation', 'user.role', 'documents',
            'lifecycleEvents.approver', 'leaveRequests.leaveType',
            'attendances', 'payrollItems.payrollRun',
        ])->findOrFail($this->employee->id);
    }

    public function render()
    {
        return view('livewire.hrm.employee-profile', [
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
