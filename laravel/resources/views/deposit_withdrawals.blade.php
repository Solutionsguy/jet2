@extends('Layout.usergame2')

@section('css')
<style>
    .history-container {
        max-width: 1000px;
        margin: 100px auto;
        padding: 20px;
    }
    .history-card {
        background: rgba(25, 26, 27, 0.95);
        border: 1px solid #2a2b2e;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        overflow: hidden;
    }
    .history-header {
        background: rgba(255, 149, 0, 0.1);
        padding: 20px 25px;
        border-bottom: 1px solid rgba(255, 149, 0, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .history-header h2 {
        margin: 0;
        color: #ff9500;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 1.5rem;
    }
    .transaction-item {
        padding: 15px 25px;
        border-bottom: 1px solid #1a1b1d;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.2s;
    }
    .transaction-item:hover {
        background: rgba(255, 255, 255, 0.03);
    }
    .txn-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .txn-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .txn-icon.credit {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    .txn-icon.debit {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    .txn-info .category {
        display: block;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        text-transform: capitalize;
    }
    .txn-info .date {
        color: #666;
        font-size: 12px;
    }
    .txn-info .remark {
        display: block;
        color: #888;
        font-size: 11px;
        margin-top: 2px;
    }
    .txn-right {
        text-align: right;
    }
    .txn-amount {
        display: block;
        font-weight: 800;
        font-size: 16px;
    }
    .txn-amount.credit { color: #28a745; }
    .txn-amount.debit { color: #dc3545; }
    
    .status-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .badge-success { background: #28a745; color: #fff; }
    .badge-warning { background: #ff9500; color: #000; }
    .badge-danger { background: #dc3545; color: #fff; }
    .badge-secondary { background: #6c757d; color: #fff; }

    .summary-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-box {
        background: rgba(255, 255, 255, 0.03);
        padding: 15px;
        border-radius: 12px;
        text-align: center;
        border: 1px solid #2a2b2e;
    }
    .stat-box .label { color: #888; font-size: 11px; text-transform: uppercase; display: block; }
    .stat-box .value { color: #fff; font-size: 18px; font-weight: 800; }

    @media (max-width: 768px) {
        .history-container { margin-top: 80px; padding: 15px; }
        .summary-stats { grid-template-columns: 1fr; gap: 10px; }
        .transaction-item { padding: 15px; }
        .txn-amount { font-size: 14px; }
        .history-header h2 { font-size: 1.2rem; }
    }
</style>
@endsection

@section('content')
<div class="history-container">
    @php
        $totalIn = $deposit->where('type', 'credit')->sum('amount');
        $totalOut = $deposit->where('type', 'debit')->sum('amount');
    @endphp

    <div class="summary-stats">
        <div class="stat-box">
            <span class="label">Total In</span>
            <span class="value text-success">KSh {{ number_format($totalIn, 2) }}</span>
        </div>
        <div class="stat-box">
            <span class="label">Total Out</span>
            <span class="value text-danger">KSh {{ number_format($totalOut, 2) }}</span>
        </div>
        <div class="stat-box">
            <span class="label">Transactions</span>
            <span class="value text-warning">{{ count($deposit) }}</span>
        </div>
    </div>

    <div class="history-card">
        <div class="history-header">
            <h2>Transaction History</h2>
            <span class="material-symbols-outlined text-grey">history</span>
        </div>

        <div class="history-body">
            @if (count($deposit) > 0)
                @foreach ($deposit as $item)
                    @php 
                        $statusData = status($item->status, 'recharge');
                        $isCredit = strtolower($item->type) == 'credit';
                    @endphp
                    <div class="transaction-item">
                        <div class="txn-left">
                            <div class="txn-icon {{ $isCredit ? 'credit' : 'debit' }}">
                                <span class="material-symbols-outlined">
                                    {{ $isCredit ? 'south_west' : 'north_east' }}
                                </span>
                            </div>
                            <div class="txn-info">
                                <span class="category">{{ $item->category }} ({{ $item->platform }})</span>
                                <span class="date">{{ dformat($item->created_at, 'd M Y, h:i A') }}</span>
                                @if($item->remark)
                                    <span class="remark">{{ $item->remark }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="txn-right">
                            <span class="txn-amount {{ $isCredit ? 'credit' : 'debit' }}">
                                {{ $isCredit ? '+' : '-' }} KSh {{ number_format($item->amount, 2) }}
                            </span>
                            <span class="status-badge badge-{{ $statusData['color'] }}">
                                {{ $statusData['name'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="p-5 text-center text-grey">
                    <span class="material-symbols-outlined" style="font-size: 48px;">receipt_long</span>
                    <p class="mt-2">No transactions found in your history.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    // No extra JS needed for this clean layout
</script>
@endsection
