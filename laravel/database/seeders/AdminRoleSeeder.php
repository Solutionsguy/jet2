<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create Superadmin Role
        $superadminRole = Role::updateOrCreate(
            ['slug' => 'superadmin'],
            [
                'name' => 'Superadmin',
                'permissions' => ['*'] // Full access wildcard
            ]
        );

        // 2. Create standard roles (Manager/Support) as placeholders
        Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'permissions' => ['view_users', 'view_analytics', 'manage_withdrawals', 'manage_rain']
            ]
        );

        Role::updateOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Support',
                'permissions' => ['view_users', 'manage_rain']
            ]
        );

        // 3. Find current admins and promote them
        $admins = User::where('isadmin', '1')->get();
        
        foreach ($admins as $admin) {
            $admin->update([
                'role_id' => $superadminRole->id,
                'is_superadmin' => true
            ]);
        }
        
        echo "✅ Roles created and existing admins promoted to Superadmin.\n";
    }
}
