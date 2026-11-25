<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['module', 'feature', 'slug', 'description'];

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }
}
