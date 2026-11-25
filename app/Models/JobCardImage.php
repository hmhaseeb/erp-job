<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardImage extends Model
{
    protected $guarded = [];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->image_path);
    }
}
