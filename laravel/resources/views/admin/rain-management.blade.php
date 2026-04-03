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
        <!-- ... (existing stats cards) ... -->
    </div>

    <!-- Auto-Rain Settings -->
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-robot"></i> Automated Rain Settings</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="auto-rain-enabled" {{ ($autoRainSettings['enabled'] == '1') ? 'checked' : '' }}>
                <label class="form-check-label text-white" for="auto-rain-enabled">Enable Auto-Rain</label>
            </div>
        </div>
        <div class="card-body">
            <form id="auto-rain-settings-form">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Amount per Winner (KSh)</label>
                        <input type="number" class="form-control" name="amount" value="{{ $autoRainSettings['amount'] }}" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Number of Winners</label>
                        <input type="number" class="form-control" name="winners" value="{{ $autoRainSettings['winners'] }}" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Frequency (Interval)</label>
                        <select class="form-control" name="interval">
                            <option value="every_30_mins" {{ $autoRainSettings['interval'] == 'every_30_mins' ? 'selected' : '' }}>Every 30 Minutes</option>
                            <option value="hourly" {{ $autoRainSettings['interval'] == 'hourly' ? 'selected' : '' }}>Hourly</option>
                            <option value="every_2_hours" {{ $autoRainSettings['interval'] == 'every_2_hours' ? 'selected' : '' }}>Every 2 Hours</option>
                            <option value="every_6_hours" {{ $autoRainSettings['interval'] == 'every_6_hours' ? 'selected' : '' }}>Every 6 Hours</option>
                            <option value="every_12_hours" {{ $autoRainSettings['interval'] == 'every_12_hours' ? 'selected' : '' }}>Every 12 Hours</option>
                            <option value="daily" {{ $autoRainSettings['interval'] == 'daily' ? 'selected' : '' }}>Daily</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-primary" onclick="saveAutoRainSettings()">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-warning" onclick="triggerAutoRainNow()">
                                <i class="fas fa-bolt"></i> Drop Now
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Auto-rain will drop a "Support Rain" (Freebet) automatically based on the interval.
                    Last drop: <strong>{{ setting('last_auto_rain_at') ? \Carbon\Carbon::parse(setting('last_auto_rain_at'))->diffForHumans() : 'Never' }}</strong>
                </small>
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
