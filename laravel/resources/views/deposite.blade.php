@extends('Layout.usergame2')

@section('css')
<style>
    .deposit-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    /* Header Section */
    .deposit-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .deposit-header h2 {
        color: #fff;
        font-weight: 900;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    
    /* Unified Navigation Tabs */
    .nav-tabs-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 40px;
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

    /* Premium Method Cards (Dashboard Style) */
    .method-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .payment-method-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }
    .payment-method-card:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.05);
        border-color: #ff9500;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
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
    .method-thumb img {
        max-height: 100%;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
    }
    .method-label {
        color: #fff;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
        transition: all 0.3s ease;
    }
    .input-field-row:focus-within {
        border-color: #ff9500;
        background: rgba(0,0,0,0.3);
        box-shadow: 0 0 15px rgba(255, 149, 0, 0.1);
    }
    .field-icon {
        color: #ff9500;
        margin-right: 12px;
        display: flex;
        align-items: center;
    }
    .field-input {
        background: transparent;
        border: none;
        color: #fff;
        font-weight: 600;
        width: 100%;
        outline: none;
    }
    .phone-prefix {
        color: #666;
        font-weight: 800;
        margin-right: 5px;
    }

    /* Quick Amounts */
    .quick-amounts {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
    }
    .quick-amount-btn {
        flex: 1;
        min-width: 70px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #ccc;
        padding: 10px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.2s;
    }
    .quick-amount-btn:hover {
        background: rgba(255, 149, 0, 0.1);
        color: #ff9500;
        border-color: #ff9500;
    }

    /* Deposit Button */
    .deposit-btn {
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
    .deposit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(255, 94, 0, 0.5);
    }

    .limit-badge {
        text-align: center;
        margin-top: 20px;
        font-size: 12px;
        color: #666;
    }
    .limit-badge strong { color: #888; }

    @media (max-width: 768px) {
        .glass-card { padding: 25px; }
        .method-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endsection

@section('content')
<div class="deposit-container py-5">
    <div class="deposit-header">
        <h2>RECHARGE WALLET</h2>
        <p class="text-grey">Choose your method and enter amount</p>
    </div>

    <div class="nav-tabs-wrapper">
        <div class="auth-tabs-container">
            <a href="#" class="auth-tab-btn active">DEPOSIT</a>
            <a href="/withdraw" class="auth-tab-btn">WITHDRAW</a>
        </div>
    </div>

    <!-- Method Selection -->
    <div class="method-grid">
        <div class="payment-method-card active" id="card_mpesa" onclick="switchDeposit('mpesa')">
            <div class="method-thumb">
                <img src="{{ asset('images/Mpesa-Logo.png') }}" alt="M-Pesa">
            </div>
            <div class="method-label">M-PESA</div>
        </div>
        <div class="payment-method-card" id="card_card" onclick="switchDeposit('card')">
            <div class="method-thumb">
                <span class="material-symbols-outlined" style="font-size: 45px; color: #ff9500;">credit_card</span>
            </div>
            <div class="method-label">CARD / OTHER</div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- M-Pesa Form -->
            <div id="mpesa_deposit" class="deposit-section glass-card">
                <div class="d-flex align-items-center mb-4">
                    <span class="material-symbols-outlined text-warning me-2">verified_user</span>
                    <h5 class="text-white mb-0">Secure M-Pesa Payment</h5>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">call</span></span>
                    <span class="phone-prefix">254</span>
                    <input type="text" class="field-input" id="mpesa_phone" placeholder="7XXXXXXXX" value="{{ (user('mobile') && strpos(user('mobile'), '254') === 0) ? substr(user('mobile'), 3) : (user('mobile') ?? '') }}">
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">mail</span></span>
                    <input type="email" class="field-input" id="mpesa_email" placeholder="Email Address" value="{{ user('email') ?? '' }}">
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">payments</span></span>
                    <input type="number" class="field-input" id="mpesa_amount" placeholder="Amount (KSh)">
                </div>

                <div class="quick-amounts">
                    <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('100')">100</button>
                    <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('500')">500</button>
                    <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('1000')">1,000</button>
                    <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('2500')">2,500</button>
                    <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('5000')">5,000</button>
                </div>

                <button class="deposit-btn" id="mpesa_submit_btn" onclick="initiateMpesaDeposit()">Pay Instant KSh</button>
                <div class="limit-badge">Min: <strong>1.00</strong> | Max: <strong>150,000.00</strong></div>
            </div>

            <!-- Card/Other Form -->
            <div id="card_deposit" class="deposit-section glass-card" style="display: none;">
                <div class="d-flex align-items-center mb-4">
                    <span class="material-symbols-outlined text-warning me-2">credit_card</span>
                    <h5 class="text-white mb-0">Card / International</h5>
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">mail</span></span>
                    <input type="email" class="field-input" id="paystack_email" placeholder="Email Address" value="{{ user('email') ?? '' }}">
                </div>

                <div class="input-field-row">
                    <span class="field-icon"><span class="material-symbols-outlined">payments</span></span>
                    <input type="number" class="field-input" id="paystack_amount" placeholder="Amount (KSh)">
                </div>

                <div class="quick-amounts">
                    <button class="quick-amount-btn" onclick="$('#paystack_amount').val('500')">500</button>
                    <button class="quick-amount-btn" onclick="$('#paystack_amount').val('1000')">1,000</button>
                    <button class="quick-amount-btn" onclick="$('#paystack_amount').val('2000')">2,000</button>
                    <button class="quick-amount-btn" onclick="$('#paystack_amount').val('5000')">5,000</button>
                </div>

                <button class="deposit-btn" id="paystack_submit_btn" onclick="initiatePaystackDeposit()">Continue to Payment</button>
                <div class="limit-badge">Min: <strong>100.00</strong> | Max: <strong>1,000,000.00</strong></div>
            </div>

            <!-- Status Messages -->
            <div id="mpesa_status_box" style="display: none;" class="mt-4">
                <div id="mpesa_loading" class="alert alert-info border-0 bg-dark text-info"><i class="mdi mdi-loading mdi-spin me-2"></i>Initializing...</div>
                <div id="mpesa_error" class="alert alert-danger border-0 bg-dark text-danger"><i class="mdi mdi-alert-circle me-2"></i><span id="mpesa_error_msg"></span></div>
            </div>
            <div id="paystack_status" style="display: none;" class="mt-4">
                <div id="paystack_loading" class="alert alert-info border-0 bg-dark text-info"><i class="mdi mdi-loading mdi-spin me-2"></i>Redirecting...</div>
                <div id="paystack_error" class="alert alert-danger border-0 bg-dark text-danger"><i class="mdi mdi-alert-circle me-2"></i><span id="paystack_error_msg"></span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="{{ url('user/mpesa-deposit.js') }}"></script>
    <script src="{{ url('user/paystack-deposit.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}/";
        function switchDeposit(type) {
            $('.deposit-section').hide();
            $('.payment-method-card').removeClass('active');
            
            if (type === 'mpesa') {
                $('#mpesa_deposit').show();
                $('#card_mpesa').addClass('active');
            } else if (type === 'card') {
                $('#card_deposit').show();
                $('#card_card').addClass('active');
            }
        }
    </script>
@endsection
