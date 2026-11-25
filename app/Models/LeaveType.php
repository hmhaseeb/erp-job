<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['name', 'code', 'days_per_year', 'is_paid', 'status'];

    protected function casts(): array
    {
        return ['is_paid' => 'boolean'];
    }
}
