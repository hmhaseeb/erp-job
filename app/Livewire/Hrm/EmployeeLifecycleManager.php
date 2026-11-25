<?php

namespace App\Livewire\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Employee Lifecycle Management')]
class EmployeeLifecycleManager extends Component
{
    public string $stageFilter = '';
    public ?int $selectedEmployeeId = null;
    public string $event_type = 'confirmation';
    public ?string $effective_date = null;
    public ?int $department_id = null;
    public ?int $designation_id = null;
    public float $basic_salary = 0;
    public float $allowances = 0;
    public string $reason = '';

    public function mount(): void
    {
        $this->effective_date = now()->toDateString();
    }

    public function updatedSelectedEmployeeId($id): void
    {
        $emp = Employee::find($id);
        if ($emp) {
            $this->department_id = $emp->department_id;
            $this->designation_id = $emp->designation_id;
            $this->basic_salary = (float) $emp->basic_salary;
            $this->allowances = (float) $emp->allowances;
        }
    }

    public function submitTransition(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        $this->validate([
            'selectedEmployeeId' => ['required', 'exists:employees,id'],
            'event_type' => ['required', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $emp = Employee::findOrFail($this->selectedEmployeeId);
        $oldVal = $emp->lifecycle_status;
        $newVal = $this->event_type;

        if ($this->event_type === 'confirmation') {
            $emp->update(['lifecycle_status' => 'confirmed', 'confirmation_date' => $this->effective_date, 'status' => 'active']);
            $newVal = 'confirmed';
        } elseif ($this->event_type === 'probation') {
            $emp->update(['lifecycle_status' => 'probation', 'probation_end_date' => now()->parse($this->effective_date)->addMonths(3)->toDateString()]);
            $newVal = 'probation';
        } elseif (in_array($this->event_type, ['assignment', 'transfer', 'promotion'])) {
            $oldVal = ($emp->department?->name ?? 'None').' / '.($emp->designation?->name ?? 'None');
            $emp->update([
                'department_id' => $this->department_id ?: $emp->department_id,
                'designation_id' => $this->designation_id ?: $emp->designation_id,
                'basic_salary' => $this->basic_salary > 0 ? $this->basic_salary : $emp->basic_salary,
            ]);
            $emp->refresh();
            $newVal = ($emp->department?->name ?? 'None').' / '.($emp->designation?->name ?? 'None');
        } elseif ($this->event_type === 'salary_revision') {
            $oldVal = format_currency($emp->gross_salary);
            $emp->update(['basic_salary' => $this->basic_salary, 'allowances' => $this->allowances]);
            $newVal = format_currency($this->basic_salary + $this->allowances);
        } elseif (in_array($this->event_type, ['resignation', 'termination', 'exit'])) {
            $statusMap = ['resignation' => 'resigned', 'termination' => 'terminated', 'exit' => 'exited'];
            $newVal = $statusMap[$this->event_type];
            $emp->update([
                'lifecycle_status' => $newVal,
                'resignation_date' => $this->event_type === 'resignation' ? $this->effective_date : $emp->resignation_date,
                'exit_date' => in_array($this->event_type, ['termination', 'exit']) ? $this->effective_date : $emp->exit_date,
                'status' => $this->event_type === 'resignation' ? $emp->status : 'inactive',
            ]);
        }

        EmployeeLifecycleEvent::create([
            'employee_id' => $emp->id,
            'event_type' => $this->event_type,
            'effective_date' => $this->effective_date,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'reason' => $this->reason,
            'approved_by' => auth()->id(),
        ]);

        $this->reason = '';
        log_activity('hrm', 'lifecycle_transition', "Recorded {$this->event_type} for {$emp->full_name}", $emp);
        $this->dispatch('toast', message: 'Lifecycle transition recorded.', type: 'success');
    }

    public function render()
    {
        $counts = [
            'onboarding' => Employee::where('lifecycle_status', 'onboarding')->count(),
            'probation' => Employee::where('lifecycle_status', 'probation')->count(),
            'confirmed' => Employee::where('lifecycle_status', 'confirmed')->count(),
            'resigned' => Employee::where('lifecycle_status', 'resigned')->count(),
            'exited' => Employee::whereIn('lifecycle_status', ['terminated', 'exited'])->count(),
        ];

        $events = EmployeeLifecycleEvent::with(['employee.department', 'employee.designation', 'approver'])
            ->when($this->stageFilter, fn ($q) => $q->where('event_type', $this->stageFilter))
            ->latest('effective_date')
            ->latest('id')
            ->limit(40)
            ->get();

        return view('livewire.hrm.employee-lifecycle-manager', [
            'counts' => $counts,
            'events' => $events,
            'employees' => Employee::orderBy('first_name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
        ]);
    }
}
