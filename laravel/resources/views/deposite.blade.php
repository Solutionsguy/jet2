@extends('Layout.usergame2')

@section('css')
<style>
    .deposit-container {
        max-width: 600px;
        margin: 100px auto;
        padding: 20px;
    }
    .deposit-card {
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

    .phone-prefix {
        color: #F59E0B !important;
        font-weight: 700 !important;
        margin-right: 5px !important;
        font-size: 16px !important;
    }

    .deposit-btn {
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
    .deposit-btn:hover {
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
        flex-wrap: wrap;
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

    @media (max-width: 768px) {
        .deposit-container { margin-top: 80px; padding: 15px; }
        .deposit-card { padding: 20px; }
        .method-btn { padding: 15px 5px; }
        .method-btn i { font-size: 24px; }
        .method-btn span { font-size: 12px; }
    }
</style>
@endsection

@section('content')
<div class="deposit-container">
    <div class="text-center mb-4">
        <div class="pay-tabs d-inline-flex bg-dark p-1 rounded-pill" style="border: 1px solid #333;">
            <a href="#" class="btn rounded-pill px-4 bg-warning text-black" style="font-size: 14px; font-weight: 700;">DEPOSIT</a>
            <a href="/withdraw" class="btn rounded-pill px-4 text-grey" style="font-size: 14px; font-weight: 700;">WITHDRAW</a>
        </div>
    </div>

    <div class="deposit-card">
        <!-- Balance Summary -->
        <div class="balance-summary">
            <span class="label">Current Balance</span>
            <span class="amount">KSh {{ number_format(\App\Models\Wallet::where('userid', user('id'))->first()->amount ?? 0, 2) }}</span>
        </div>

        <!-- Method Selector -->
        <div class="method-selector">
            <div class="method-btn active" id="btn_mpesa_tab" onclick="switchDeposit('mpesa')">
                <i class="material-symbols-outlined">smartphone</i>
                <span>M-Pesa</span>
            </div>
            <div class="method-btn" id="btn_card_tab" onclick="switchDeposit('card')">
                <i class="material-symbols-outlined">credit_card</i>
                <span>Card / Other</span>
            </div>
        </div>

        <!-- M-Pesa Form -->
        <div id="mpesa_deposit" class="deposit-section">
            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">call</span>
                </span>
                <span class="phone-prefix">254</span>
                <input type="text" class="field-input" id="mpesa_phone" placeholder="7XXXXXXXX" value="{{ (user('mobile') && strpos(user('mobile'), '254') === 0) ? substr(user('mobile'), 3) : (user('mobile') ?? '') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);">
            </div>

            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">mail</span>
                </span>
                <input type="email" class="field-input" id="mpesa_email" placeholder="Email Address" value="{{ user('email') ?? '' }}">
            </div>

            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">payments</span>
                </span>
                <input type="number" class="field-input" id="mpesa_amount" placeholder="Amount (KSh)" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <div class="quick-amounts">
                <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('100')">100</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('500')">500</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('1000')">1000</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('2000')">2000</button>
                <button class="quick-amount-btn" onclick="$('#mpesa_amount').val('5000')">5000</button>
            </div>

            <button class="deposit-btn" id="mpesa_submit_btn" onclick="initiateMpesaDeposit()">Deposit via M-Pesa</button>
            
            <div class="limit-badge">Minimum Deposit: <strong>KSh 1</strong></div>
        </div>

        <!-- Card/Paystack Form -->
        <div id="card_deposit" class="deposit-section" style="display: none;">
            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">mail</span>
                </span>
                <input type="email" class="field-input" id="paystack_email" placeholder="Email Address" value="{{ user('email') ?? '' }}">
            </div>

            <div class="input-field-row">
                <span class="field-icon">
                    <span class="material-symbols-outlined">payments</span>
                </span>
                <input type="number" class="field-input" id="paystack_amount" placeholder="Amount (KSh)" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div>

            <div class="quick-amounts">
                <button class="quick-amount-btn" onclick="$('#paystack_amount').val('500')">500</button>
                <button class="quick-amount-btn" onclick="$('#paystack_amount').val('1000')">1000</button>
                <button class="quick-amount-btn" onclick="$('#paystack_amount').val('2000')">2000</button>
                <button class="quick-amount-btn" onclick="$('#paystack_amount').val('5000')">5000</button>
            </div>

            <button class="deposit-btn" id="paystack_submit_btn" onclick="initiatePaystackDeposit()">Deposit via Card/Other</button>
            
            <div class="limit-badge">Minimum Deposit: <strong>KSh 100</strong></div>
        </div>

        <!-- Global Status Messages -->
        <div id="mpesa_status_box" style="display: none;" class="mt-3">
            <div id="mpesa_loading" class="alert alert-info py-2 small">
                <i class="mdi mdi-loading mdi-spin me-1"></i> Processing request...
            </div>
            <div id="mpesa_success" class="alert alert-success py-2 small">
                <i class="mdi mdi-check-circle me-1"></i> Deposit initiated successfully!
            </div>
            <div id="mpesa_error" class="alert alert-danger py-2 small">
                <i class="mdi mdi-alert-circle me-1"></i> <span id="mpesa_error_msg"></span>
            </div>
        </div>
        
        <!-- Paystack Status Messages -->
        <div id="paystack_status" style="display: none;" class="mt-3">
            <div id="paystack_loading" class="alert alert-info py-2 small">
                <i class="mdi mdi-loading mdi-spin me-1"></i> Processing request...
            </div>
            <div id="paystack_error" class="alert alert-danger py-2 small">
                <i class="mdi mdi-alert-circle me-1"></i> <span id="paystack_error_msg"></span>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="mpesa_min_recharge" value="1">

@endsection

@section('js')
    <script src="{{ url('user/mpesa-deposit.js') }}"></script>
    <script src="{{ url('user/paystack-deposit.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}/";
        function switchDeposit(type) {
            $('.deposit-section').hide();
            $('.method-btn').removeClass('active');
            
            if (type === 'mpesa') {
                $('#mpesa_deposit').show();
                $('#btn_mpesa_tab').addClass('active');
            } else if (type === 'card') {
                $('#card_deposit').show();
                $('#btn_card_tab').addClass('active');
            }
        }
    </script>
    @isset($_GET['msg'])
    @if ($_GET['msg'] == 'Success')
        <script>
            toastr.success("Payment successful! Your wallet has been credited.")
        </script>
    @endif
    @endisset
@endsection
