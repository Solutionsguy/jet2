@extends('Layout.usergame2')

@section('css')
<style>
    .history-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .history-header {
        text-align: center;
        margin-bottom: 25px;
    }
    .history-header h2 {
        color: #fff;
        font-weight: 900;
        letter-spacing: 1px;
    }
    
    .glass-card {
        background: rgba(255, 255, 255, 0.03) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 0;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }

    /* Compact Table for Mobile */
    .table-responsive { border-radius: 15px; overflow: hidden; }
    .history-table {
        width: 100%;
        background: rgba(0,0,0,0.2);
        color: #eee;
        margin-bottom: 0;
    }
    .history-table th {
        background: rgba(255, 149, 0, 0.1);
        color: #ff9500;
        padding: 12px 10px;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
    }
    .history-table td {
        padding: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.02);
        vertical-align: middle;
        font-size: 13px;
    }
    
    .type-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .type-icon span { font-size: 18px; }
    .type-credit { background: rgba(0, 255, 136, 0.1); color: #00ff88; }
    .type-debit { background: rgba(255, 50, 50, 0.1); color: #ff3232; }

    .status-badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .badge-success { background: rgba(0, 255, 136, 0.2); color: #00ff88; }
    .badge-warning { background: rgba(255, 149, 0, 0.2); color: #ff9500; }
    .badge-danger { background: rgba(255, 50, 50, 0.2); color: #ff3232; }

    .amount-text { font-family: 'Roboto', sans-serif; font-weight: 900; font-size: 14px; }

    @media (max-width: 768px) {
        .history-table td { padding: 8px 5px; }
        .type-icon { width: 25px; height: 25px; }
        .type-icon span { font-size: 14px; }
        .amount-text { font-size: 12px; }
        .history-header h2 { font-size: 18px !important; }
    }
</style>
@endsection

@section('content')
<div class="history-container">
    <div class="history-header">
        <h2 class="mb-1">HISTORY</h2>
        <p class="text-grey mb-0 small">Transaction logs</p>
    </div>

    <div class="nav-tabs-wrapper mb-3 text-center">
        <div class="auth-tabs-container">
            <a href="/deposit" class="auth-tab-btn">DEPOSIT</a>
            <a href="/withdraw" class="auth-tab-btn">WITHDRAW</a>
            <a href="#" class="auth-tab-btn active">HISTORY</a>
        </div>
    </div>

    <div class="glass-card">
        <div class="table-responsive">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>TYP</th>
                        <th>DETAILS</th>
                        <th>AMOUNT</th>
                        <th class="text-end">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deposit as $item)
                        @php
                            $st = status($item->status, $item->category);
                        @endphp
                        <tr>
                            <td>
                                <div class="type-icon {{ $item->type == 'credit' ? 'type-credit' : 'type-debit' }}">
                                    <span class="material-symbols-outlined">
                                        {{ $item->type == 'credit' ? 'south_west' : 'north_east' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="text-white fw-700 x-small">{{ strtoupper($item->category) }}</div>
                                <div class="text-grey x-small opacity-50">{{ $item->created_at->format('d M, H:i') }}</div>
                            </td>
                            <td>
                                <span class="amount-text {{ $item->type == 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $item->type == 'credit' ? '+' : '-' }}{{ number_format($item->amount, 0) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="status-badge badge-{{ $st['color'] }}">
                                    {{ $st['name'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <p class="text-grey mb-0">No transactions found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
