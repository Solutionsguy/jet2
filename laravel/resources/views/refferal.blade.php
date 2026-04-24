@extends('Layout.usergame2')

@section('css')
<style>
    .referral-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .referral-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .referral-header h2 {
        color: #fff;
        font-weight: 900;
        letter-spacing: 1px;
    }
    
    .referral-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.03) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .card-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 149, 0, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: #ff9500;
    }
    .card-icon span { font-size: 32px; }

    .copy-group {
        display: flex;
        align-items: center;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 10px 15px;
        margin-bottom: 20px;
    }
    #ref-url {
        color: #888;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        font-weight: 600;
    }
    .copy-btn {
        background: #ff9500;
        color: #000;
        border: none;
        padding: 5px 15px;
        border-radius: 6px;
        font-weight: 800;
        font-size: 11px;
        margin-left: 10px;
        transition: all 0.2s;
    }
    .copy-btn:hover { background: #fff; }

    .theme-btn {
        width: 100%;
        background: linear-gradient(135deg, #ff9500 0%, #ff5e00 100%);
        border: none;
        color: #000;
        font-weight: 900;
        text-transform: uppercase;
        padding: 15px;
        border-radius: 12px;
        letter-spacing: 1px;
        box-shadow: 0 8px 20px rgba(255, 94, 0, 0.2);
        transition: all 0.3s ease;
    }
    .theme-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(255, 94, 0, 0.4); }

    .promo-box {
        background: linear-gradient(to right, rgba(255, 149, 0, 0.1), transparent);
        border-left: 4px solid #ff9500;
        padding: 20px;
        border-radius: 0 15px 15px 0;
        margin-top: 30px;
    }
    .promo-box h4 { color: #fff; font-weight: 800; margin-bottom: 5px; }
    .promo-box p { color: #888; margin-bottom: 0; font-size: 14px; }
</style>
@endsection

@section('content')
<div class="referral-container py-5">
    <div class="referral-header">
        <h2>INVITE & EARN</h2>
        <p class="text-grey">Grow your network and earn commissions on every bet your friends make</p>
    </div>

    <div class="referral-grid">
        <!-- Commission Card -->
        <div class="glass-card text-center">
            <div class="card-icon">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <h3 class="text-white fw-900 mb-1">20%</h3>
            <p class="text-grey uppercase small fw-700 mb-4">Lifetime Commission</p>
            <p class="text-secondary">You receive a percentage of the house edge for every game played by your referrals, paid instantly to your wallet.</p>
        </div>

        <!-- Sharing Link Card -->
        <div class="glass-card">
            <div class="card-icon">
                <span class="material-symbols-outlined">link</span>
            </div>
            <h5 class="text-white text-center mb-3">Your Referral Link</h5>
            <div class="copy-group mb-3">
                <span id="ref-url">{{url('register?refer='.user('id'))}}</span>
                <button class="copy-btn" onclick="copyText('ref-url', this)">COPY</button>
            </div>
            <button class="theme-btn" onclick="shareJetMtaa()">
                <span class="material-symbols-outlined f-18 align-middle me-1">share</span> 
                SHARE LINK
            </button>
        </div>
    </div>

    <div class="glass-card">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="promo-box">
                    <h4>Earn Passive Income</h4>
                    <p>There is no limit to how many friends you can invite. The more they play, the more you earn. Track your earnings in the Level Management tab.</p>
                </div>
            </div>
            <div class="col-lg-4 text-center mt-4 mt-lg-0">
                <a href="/level-management" class="theme-btn d-inline-block w-auto px-5">View My Team</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function copyText(elementId, btn) {
        var text = document.getElementById(elementId).innerText;
        var elem = document.createElement("textarea");
        document.body.appendChild(elem);
        elem.value = text;
        elem.select();
        document.execCommand("copy");
        document.body.removeChild(elem);
        
        var originalText = btn.innerText;
        btn.innerText = 'COPIED!';
        btn.style.background = '#00ff88';
        
        setTimeout(() => {
            btn.innerText = originalText;
            btn.style.background = '#ff9500';
        }, 2000);
    }

    async function shareJetMtaa() {
        const shareData = {
            title: 'JetMtaa - Win Big Today!',
            text: 'Hey! Join me on JetMtaa and let\'s win together. Get 100% bonus on your first deposit! 🚀',
            url: document.getElementById('ref-url').innerText
        };

        try {
            if (navigator.share) {
                await navigator.share(shareData);
            } else {
                const copyBtn = document.querySelector('.copy-btn');
                copyText('ref-url', copyBtn);
                toastr.info('Link copied to clipboard');
            }
        } catch (err) {
            console.error('Error sharing:', err);
        }
    }
</script>
@endsection
