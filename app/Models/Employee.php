<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'join_date' => 'date',
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'resignation_date' => 'date',
            'exit_date' => 'date',
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary + (float) $this->allowances;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    public function lifecycleEvents()
    {
        return $this->hasMany(EmployeeLifecycleEvent::class)->orderByDesc('effective_date')->orderByDesc('id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class)->latest();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class)->orderByDesc('attendance_date');
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class)->latest();
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class, 'assigned_to');
    }
}
