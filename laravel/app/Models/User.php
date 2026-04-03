<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function role()
    {
        return $this->belongsTo(Role::class);
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
