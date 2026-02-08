@extends('Layout.admindashboard')

@section('content')
<div class="container-fluid p-4">
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Rain Management</h2>
            <p class="text-muted">Create and manage support rains</p>
        </div>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#createRainModal">
            <i class="fas fa-cloud-rain"></i> Create Support Rain
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Active Rains</h6>
                            <h3 class="mb-0" id="active-rains-count">{{ count($activeRains) }}</h3>
                        </div>
                        <i class="fas fa-cloud-rain fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Today's Rains</h6>
                            <h3 class="mb-0">{{ $todayRains }}</h3>
                        </div>
                        <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #FF9500 0%, #FFA500 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Distributed Today</h6>
                            <h3 class="mb-0">KSh {{ number_format($todayDistributed, 2) }}</h3>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Total Active Amount</h6>
                            <h3 class="mb-0" id="total-active-amount">KSh 0</h3>
                        </div>
                        <i class="fas fa-coins fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Rains Table -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-warning text-white">
            <h5 class="mb-0">Active Rains</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="active-rains-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Creator</th>
                            <th>Amount/User</th>
                            <th>Slots</th>
                            <th>Total</th>
                            <th>Claimed</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeRains as $rain)
                        <tr>
                            <td>{{ $rain->id }}</td>
                            <td>
                                @if($rain->creator && $rain->creator->isadmin)
                                    <span class="badge bg-warning">SUPPORT</span>
                                @else
                                    {{ $rain->creator->username ?? $rain->creator->name ?? 'User' }}
                                @endif
                            </td>
                            <td>KSh {{ number_format($rain->amount_per_user, 2) }}</td>
                            <td>{{ $rain->num_winners }}</td>
                            <td>KSh {{ number_format($rain->total_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-info">
                                    {{ \App\Models\RainParticipant::where('rain_id', $rain->id)->count() }}/{{ $rain->num_winners }}
                                </span>
                            </td>
                            <td>{{ $rain->created_at->diffForHumans() }}</td>
                            <td>
                                <button class="btn btn-sm btn-info me-1" onclick="viewParticipants({{ $rain->id }})" title="View Participants">
                                    <i class="fas fa-users"></i> View
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="cancelRain({{ $rain->id }})" title="Cancel Rain">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No active rains</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Rain History & Analytics Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#history">Rain History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#analytics">Analytics</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- History Tab -->
                <div class="tab-pane fade show active" id="history">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="filter-status">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="filter-type">
                                <option value="all">All Types</option>
                                <option value="admin">Support Rain</option>
                                <option value="user">User Rain</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" id="filter-date-from">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" id="filter-date-to">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" onclick="loadRainHistory()">Filter</button>
                        </div>
                    </div>
                    <div id="rain-history-container">
                        <p class="text-center text-muted">Click Filter to load history</p>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div class="tab-pane fade" id="analytics">
                    <div id="analytics-container">
                        <p class="text-center text-muted">Loading analytics...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Create Rain Modal - Will add in next file -->
@include('admin.modals.create-rain-modal')

<!-- Participants Modal - Will add in next file -->
@include('admin.modals.rain-participants-modal')

<script src="/js/admin/rain-management.js"></script>

@endsection
