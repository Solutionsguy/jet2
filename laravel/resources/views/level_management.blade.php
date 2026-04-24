@extends('Layout.usergame2')

@section('css')
<style>
    .level-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .level-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .level-header h2 {
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
        padding: 30px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }

    /* Stats Grid: 4 columns on Desktop, 2x2 on Mobile */
    .level-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: rgba(255,255,255,0.04);
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.03);
    }
    .stat-card .label { color: #888; font-size: 10px; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 5px; }
    .stat-card .value { color: #ff9500; font-size: 18px; font-weight: 900; display: block; }

    /* Custom Table Styles */
    .table-responsive { border-radius: 12px; overflow: hidden; }
    .custom-table {
        width: 100%;
        color: #eee;
        background: rgba(0,0,0,0.2);
        margin-bottom: 0;
    }
    .custom-table th {
        background: rgba(255, 149, 0, 0.1);
        color: #ff9500;
        padding: 12px 10px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
    }
    .custom-table td {
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255,255,255,0.02);
        font-size: 13px;
        vertical-align: middle;
    }
    .user-pill {
        background: rgba(255,255,255,0.05);
        padding: 3px 10px;
        border-radius: 15px;
        font-weight: 600;
        color: #fff;
        font-size: 12px;
    }
    
    .nav-pills-custom {
        background: rgba(0,0,0,0.3);
        padding: 4px;
        border-radius: 30px;
        display: inline-flex;
        border: 1px solid rgba(255,255,255,0.05);
        margin-bottom: 25px;
    }
    .nav-pills-custom .nav-link {
        padding: 6px 20px;
        border-radius: 25px;
        color: #888;
        font-weight: 700;
        font-size: 12px;
        border: none;
        transition: all 0.3s;
    }
    .nav-pills-custom .nav-link.active {
        background: #ff9500;
        color: #000;
        box-shadow: 0 4px 10px rgba(255,149,0,0.3);
    }

    @media (max-width: 768px) {
        .level-stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .glass-card { padding: 15px; border-radius: 16px; }
        .stat-card .value { font-size: 16px; }
        .custom-table td { font-size: 12px; padding: 10px 8px; }
    }
</style>
@endsection

@section('content')
<div class="level-container">
    <div class="level-header">
        <h2 class="mb-1">MY TEAM</h2>
        <p class="text-grey mb-0">Track your referral network levels</p>
    </div>

    <div class="glass-card">
        <!-- Stats Row -->
        <div class="level-stats-grid">
            <div class="stat-card">
                <span class="label">Total</span>
                <span class="value">{{ $users }}</span>
            </div>
            <div class="stat-card">
                <span class="label">LV 1</span>
                <span class="value">{{ count($level1) }}</span>
            </div>
            <div class="stat-card">
                <span class="label">LV 2</span>
                <span class="value">{{ count($level2) }}</span>
            </div>
            <div class="stat-card">
                <span class="label">LV 3</span>
                <span class="value">{{ count($level3) }}</span>
            </div>
        </div>

        <div class="text-center">
            <ul class="nav nav-pills-custom" id="levelTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#lv1">LV 1</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#lv2">LV 2</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#lv3">LV 3</button></li>
            </ul>
        </div>

        <div class="tab-content" id="levelTabsContent">
            <!-- Level 1 -->
            <div class="tab-pane fade show active" id="lv1">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>MEMBER</th>
                                <th>PHONE</th>
                                <th class="text-end">JOINED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($level1 as $user)
                            <tr>
                                <td><span class="user-pill">{{ Str::limit($user->name, 12) }}</span></td>
                                <td class="font-family-number text-grey">{{ $user->mobile }}</td>
                                <td class="text-grey small text-end">{{ $user->created_at->format('d M') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-grey">No members found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Level 2 -->
            <div class="tab-pane fade" id="lv2">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>MEMBER</th>
                                <th>PHONE</th>
                                <th class="text-end">JOINED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $hasData = false; @endphp
                            @foreach($level2 as $sub)
                                @foreach($sub as $user)
                                    @php $hasData = true; @endphp
                                    <tr>
                                        <td><span class="user-pill">{{ Str::limit($user->name, 12) }}</span></td>
                                        <td class="font-family-number text-grey">{{ $user->mobile }}</td>
                                        <td class="text-grey small text-end">{{ $user->created_at->format('d M') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                            @if(!$hasData)
                            <tr><td colspan="3" class="text-center py-4 text-grey">No members found</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Level 3 -->
            <div class="tab-pane fade" id="lv3">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>MEMBER</th>
                                <th>PHONE</th>
                                <th class="text-end">JOINED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $hasData = false; @endphp
                            @foreach($level3 as $sub)
                                @foreach($sub as $user)
                                    @php $hasData = true; @endphp
                                    <tr>
                                        <td><span class="user-pill">{{ Str::limit($user->name, 12) }}</span></td>
                                        <td class="font-family-number text-grey">{{ $user->mobile }}</td>
                                        <td class="text-grey small text-end">{{ $user->created_at->format('d M') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                            @if(!$hasData)
                            <tr><td colspan="3" class="text-center py-4 text-grey">No members found</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
