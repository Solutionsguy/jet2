@extends('Layout.admindashboard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="content-wrapper">
    
    <!-- Page Header -->
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-gift"></i>
            </span> Freebet Management
        </h3>
        <nav aria-label="breadcrumb">
            <button class="btn btn-sm btn-gradient-info" data-bs-toggle="modal" data-bs-target="#addFreebetModal">
                <i class="mdi mdi-plus"></i> Add Freebet
            </button>
        </nav>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 stretch-card grid-margin">
            <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">Total Freebet</p>
                            <h4 class="mb-0">KSh {{ number_format($totalFreebet, 2) }}</h4>
                        </div>
                        <i class="mdi mdi-wallet-giftcard mdi-36px opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 stretch-card grid-margin">
            <div class="card text-white" style="background: linear-gradient(135deg, #FF9500 0%, #FFA500 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">Users with Freebet</p>
                            <h4 class="mb-0">{{ $usersWithFreebet }}</h4>
                        </div>
                        <i class="mdi mdi-account-multiple mdi-36px opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 stretch-card grid-margin">
            <div class="card text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">Added Today</p>
                            <h4 class="mb-0">KSh {{ number_format($todayAdded, 2) }}</h4>
                        </div>
                        <i class="mdi mdi-plus-circle mdi-36px opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 stretch-card grid-margin">
            <div class="card text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">Avg per User</p>
                            <h4 class="mb-0">KSh {{ $usersWithFreebet > 0 ? number_format($totalFreebet / $usersWithFreebet, 2) : '0.00' }}</h4>
                        </div>
                        <i class="mdi mdi-chart-bar mdi-36px opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent Transactions -->
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Quick Actions</h4>
                    <div class="d-grid gap-2">
                        <button class="btn btn-gradient-info" data-bs-toggle="modal" data-bs-target="#addFreebetModal">
                            <i class="mdi mdi-plus-circle"></i> Add Freebet
                        </button>
                        <button class="btn btn-gradient-danger" data-bs-toggle="modal" data-bs-target="#removeFreebetModal">
                            <i class="mdi mdi-minus-circle"></i> Remove Freebet
                        </button>
                        <button class="btn btn-gradient-success" data-bs-toggle="modal" data-bs-target="#bulkFreebetModal">
                            <i class="mdi mdi-account-multiple-plus"></i> Bulk Distribution
                        </button>
                        <button class="btn btn-gradient-primary" onclick="showStats()">
                            <i class="mdi mdi-chart-line"></i> View Statistics
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Recent Transactions</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $txn)
                                <tr>
                                    <td>{{ $txn->name ? $txn->name : 'Unknown' }}</td>
                                    <td>
                                        @if($txn->type === 'added')
                                            <label class="badge badge-success">Added</label>
                                        @elseif($txn->type === 'removed')
                                            <label class="badge badge-danger">Removed</label>
                                        @elseif($txn->type === 'expired')
                                            <label class="badge badge-warning">Expired</label>
                                        @else
                                            <label class="badge badge-info">Used</label>
                                        @endif
                                    </td>
                                    <td>KSh {{ number_format($txn->amount, 2) }}</td>
                                    <td>{{ Str::limit($txn->reason, 30) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($txn->created_at)->diffForHumans() }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewUserHistory({{ $txn->user_id }})">
                                            <i class="mdi mdi-history"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No transactions yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Panel (Initially Hidden) -->
    <div class="row" id="stats-panel" style="display: none;">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Freebet Statistics</h4>
                        <button class="btn btn-sm btn-outline-secondary" onclick="hideStats()">
                            <i class="mdi mdi-close"></i> Close
                        </button>
                    </div>
                    <div id="stats-content">
                        <p class="text-center"><i class="mdi mdi-loading mdi-spin"></i> Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Add Freebet Modal -->
@include('admin.modals.add-freebet-modal')

<!-- Remove Freebet Modal -->
@include('admin.modals.remove-freebet-modal')

<!-- Bulk Freebet Modal -->
@include('admin.modals.bulk-freebet-modal')

<!-- User History Modal -->
@include('admin.modals.user-freebet-history-modal')

<script src="/js/admin/freebet-management.js"></script>

@endsection
