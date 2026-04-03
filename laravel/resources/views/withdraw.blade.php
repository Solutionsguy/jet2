@extends('Layout.usergame2')

@section('css')
<style>
    .withdraw-container {
        max-width: 600px;
        margin: 100px auto;
        padding: 20px;
    }
    .withdraw-card {
        background: rgba(25, 26, 27, 0.95);
        border: 1px solid #2a2b2e;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .method-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 30px;
    }
    .method-btn {
        background: #1a1b1d;
        border: 2px solid #333;
        border-radius: 15px;
        padding: 20px 10px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }
    .method-btn.active {
        border-color: #ff9500;
        background: rgba(255, 149, 0, 0.05);
    }
    .method-btn i {
        font-size: 32px;
        margin-bottom: 10px;
        display: block;
    }
    .method-btn span {
        display: block;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .balance-summary {
        background: rgba(255, 149, 0, 0.1);
        border: 1px dashed #ff9500;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        margin-bottom: 25px;
    }
    .balance-summary .label {
        color: #888;
        font-size: 12px;
        text-transform: uppercase;
        display: block;
    }
    .balance-summary .amount {
        color: #ff9500;
        font-size: 24px;
        font-weight: 800;
    }

    /* Ultra-Minimalist Input Style - Synchronized */
    .input-field-row {
        display: flex !important;
        align-items: center !important;
        border: none !important;
        border-bottom: 1px solid #333 !important;
        margin-bottom: 25px !important;
        padding: 5px 0 !important;
        transition: border-color 0.3s ease;
        width: 100% !important;
        background: transparent !important;
    }
    .input-field-row:focus-within {
        border-bottom-color: #F59E0B !important;
    }
    .field-icon {
        color: #666 !important;
        margin-right: 15px !important;
        display: flex !important;
        align-items: center !important;
        font-size: 20px !important;
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
    }
    .input-field-row:focus-within .field-icon {
        color: #F59E0B !important;
    }
    .field-input {
        background: transparent !important;
        border: none !important;
        color: #fff !important;
        font-size: 16px !important;
        padding: 8px 0 !important;
        width: 100% !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .field-input::placeholder {
        color: #444 !important;
    }

    .withdraw-btn {
        width: 100%;
        background: #ff9500;
        color: #000;
        border: none;
        padding: 15px;
        border-radius: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.2s;
    }
    .withdraw-btn:hover {
        background: #fa5e00;
        transform: translateY(-2px);
    }
    .limit-badge {
        font-size: 11px;
        color: #666;
        text-align: center;
        margin-top: 15px;
    }
    .limit-badge strong { color: #ff9500; }
    
    .quick-amounts {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        justify-content: center;
    }
    .quick-amount-btn {
        background: #222;
        border: 1px solid #333;
        color: #fff;
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.2s;
    }
    .quick-amount-btn:hover, .quick-amount-btn.active {
        border-color: #ff9500;
        color: #ff9500;
    }

    /* Radar Animation Area */
    .radar-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: rgba(0, 210, 91, 0.05);
        overflow: hidden;
        border: 1px solid rgba(0, 210, 91, 0.2);
    }
    .radar-circle {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 0; height: 0; border: 1px solid #00d25b; border-radius: 50%;
        opacity: 0.5; animation: radar-pulse 3s infinite linear;
    }
    .radar-circle.delay-1 { animation-delay: 1s; }
    .radar-circle.delay-2 { animation-delay: 2s; }
    .radar-scanner {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: conic-gradient(from 0deg, rgba(0, 210, 91, 0.4) 0deg, transparent 90deg);
        animation: radar-rotate 4s infinite linear; transform-origin: center;
    }
    @keyframes radar-pulse { from { width: 0; height: 0; opacity: 0.5; } to { width: 100%; height: 100%; opacity: 0; } }
    @keyframes radar-rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    @media (max-width: 768px) {
        .withdraw-container { margin-top: 80px; padding: 15px; }
        .withdraw-card { padding: 20px; }
        .method-btn { padding: 15px 5px; }
        .method-btn i { font-size: 24px; }
        .method-btn span { font-size: 12px; }
    }
</style>
@endsection

@section('content')
<div class="withdraw-container">
    <div class="text-center mb-4">
        <div class="pay-tabs d-inline-flex bg-dark p-1 rounded-pill" style="border: 1px solid #333;">
            <a href="/deposit" class="btn rounded-pill px-4 text-grey" style="font-size: 14px; font-weight: 700;">DEPOSIT</a>
            <a href="#" class="btn rounded-pill px-4 bg-warning text-black" style="font-size: 14px; font-weight: 700;">WITHDRAW</a>
        </div>
    </div>

    <div class="withdraw-card">
        <!-- Balance Summary -->
        <div class="balance-summary">
            <span class="label">Current Balance</span>
            <span class="amount">KSh {{ number_format(\App\Models\Wallet::where('userid', user('id'))->first()->amount ?? 0, 2) }}</span>
        </div>

        <!-- Method Selector -->
        <div class="method-selector">
            <div class="method-btn active" id="btn_mpesa_tab" onclick="switchWithdraw('mpesa')">
                <i class="material-symbols-outlined">smartphone</i>
                <span>M-Pesa</span>
            </div>
            <div class="method-btn" id="btn_p2p_tab" onclick="switchWithdraw('p2p')">
                <i class="material-symbols-outlined">sync_alt</i>
                <span>P2P Peer</span>
            </div>
        </div>

        <!-- M-Pesa Form -->
        <div id="mpesa_withdraw" class="withdraw-section">
            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">call</span>
                </span>
                <input type="text" class="field-input" id="mpesa_withdraw_phone" placeholder="2547XXXXXXXX" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">payments</span>
                </span>
                <input type="number" class="field-input" id="mpesa_withdraw_amount" placeholder="Amount (KSh)" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <div class="quick-amounts">
                <button class="quick-amount-btn" onclick="$('#mpesa_withdraw_amount').val('500')">500</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_withdraw_amount').val('1000')">1000</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_withdraw_amount').val('2000')">2000</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_withdraw_amount').val('5000')">5000</button>
            </div>

            <button class="withdraw-btn" id="mpesa_withdraw_btn" onclick="initiateMpesaWithdraw()">Withdraw via M-Pesa</button>
            
            <div class="limit-badge">Minimum Withdrawal: <strong>KSh {{setting('min_withdraw')}}</strong></div>
        </div>

        <!-- P2P Form -->
        <div id="p2p_withdraw" class="withdraw-section" style="display: none;">
            <div id="p2p_initial_form">
                <div class="input-field-row">
                    <span class="field-icon">
                        <span class="material-symbols-outlined">payments</span>
                    </span>
                    <input type="number" class="field-input" id="p2p_withdraw_amount" placeholder="Amount to withdraw" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>

                <div class="quick-amounts">
                    <button class="quick-amount-btn" onclick="$('#p2p_withdraw_amount').val('1000')">1000</button>
                    <button class="quick-amount-btn" onclick="$('#p2p_withdraw_amount').val('2000')">2000</button>
                    <button class="quick-amount-btn" onclick="$('#p2p_withdraw_amount').val('5000')">5000</button>
                </div>

                <button class="withdraw-btn" onclick="startP2PSearch()">Find Peer & Withdraw</button>
            </div>

            <!-- Radar Animation Area -->
            <div id="p2p_searching_area" style="display: none;" class="text-center py-2">
                <div class="radar-container">
                    <div class="radar-circle"></div>
                    <div class="radar-circle delay-1"></div>
                    <div class="radar-circle delay-2"></div>
                    <div class="radar-scanner"></div>
                </div>
                <h5 id="p2p_status_text" class="text-white">Searching for active peers...</h5>
                <p class="text-grey small" id="p2p_sub_status">Connecting to liquidity network</p>
                <button class="btn btn-sm btn-outline-danger mt-3" onclick="cancelP2PSearch()">Cancel Search</button>
            </div>

            <!-- Matched Peer Area -->
            <div id="p2p_matched_area" style="display: none;">
                <div class="matched-card p-3 rounded-4 mb-3 border border-success bg-dark">
                    <div class="d-flex align-items-center mb-3">
                        <img src="/images/avtar/av-1.png" width="50" class="rounded-circle border border-success me-3">
                        <div>
                            <h6 class="mb-0 text-success" id="matched_peer_name">Matched: John M.</h6>
                            <small class="text-grey">Verified Liquidity Provider</small>
                        </div>
                    </div>
                    <div class="bg-black p-3 rounded border border-secondary text-center mb-3">
                        <small class="text-grey d-block mb-1 uppercase">Peer M-Pesa Number</small>
                        <code class="h4 text-warning" id="matched_peer_phone">254700000000</code>
                    </div>
                    <button class="btn btn-sm btn-warning w-100" onclick="copyToClipboard('#matched_peer_phone')">Copy Phone Number</button>
                </div>
                <button class="btn btn-sm btn-outline-secondary w-100" onclick="resetP2P()">New Withdrawal</button>
            </div>
        </div>

        <!-- Global Status Messages -->
        <div id="mpesa_withdraw_status" style="display: none;" class="mt-3">
            <div id="mpesa_withdraw_loading" class="alert alert-info py-2 small">
                <i class="mdi mdi-loading mdi-spin me-1"></i> Processing request...
            </div>
            <div id="mpesa_withdraw_success" class="alert alert-success py-2 small">
                <i class="mdi mdi-check-circle me-1"></i> Withdrawal initiated successfully!
            </div>
            <div id="mpesa_withdraw_error" class="alert alert-danger py-2 small">
                <i class="mdi mdi-alert-circle me-1"></i> <span id="mpesa_withdraw_error_msg"></span>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="mpesa_min_withdraw" value="{{setting('min_withdraw')}}">
<input type="hidden" id="mpesa_wallet_balance" value="{{ \App\Models\Wallet::where('userid', user('id'))->first()->amount ?? 0 }}">

@endsection

@section('js')
    <script src="{{ url('user/mpesa-withdraw.js') }}"></script>
    <script src="{{ url('user/p2p-withdraw.js') }}"></script>
    <script>
        function switchWithdraw(type) {
            $('.withdraw-section').hide();
            $('.method-btn').removeClass('active');
            
            if (type === 'mpesa') {
                $('#mpesa_withdraw').show();
                $('#btn_mpesa_tab').addClass('active');
            } else if (type === 'p2p') {
                $('#p2p_withdraw').show();
                $('#btn_p2p_tab').addClass('active');
            }
        }

        function copyToClipboard(element) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val($(element).text()).select();
            document.execCommand("copy");
            $temp.remove();
            toastr.success("Copied to clipboard!");
        }
    </script>
@endsection
