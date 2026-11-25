<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'role_id',
        'department_id',
        'user_type',
        'status',
        'last_login_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Check module-level CRUD permission via assigned role.
     * Admins with slug 'admin' or user_type 'admin' have full access unless restricted.
     */
    public function hasPermission(string $module, string $action = 'view'): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->user_type === 'admin' || $this->role?->slug === 'super-admin' || $this->role?->slug === 'admin') {
            return true;
        }

        if (! $this->role) {
            return false;
        }

        $column = match ($action) {
            'create' => 'can_create',
            'edit', 'update' => 'can_edit',
            'delete', 'destroy' => 'can_delete',
            default => 'can_view',
        };

        $permissions = $this->role->rolePermissions()->with('permission')->get();

        foreach ($permissions as $rp) {
            if ($rp->permission && $rp->permission->module === $module && $rp->{$column}) {
                return true;
            }
        }

        return false;
    }

    public function hasFeaturePermission(string $slug, string $action = 'view'): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->user_type === 'admin' || $this->role?->slug === 'super-admin' || $this->role?->slug === 'admin') {
            return true;
        }

        if (! $this->role) {
            return false;
        }

        $column = match ($action) {
            'create' => 'can_create',
            'edit', 'update' => 'can_edit',
            'delete', 'destroy' => 'can_delete',
            default => 'can_view',
        };

        return $this->role->rolePermissions()
            ->whereHas('permission', fn ($q) => $q->where('slug', $slug))
            ->where($column, true)
            ->exists();
    }
}
