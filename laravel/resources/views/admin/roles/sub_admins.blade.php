@extends('Layout.admindashboard')

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-account-group"></i>
            </span> Sub-Admin Management
        </h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Add New Admin -->
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Add New Sub-Admin</h4>
                    <p class="card-description"> Create a new administrative account with a specific role. </p>
                    <form action="{{ url('manage_jet_secure/sub-admins') }}" method="POST" class="forms-sample">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adminName">Full Name</label>
                                    <input type="text" class="form-control" name="name" id="adminName" placeholder="Full Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adminEmail">Email address</label>
                                    <input type="email" class="form-control" name="email" id="adminEmail" placeholder="Email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adminMobile">Mobile Number</label>
                                    <input type="text" class="form-control" name="mobile" id="adminMobile" placeholder="e.g. 254712345678" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adminRole">Assign Role</label>
                                    <select class="form-control text-dark" name="role_id" id="adminRole" required>
                                        <option value="">Select a Role</option>
                                        @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="adminPassword">Password</label>
                            <input type="password" class="form-control" name="password" id="adminPassword" placeholder="Minimum 6 characters" required>
                        </div>
                        
                        <button type="submit" class="btn btn-gradient-primary me-2">Add Admin Account</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admin List -->
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Current Administrators</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th> Name </th>
                                    <th> Mobile/Email </th>
                                    <th> Role </th>
                                    <th> Superadmin </th>
                                    <th> Status </th>
                                    <th> Joined </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($admins as $admin)
                                <tr>
                                    <td class="py-1">
                                        <img src="{{ $admin->image ?? '/aviatoradmin/assets/images/faces/face1.jpg' }}" alt="image" />
                                        {{ $admin->name }}
                                    </td>
                                    <td>
                                        <div>{{ $admin->mobile }}</div>
                                        <div class="small text-muted">{{ $admin->email }}</div>
                                    </td>
                                    <td>
                                        <label class="badge badge-gradient-info">{{ $admin->role->name ?? 'No Role' }}</label>
                                    </td>
                                    <td>
                                        @if($admin->is_superadmin)
                                            <span class="text-success"><i class="mdi mdi-check-circle"></i> Yes</span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <label class="badge badge-{{ $admin->status == '1' ? 'success' : 'danger' }}">
                                            {{ $admin->status == '1' ? 'Active' : 'Inactive' }}
                                        </label>
                                    </td>
                                    <td> {{ $admin->created_at->format('M d, Y') }} </td>
                                </tr>
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
