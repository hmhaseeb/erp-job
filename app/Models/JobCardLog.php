<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardLog extends Model
{
    protected $guarded = [];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function logger()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
