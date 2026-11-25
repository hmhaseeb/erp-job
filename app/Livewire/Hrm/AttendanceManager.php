<?php

namespace App\Livewire\Hrm;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Attendance Management')]
class AttendanceManager extends Component
{
    public string $date = '';
    public array $entries = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadEntries();
    }

    public function updatedDate(): void
    {
        $this->loadEntries();
    }

    public function loadEntries(): void
    {
        $existing = Attendance::whereDate('attendance_date', $this->date)->get()->keyBy('employee_id');
        $this->entries = [];
        foreach (Employee::where('status', 'active')->orderBy('first_name')->get() as $emp) {
            $att = $existing->get($emp->id);
            $this->entries[$emp->id] = [
                'status' => $att?->status ?? 'present',
                'check_in' => $att?->check_in ? substr($att->check_in, 0, 5) : '09:00',
                'check_out' => $att?->check_out ? substr($att->check_out, 0, 5) : '18:00',
                'notes' => (string) ($att?->notes ?? ''),
            ];
        }
    }

    public function markAllPresent(): void
    {
        foreach ($this->entries as $empId => $row) {
            $this->entries[$empId]['status'] = 'present';
            $this->entries[$empId]['check_in'] = '09:00';
            $this->entries[$empId]['check_out'] = '18:00';
        }
    }

    public function saveAttendance(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        foreach ($this->entries as $empId => $row) {
            $hours = 0;
            if (! empty($row['check_in']) && ! empty($row['check_out']) && ! in_array($row['status'], ['absent', 'on_leave'])) {
                try {
                    $in = Carbon::parse($this->date.' '.$row['check_in']);
                    $out = Carbon::parse($this->date.' '.$row['check_out']);
                    $hours = max(0, round($in->diffInMinutes($out, false) / 60, 2));
                } catch (\Throwable $e) {
                    $hours = 0;
                }
            }

            Attendance::updateOrCreate(
                ['employee_id' => $empId, 'attendance_date' => $this->date],
                [
                    'status' => $row['status'],
                    'check_in' => in_array($row['status'], ['absent', 'on_leave']) ? null : $row['check_in'],
                    'check_out' => in_array($row['status'], ['absent', 'on_leave']) ? null : $row['check_out'],
                    'work_hours' => $hours,
                    'notes' => $row['notes'] ?? null,
                ]
            );
        }

        log_activity('hrm', 'attendance_saved', "Saved daily attendance register for {$this->date}");
        $this->dispatch('toast', message: "Attendance saved for {$this->date}.", type: 'success');
    }

    public function render()
    {
        $employees = Employee::with('department')->where('status', 'active')->orderBy('first_name')->get();
        $summary = [
            'present' => collect($this->entries)->where('status', 'present')->count(),
            'late' => collect($this->entries)->where('status', 'late')->count(),
            'absent' => collect($this->entries)->where('status', 'absent')->count(),
            'remote' => collect($this->entries)->where('status', 'remote')->count(),
        ];

        return view('livewire.hrm.attendance-manager', [
            'employees' => $employees,
            'summary' => $summary,
        ]);
    }
}
