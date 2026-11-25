<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLifecycleEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
