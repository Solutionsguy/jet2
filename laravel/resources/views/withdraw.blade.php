@extends('Layout.usergame')
@section('content')
    <div class="deposite-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="pay-tabs">
                        <a href="/deposit" class="custom-tabs-link">DEPOSIT</a>
                        <a href="#" class="custom-tabs-link active">WITHDRAW</a>
                    </div>
                    <div class="pay-options">
                        <div class="payment-cols">
                            <!-- M-Pesa Withdrawal Option -->
                            <div class="grid-view">
                                <div class="grid-list">
                                    <button class="btn payment-btn active" onclick="showMpesaWithdraw()">
                                        <img src="images/Mpesa-Logo.png" style="max-height: 60px;" />
                                        <div class="PaymentCard_limit">M-Pesa Withdrawal</div>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- M-Pesa Withdrawal Form -->
                            <div class="deposite-box" id="mpesa_withdraw" style="display: block; margin-top: 20px;">
                                <div class="d-box">
                                    <div class="limit-txt">MINIMUM WITHDRAWAL: <span>{{setting('min_withdraw')}} KSh</span></div>
                                    
                                    <input type="hidden" id="mpesa_min_withdraw" value="{{setting('min_withdraw')}}">
                                    <input type="hidden" id="mpesa_wallet_balance" value="{{ \App\Models\Wallet::where('userid', user('id'))->first()->balance ?? 0 }}">
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="mpesa_withdraw_phone" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10" 
                                                        id="mpesa_withdraw_phone"
                                                        placeholder="2547XXXXXXXX"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                    <i class="Input_currency">
                                                        📱 M-Pesa Number
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="mpesa_withdraw_amount" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10 amount"
                                                        id="mpesa_withdraw_amount"
                                                        placeholder="Amount"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^0[^.]/, '0');">
                                                    <i class="Input_currency">
                                                        KSh
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <button
                                                class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow"
                                                id="mpesa_withdraw_btn"
                                                onclick="initiateMpesaWithdraw()">
                                                WITHDRAW
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="amount-tooltips">
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_withdraw_amount').val('500')">500</button>
                                        <button class="btn amount-tooltips-btn active" onclick="$('#mpesa_withdraw_amount').val('1000')">1000</button>
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_withdraw_amount').val('2000')">2000</button>
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_withdraw_amount').val('5000')">5000</button>
                                    </div>
                                    
                                    <!-- Status Messages -->
                                    <div id="mpesa_withdraw_status" style="display: none;" class="mt-3">
                                        <div id="mpesa_withdraw_loading" class="alert alert-info" style="display: none;">
                                            <i class="mdi mdi-loading mdi-spin"></i> Processing withdrawal request...
                                        </div>
                                        <div id="mpesa_withdraw_success" class="alert alert-success" style="display: none;">
                                            <i class="mdi mdi-check-circle"></i> Withdrawal successful! Funds sent to your M-Pesa.
                                        </div>
                                        <div id="mpesa_withdraw_error" class="alert alert-danger" style="display: none;">
                                            <i class="mdi mdi-alert-circle"></i> <span id="mpesa_withdraw_error_msg"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="deposite-blc mt-3">
                                        <div>CURRENT BALANCE</div>
                                        <div class="dopsite-vlue">KSh {{ \App\Models\Wallet::where('userid', user('id'))->first()->balance ?? 0 }}</div>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <img src="images/Mpesa-Logo.png" style="max-width: 150px; opacity: 0.7;">
                                        <p class="text-muted small mt-2">Instant M-Pesa Withdrawal</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script src="{{ url('user/mpesa-withdraw.js') }}"></script>
    @isset($_GET['msg'])
    @if ($_GET['msg'] == 'Success')
        <script>
            toastr.success("Withdrawal successful! Funds sent to your M-Pesa.");
        </script>
    @endif
    @if ($_GET['msg'] == 'error')
        <script>
            toastr.error("Something went wrong!");
        </script>
    @endif
    @endisset
    
    <script>
        function showMpesaWithdraw() {
            // Already visible, just scroll to form
            $('html, body').animate({
                scrollTop: $("#mpesa_withdraw").offset().top - 100
            }, 500);
        }
    </script>
@endsection
