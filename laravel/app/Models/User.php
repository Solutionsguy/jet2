<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_superadmin' => 'boolean',
        'role_id' => 'integer',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'userid', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($user) {
            if ($user->isDirty('balance')) {
                $user->wallet()->update(['amount' => $user->balance]);
            }
        });
    }

    /**
     * Check if the user has a specific permission.
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->is_superadmin) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        $permissions = $this->role->permissions;

        if (is_array($permissions) && in_array('*', $permissions)) {
            return true;
        }

        return is_array($permissions) && in_array($permission, $permissions);
    }
}
