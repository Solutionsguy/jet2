@extends('Layout.admindashboard')

@section('css')
<style>
    .permission-checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .permission-item {
        display: flex;
        align-items: center;
        padding: 5px;
    }
    .permission-item label {
        margin-bottom: 0;
        margin-left: 8px;
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-shield-key"></i>
            </span> Role Management
        </h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Add New Role -->
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Create New Role</h4>
                    <p class="card-description"> Define a new role and assign specific permissions. </p>
                    <form action="{{ url('manage_jet_secure/roles') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="roleName">Role Name</label>
                            <input type="text" class="form-control" id="roleName" name="name" placeholder="e.g. Manager, Support" required>
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        
                        <div class="form-group">
                            <label>Permissions</label>
                            <div class="permission-checkbox-group border mt-2">
                                @foreach($permissions as $slug => $label)
                                <div class="permission-item">
                                    <input type="checkbox" name="permissions[]" value="{{ $slug }}" id="perm_{{ $slug }}">
                                    <label for="perm_{{ $slug }}">{{ $label }}</label>
                                </div>
                                @endforeach
                            </div>
                            @error('permissions') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-gradient-primary me-2">Create Role</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Role List -->
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Existing Roles</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Slug</th>
                                    <th>Permissions Count</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td><code>{{ $role->slug }}</code></td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ is_array($role->permissions) ? count($role->permissions) : 0 }} Permissions
                                        </span>
                                    </td>
                                    <td>{{ $role->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if($role->slug !== 'superadmin')
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}">Edit</button>
                                        @else
                                        <span class="text-muted small">System Protected</span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                @if($role->slug !== 'superadmin')
                                <div class="modal fade" id="editRole{{ $role->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Role: {{ $role->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ url('manage_jet_secure/roles/'.$role->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Role Name</label>
                                                        <input type="text" class="form-control" name="name" value="{{ $role->name }}" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Permissions</label>
                                                        <div class="permission-checkbox-group border mt-2">
                                                            @foreach($permissions as $slug => $label)
                                                            <div class="permission-item">
                                                                <input type="checkbox" name="permissions[]" value="{{ $slug }}" 
                                                                    id="edit_perm_{{ $role->id }}_{{ $slug }}"
                                                                    {{ is_array($role->permissions) && in_array($slug, $role->permissions) ? 'checked' : '' }}>
                                                                <label for="edit_perm_{{ $role->id }}_{{ $slug }}">{{ $label }}</label>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-gradient-primary">Update Role</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
