@extends('Layout.usergame2')

@section('css')
<style>
    .level-container {
        max-width: 1000px;
        margin: 100px auto;
        padding: 20px;
    }
    .summary-card {
        background: linear-gradient(135deg, #ff9500 0%, #fa5e00 100%);
        border-radius: 15px;
        padding: 30px;
        color: #000;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 10px 20px rgba(250, 94, 0, 0.3);
    }
    .summary-card h2 {
        font-weight: 800;
        margin: 0;
        font-size: 2.5rem;
    }
    .summary-card p {
        margin: 0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
    }
    .summary-icon {
        font-size: 60px !important;
        opacity: 0.3;
    }
    .level-card {
        background: rgba(25, 26, 27, 0.95);
        border: 1px solid #2a2b2e;
        border-radius: 15px;
        height: 100%;
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    .level-card:hover {
        border-color: #ff9500;
    }
    .level-header {
        background: rgba(255, 149, 0, 0.1);
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 149, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .level-header h5 {
        margin: 0;
        color: #ff9500;
        font-weight: 700;
    }
    .user-count-badge {
        background: #ff9500;
        color: #000;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 800;
    }
    .level-body {
        padding: 0;
        max-height: 400px;
        overflow-y: auto;
    }
    .user-list-item {
        padding: 12px 20px;
        border-bottom: 1px solid #1a1b1d;
        display: flex;
        align-items: center;
        transition: background 0.2s;
    }
    .user-list-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .user-avatar {
        width: 35px;
        height: 35px;
        background: #333;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #ff9500;
    }
    .user-info .name {
        display: block;
        color: #fff;
        font-weight: 600;
        font-size: 14px;
    }
    .user-info .id {
        color: #666;
        font-size: 12px;
    }
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #444;
    }
    .empty-state span {
        font-size: 40px !important;
        display: block;
        margin-bottom: 10px;
    }
    @media (max-width: 768px) {
        .level-container {
            margin-top: 80px;
            padding: 15px;
        }
        .summary-card {
            padding: 20px;
        }
        .summary-card h2 {
            font-size: 1.8rem;
        }
    }
</style>
@endsection

@section('content')
<div class="level-container">
    <!-- Summary Section -->
    <div class="summary-card">
        <div>
            <p>Network Performance</p>
            <h2>{{ $users }} <span style="font-size: 1rem; font-weight: 400;">Total Players</span></h2>
        </div>
        <span class="material-symbols-outlined summary-icon">hub</span>
    </div>

    <div class="row g-4">
        <!-- Level 1 Card -->
        <div class="col-lg-4 col-md-6">
            <div class="level-card">
                <div class="level-header">
                    <h5>Level 1</h5>
                    <span class="user-count-badge">{{ count($level1) }}</span>
                </div>
                <div class="level-body">
                    @if (count($level1) > 0)
                        @foreach ($level1 as $item)
                            <div class="user-list-item">
                                <div class="user-avatar">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
                                </div>
                                <div class="user-info">
                                    <span class="name">{{ $item->name }}</span>
                                    <span class="id">ID: {{ $item->id }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <span class="material-symbols-outlined">group_off</span>
                            <p>No Level 1 Players</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Level 2 Card -->
        <div class="col-lg-4 col-md-6">
            <div class="level-card">
                @php $l2count = 0; foreach($level2 as $sub) $l2count += count($sub); @endphp
                <div class="level-header">
                    <h5>Level 2</h5>
                    <span class="user-count-badge">{{ $l2count }}</span>
                </div>
                <div class="level-body">
                    @if (count($level2) > 0)
                        @foreach ($level2 as $subitem)
                            @foreach ($subitem as $item)
                                <div class="user-list-item">
                                    <div class="user-avatar">
                                        <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
                                    </div>
                                    <div class="user-info">
                                        <span class="name">{{ $item->name }}</span>
                                        <span class="id">ID: {{ $item->id }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @else
                        <div class="empty-state">
                            <span class="material-symbols-outlined">group_off</span>
                            <p>No Level 2 Players</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Level 3 Card -->
        <div class="col-lg-4 col-md-12">
            <div class="level-card">
                @php $l3count = 0; foreach($level3 as $sub) $l3count += count($sub); @endphp
                <div class="level-header">
                    <h5>Level 3</h5>
                    <span class="user-count-badge">{{ $l3count }}</span>
                </div>
                <div class="level-body">
                    @if (count($level3) > 0)
                        @foreach ($level3 as $subitem)
                            @foreach ($subitem as $item)
                                <div class="user-list-item">
                                    <div class="user-avatar">
                                        <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
                                    </div>
                                    <div class="user-info">
                                        <span class="name">{{ $item->name }}</span>
                                        <span class="id">ID: {{ $item->id }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @else
                        <div class="empty-state">
                            <span class="material-symbols-outlined">group_off</span>
                            <p>No Level 3 Players</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
