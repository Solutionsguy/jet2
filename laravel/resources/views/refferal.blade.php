@extends('Layout.usergame2')

@section('css')
<style>
    .referral-container {
        max-width: 800px;
        margin: 100px auto;
        padding: 20px;
    }
    .referral-card {
        background: rgba(25, 26, 27, 0.95);
        border: 1px solid #2a2b2e;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    .referral-card:hover {
        transform: translateY(-5px);
        border-color: #ff9500;
    }
    .card-icon {
        background: rgba(255, 149, 0, 0.1);
        color: #ff9500;
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    .copy-group {
        position: relative;
        background: #111;
        border-radius: 8px;
        padding: 12px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid #333;
    }
    .copy-group span {
        color: #fff;
        font-family: 'Roboto Mono', monospace;
        font-size: 14px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-right: 10px;
    }
    .copy-btn {
        background: #ff9500;
        color: #000;
        border: none;
        padding: 5px 15px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .copy-btn:hover {
        background: #ffaa33;
        transform: scale(1.05);
    }
    .referral-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .referral-header h2 {
        color: #ff9500;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .referral-header p {
        color: #888;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .referral-container {
            margin-top: 70px;
            padding: 10px;
        }
        .referral-card {
            padding: 15px;
            margin-bottom: 10px;
        }
        .referral-header h2 {
            font-size: 1.5rem;
        }
        .referral-header p {
            font-size: 13px;
        }
        .card-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
        }
        .card-icon span {
            font-size: 20px !important;
        }
        .copy-group span {
            font-size: 12px;
        }
        .copy-btn {
            padding: 4px 10px;
            font-size: 11px;
        }
    }
</style>
@section('content')
    <div class="referral-container">
        <div class="referral-header">
            <h2>Invite & Earn</h2>
            <p>Share your unique link with friends and start earning rewards together!</p>
        </div>

        <div class="stats-grid">
            <!-- Referral Code Card -->
            <div class="referral-card">
                <div class="card-icon">
                    <span class="material-symbols-outlined">qr_code_2</span>
                </div>
                <h5 class="text-white mb-3">Your Referral Code</h5>
                <div class="copy-group">
                    <span id="ref-code">{{user('id')}}</span>
                    <button class="copy-btn" onclick="copyText('ref-code', this)">COPY</button>
                </div>
            </div>

            <!-- Referral Link Card -->
            <div class="referral-card">
                <div class="card-icon">
                    <span class="material-symbols-outlined">link</span>
                </div>
                <h5 class="text-white mb-3">Sharing Link</h5>
                <div class="copy-group">
                    <span id="ref-url">{{url('register?refer='.user('id'))}}</span>
                    <button class="copy-btn" onclick="copyText('ref-url', this)">COPY</button>
                </div>
            </div>
        </div>

        <!-- How it works -->
        <div class="referral-card mt-4">
            <h5 class="text-white mb-4">How it works</h5>
            <div class="row text-center text-grey small">
                <div class="col-4">
                    <div class="mb-2 text-warning"><span class="material-symbols-outlined" style="font-size: 32px;">share</span></div>
                    <div>Share Link</div>
                </div>
                <div class="col-4">
                    <div class="mb-2 text-warning"><span class="material-symbols-outlined" style="font-size: 32px;">person_add</span></div>
                    <div>Friend Joins</div>
                </div>
                <div class="col-4">
                    <div class="mb-2 text-warning"><span class="material-symbols-outlined" style="font-size: 32px;">payments</span></div>
                    <div>Get Rewards</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function copyText(id, btn) {
        const text = document.getElementById(id).innerText;
        const elem = document.createElement('textarea');
        document.body.appendChild(elem);
        elem.value = text;
        elem.select();
        document.execCommand('copy');
        document.body.removeChild(elem);
        
        const originalText = btn.innerText;
        btn.innerText = 'COPIED!';
        btn.style.background = '#00ff00';
        
        setTimeout(() => {
            btn.innerText = originalText;
            btn.style.background = '#ff9500';
        }, 2000);
    }
</script>
@endsection