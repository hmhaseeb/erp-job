<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'location', 'manager_name', 'phone', 'is_default', 'status'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }
}
