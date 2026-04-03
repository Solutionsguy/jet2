@extends('Layout.admindashboard')

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title"> P2P Peer Management </h3>
        <button type="button" class="btn btn-gradient-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPeerModal">
            + Add New Peer
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Manage Peer Numbers</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Limits (Min - Max)</th>
                                    <th>Success Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($peers as $peer)
                                <tr>
                                    <td>{{ $peer->name }}</td>
                                    <td>{{ $peer->phone }}</td>
                                    <td>{{ number_format($peer->min_limit) }} - {{ number_format($peer->max_limit) }}</td>
                                    <td>{{ $peer->success_rate }}%</td>
                                    <td>
                                        <a href="{{ route('admin.p2p.peers.toggle', $peer->id) }}" 
                                           class="badge {{ $peer->status ? 'badge-success' : 'badge-danger' }} text-decoration-none">
                                            {{ $peer->status ? 'Online' : 'Offline' }}
                                        </a>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editPeerModal{{ $peer->id }}">Edit</button>
                                        <a href="{{ route('admin.p2p.peers.delete', $peer->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Delete this peer?')">Delete</a>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editPeerModal{{ $peer->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Peer: {{ $peer->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.p2p.peers.update', $peer->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Display Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $peer->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Phone Number</label>
                                                        <input type="text" name="phone" class="form-control" value="{{ $peer->phone }}" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Min Limit</label>
                                                            <input type="number" name="min_limit" class="form-control" value="{{ $peer->min_limit }}" required>
                                                        </div>
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Max Limit</label>
                                                            <input type="number" name="max_limit" class="form-control" value="{{ $peer->max_limit }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Average Time (Text)</label>
                                                        <input type="text" name="avg_time" class="form-control" value="{{ $peer->avg_time }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addPeerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New P2P Peer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.p2p.peers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. John M. (Verified)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="2547XXXXXXXX" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Min Limit</label>
                            <input type="number" name="min_limit" class="form-control" value="100" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Max Limit</label>
                            <input type="number" name="max_limit" class="form-control" value="50000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Peer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
