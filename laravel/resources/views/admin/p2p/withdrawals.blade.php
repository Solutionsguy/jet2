@extends('Layout.admindashboard')

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title"> P2P Withdrawal History </h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">P2P Requests</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>User</th>
                                    <th>Matched Peer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $row)
                                <tr>
                                    <td>{{ $row->reference }}</td>
                                    <td>
                                        {{ $row->user->name ?? 'Deleted' }} <br>
                                        <small class="text-muted">{{ $row->user->mobile ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if($row->peer)
                                            {{ $row->peer->name }} <br>
                                            <small class="text-muted">{{ $row->peer->phone }}</small>
                                        @else
                                            <span class="text-danger">None</span>
                                        @endif
                                    </td>
                                    <td>KSh {{ number_format($row->amount, 2) }}</td>
                                    <td>
                                        @php
                                            $badgeClass = [
                                                'searching' => 'badge-info',
                                                'matched' => 'badge-warning',
                                                'completed' => 'badge-success',
                                                'failed' => 'badge-danger',
                                                'cancelled' => 'badge-secondary',
                                            ][$row->status] ?? 'badge-dark';
                                        @endphp
                                        <label class="badge {{ $badgeClass }}">{{ strtoupper($row->status) }}</label>
                                    </td>
                                    <td>{{ $row->created_at->format('d M Y, H:i') }}</td>
                                    <td>
                                        @if($row->status == 'matched' || $row->status == 'searching')
                                            <a href="{{ route('admin.p2p.withdrawals.approve', $row->id) }}" 
                                               class="btn btn-sm btn-success" 
                                               onclick="return confirm('Mark this withdrawal as completed?')">Approve</a>
                                            
                                            <a href="{{ route('admin.p2p.withdrawals.reject', $row->id) }}" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Reject this withdrawal and refund user?')">Reject</a>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Processed</button>
                                        @endif
                                    </td>
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
