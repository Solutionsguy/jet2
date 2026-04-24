@extends('Layout.usergame2')

@section('css')
<style>
    .withdraw-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .withdraw-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .withdraw-header h2 {
        color: #fff;
        font-weight: 900;
        letter-spacing: 1px;
    }
    
    .nav-tabs-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }
    .auth-tabs-container {
        background: rgba(0,0,0,0.4);
        padding: 5px;
        border-radius: 30px;
        display: inline-flex;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .auth-tab-btn {
        padding: 8px 35px;
        border-radius: 25px;
        color: #888;
        font-weight: 700;
        font-size: 13px;
        transition: all 0.3s ease;
        border: none;
        background: transparent;
        text-decoration: none !important;
    }
    .auth-tab-btn.active {
        background: #ff9500;
        color: #000;
        box-shadow: 0 4px 12px rgba(255, 149, 0, 0.3);
    }

    /* Method Selection Grid */
    .method-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .payment-method-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .payment-method-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.05);
        border-color: #ff9500;
    }
    .payment-method-card.active {
        border-color: #ff9500;
        background: rgba(255, 149, 0, 0.05);
        box-shadow: 0 0 20px rgba(255, 149, 0, 0.2);
    }
    .method-thumb {
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    .method-thumb i {
        font-size: 40px;
        color: #ff9500;
    }
    .method-label {
        color: #fff;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
    }

    /* Glassmorphism Form Card */
    .glass-card {
        background: rgba(255, 255, 255, 0.03) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }

    /* Input Styling */
    .input-field-row {
        display: flex;
        align-items: center;
        background: rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }
    .input-field-row:focus-within {
        border-color: #ff9500;
        background: rgba(0,0,0,0.3);
    }
    .field-icon { color: #ff9500; margin-right: 12px; display: flex; }
    .field-input {
        background: transparent;
        border: none;
        color: #fff;
        font-weight: 600;
        width: 100%;
        outline: none;
    }

    /* Action Button */
    .theme-btn {
        width: 100%;
        background: linear-gradient(135deg, #ff9500 0%, #ff5e00 100%);
        border: none;
        color: #000;
        font-weight: 900;
        text-transform: uppercase;
        padding: 18px;
        border-radius: 14px;
        letter-spacing: 1px;
        box-shadow: 0 8px 20px rgba(255, 94, 0, 0.3);
        transition: all 0.3s ease;
    }
    .theme-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(255, 94, 0, 0.5);
    }

    @media (max-width: 768px) {
        .glass-card { padding: 25px; }
        .method-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    }
</style>
@endsection

@section('content')
<div class="withdraw-container py-5">
    <div class="withdraw-header">
        <h2>WITHDRAW PROFITS</h2>
        <p class="text-grey">Select method and enter withdrawal amount</p>
    </div>

    <div class="nav-tabs-wrapper">
        <div class="auth-tabs-container">
            <a href="/deposit" class="auth-tab-btn">DEPOSIT</a>
            <a href="#" class="auth-tab-btn active">WITHDRAW</a>
        </div>
    </div>

    <div class="method-grid">
        <div class="payment-method-card active" id="card_mpesa" onclick="switchMethod('mpesa')">
            <div class="method-thumb">
                <img src="{{ asset('images/Mpesa-Logo.png') }}" style="height: 40px;">
            </div>
            <div class="method-label">M-Pesa Instant</div>
        </div>
        <div class="payment-method-card" id="card_p2p" onclick="switchMethod('p2p')">
            <div class="method-thumb">
                <span class="material-symbols-outlined" style="font-size: 40px; color: #ff9500;">group</span>
            </div>
            <div class="method-label">P2P Transfer</div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- M-Pesa Withdrawal -->
            <div id="mpesa_withdraw_section" class="glass-card">
                <div class="d-flex align-items-center mb-4">
                    <span class="material-symbols-outlined text-warning me-2">verified_user</span>
                    <h5 class="text-white mb-0">M-Pesa Instant B2C</h5>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">call</span></span>
                    <input type="text" class="field-input" id="mpesa_withdraw_phone" placeholder="Phone (e.g. 07XXXXXXXX)" value="{{ user('mobile') }}">
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">payments</span></span>
                    <input type="number" class="field-input" id="mpesa_withdraw_amount" placeholder="Amount (KSh)">
                </div>

                <button class="theme-btn" id="mpesa_withdraw_btn" onclick="initiateMpesaWithdraw()">Withdraw via M-Pesa</button>
                <div class="text-center mt-3 small text-grey">Min: <strong>10.00</strong> | Max: <strong>150,000.00</strong></div>
            </div>

            <!-- P2P Withdrawal -->
            <div id="p2p_withdraw_section" class="glass-card" style="display: none;">
                <div class="d-flex align-items-center mb-4">
                    <span class="material-symbols-outlined text-warning me-2">group</span>
                    <h5 class="text-white mb-0">P2P Marketplace</h5>
                </div>
                
                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">payments</span></span>
                    <input type="number" class="field-input" id="p2p_withdraw_amount" placeholder="Withdrawal Amount (KSh)">
                </div>

                <div class="alert alert-info bg-dark border-secondary small mb-4">
                    <i class="material-symbols-outlined f-14 align-middle me-1">info</i>
                    P2P trades your balance with verified users for instant mobile money.
                </div>

                <button class="theme-btn" id="p2p_submit_btn" onclick="initiateP2PSearch()">Find P2P Matches</button>
                <div class="text-center mt-3 small text-grey">Min: <strong id="p2p_min_display">100.00</strong></div>
            </div>

            <!-- Global Search Overlay for P2P -->
            <div id="p2p_searching_overlay" class="glass-card text-center py-5" style="display:none;">
                <div class="spinner-border text-warning mb-4" role="status" style="width: 3rem; height: 30px;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="text-white mb-2">Searching for Peers...</h4>
                <p class="text-grey mb-4">Please wait while we find a verified merchant for your transfer.</p>
                <button class="btn btn-outline-danger btn-sm rounded-pill px-4" onclick="cancelP2PSearch()">Cancel Search</button>
            </div>

            <!-- P2P Match Found -->
            <div id="p2p_match_found" class="glass-card" style="display:none;">
                <div class="text-center mb-4">
                    <div class="type-icon type-credit mx-auto mb-3" style="width:60px; height:60px;">
                        <span class="material-symbols-outlined" style="font-size:32px;">handshake</span>
                    </div>
                    <h4 class="text-white">Match Found!</h4>
                    <p class="text-success small fw-700">Verified Peer Assigned</p>
                </div>
                
                <div class="bg-dark p-3 rounded-3 mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-grey small">Peer Name:</span>
                        <span class="text-white fw-700" id="peer_name">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-grey small">M-Pesa Number:</span>
                        <span class="text-warning fw-700" id="peer_phone">-</span>
                    </div>
                </div>
                
                <div class="alert alert-warning border-secondary small">
                    <i class="material-symbols-outlined f-14 align-middle me-1">info</i>
                    The peer will send KSh to your number. Do not confirm until you receive the payment.
                </div>
                
                <button class="theme-btn" onclick="location.reload()">Return to History</button>
            </div>

            <!-- Status Messages -->
            <div id="withdraw_status" style="display: none;" class="mt-4">
                <div id="withdraw_loading" class="alert alert-info border-0 bg-dark text-info"><i class="mdi mdi-loading mdi-spin me-2"></i>Processing...</div>
                <div id="withdraw_error" class="alert alert-danger border-0 bg-dark text-danger"><i class="mdi mdi-alert-circle me-2"></i><span id="withdraw_error_msg"></span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="{{ url('user/mpesa-withdraw.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}/";
        let p2pPollInterval = null;
        let p2pCurrentRef = null;

        function switchMethod(type) {
            $('.glass-card').hide();
            $('.payment-method-card').removeClass('active');
            
            if (type === 'mpesa') {
                $('#mpesa_withdraw_section').show();
                $('#card_mpesa').addClass('active');
            } else if (type === 'p2p') {
                $('#p2p_withdraw_section').show();
                $('#card_p2p').addClass('active');
            }
        }

        // --- P2P CORE LOGIC ---
        function initiateP2PSearch() {
            const amount = $('#p2p_withdraw_amount').val();
            if (!amount || amount < 100) {
                toastr.error("Minimum P2P withdrawal is 100 KSh");
                return;
            }

            $('#p2p_withdraw_section').hide();
            $('#p2p_searching_overlay').show();

            $.ajax({
                url: '/p2p/search',
                method: 'POST',
                data: {
                    amount: amount,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    if (res.isSuccess) {
                        p2pCurrentRef = res.reference;
                        p2pPollInterval = setInterval(checkP2PStatus, 3000);
                        toastr.success("Search initiated...");
                    } else {
                        showP2PError(res.message);
                    }
                },
                error: function(xhr) {
                    showP2PError(xhr.responseJSON ? xhr.responseJSON.message : "Server error");
                }
            });
        }

        function checkP2PStatus() {
            if (!p2pCurrentRef) return;
            $.get('/p2p/status/' + p2pCurrentRef, function(res) {
                if (res.status === 'matched') {
                    clearInterval(p2pPollInterval);
                    $('#p2p_searching_overlay').hide();
                    $('#p2p_match_found').show();
                    $('#peer_name').text(res.peer.name);
                    $('#peer_phone').text(res.peer.phone);
                    toastr.success("MATCH FOUND!");
                } else if (res.status === 'cancelled' || res.status === 'failed') {
                    showP2PError("Transaction " + res.status);
                }
            });
        }

        function cancelP2PSearch() {
            if (!p2pCurrentRef) { resetP2P(); return; }
            if (!confirm("Cancel search and refund wallet?")) return;

            $.post('/p2p/cancel/' + p2pCurrentRef, { _token: '{{ csrf_token() }}' }, function(res) {
                toastr.info(res.message);
                resetP2P();
            });
        }

        function resetP2P() {
            clearInterval(p2pPollInterval);
            p2pCurrentRef = null;
            $('#p2p_searching_overlay').hide();
            $('#p2p_match_found').hide();
            $('#p2p_withdraw_section').show();
        }

        function showP2PError(msg) {
            toastr.error(msg);
            resetP2P();
        }
    </script>
@endsection
