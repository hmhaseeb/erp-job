<?php

namespace App\Livewire\Hrm;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Leave Management')]
class LeaveManager extends Component
{
    public bool $showLeaveModal = false;
    public ?int $employee_id = null;
    public ?int $leave_type_id = null;
    public string $start_date = '';
    public string $end_date = '';
    public string $reason = '';

    public string $lt_name = '';
    public string $lt_code = '';
    public int $lt_days = 15;
    public bool $lt_paid = true;

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->toDateString();
    }

    public function applyLeave(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'create'), 403);
        $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $days = Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
        LeaveRequest::create([
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_days' => $days,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        $this->showLeaveModal = false;
        $this->reset(['employee_id', 'leave_type_id', 'reason']);
        log_activity('hrm', 'leave_applied', "Leave request submitted for {$days} day(s).");
        $this->dispatch('toast', message: 'Leave request submitted.', type: 'success');
    }

    public function updateStatus(int $id, string $status): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        $leave = LeaveRequest::findOrFail($id);
        $leave->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        log_activity('hrm', 'leave_'.$status, "Leave #{$leave->id} marked {$status}", $leave);
        $this->dispatch('toast', message: "Leave request {$status}.", type: 'success');
    }

    public function addLeaveType(): void
    {
        $this->validate([
            'lt_name' => ['required', 'string', 'max:80', 'unique:leave_types,name'],
            'lt_code' => ['required', 'string', 'max:20', 'unique:leave_types,code'],
            'lt_days' => ['required', 'integer', 'min:1'],
        ]);
        LeaveType::create([
            'name' => $this->lt_name,
            'code' => strtoupper($this->lt_code),
            'days_per_year' => $this->lt_days,
            'is_paid' => $this->lt_paid,
            'status' => 'active',
        ]);
        $this->reset(['lt_name', 'lt_code']);
        $this->lt_days = 15;
        $this->dispatch('toast', message: 'Leave type added.', type: 'success');
    }

    public function render()
    {
        return view('livewire.hrm.leave-manager', [
            'leaves' => LeaveRequest::with(['employee.department', 'leaveType', 'approver'])->latest()->get(),
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'employees' => Employee::where('status', 'active')->orderBy('first_name')->get(),
        ]);
    }
}
