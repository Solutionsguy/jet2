<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminRoleController extends Controller
{
    public function rolesIndex()
    {
        $roles = Role::all();
        $permissions = [
            'view_analytics' => 'View Dashboard Analytics',
            'view_users' => 'View User List',
            'edit_users' => 'Edit User Balances/Passwords',
            'manage_deposits' => 'Approve/Reject Deposits',
            'manage_withdrawals' => 'Approve/Reject Withdrawals',
            'game_settings' => 'Edit Game Rules/Multipliers',
            'manage_rain' => 'Create/Manage Rain',
            'manage_freebets' => 'Distribute Freebets',
            'manage_p2p' => 'Manage P2P Peers',
            'manage_chat' => 'Manage Chat (Approve/Delete)',
            'full_access' => 'Full System Access (Superadmin)'
        ];

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function roleStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array'
        ]);

        Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'permissions' => $request->permissions
        ]);

        return back()->with('success', 'Role created successfully!');
    }

    public function roleUpdate(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'permissions' => 'required|array'
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'permissions' => $request->permissions
        ]);

        return back()->with('success', 'Role updated successfully!');
    }

    public function subAdminsIndex()
    {
        $admins = User::where('isadmin', '1')->with('role')->get();
        $roles = Role::all();
        
        return view('admin.roles.sub_admins', compact('admins', 'roles'));
    }

    public function subAdminStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'required|unique:users,mobile',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'isadmin' => '1',
            'is_superadmin' => false,
            'status' => '1',
            'currency' => 'KES',
            'country' => 'KE'
        ]);

        return back()->with('success', 'Sub-admin created successfully!');
    }
}
