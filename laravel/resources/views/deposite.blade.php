@extends('Layout.usergame')
@section('content')
    <div class="deposite-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="pay-tabs">
                        <a href="#" class="custom-tabs-link active">DEPOSIT</a>
                        <a href="/withdraw" class="custom-tabs-link">WITHDRAW</a>
                    </div>

                    <div class="pay-options">
                        <div class="payment-cols">
                            <!-- M-Pesa Payment Option -->
                            <div class="grid-view">
                                <div class="grid-list" onclick="showMpesaDeposit()">
                                    <button class="btn payment-btn active" data-tab="mpesa">
                                        <img src="images/Mpesa-Logo.png" style="max-height: 60px;" />
                                        <div class="PaymentCard_limit">M-Pesa Payment</div>
                                    </button>
                                </div>
                            </div>
                            <!-- M-Pesa Deposit Form -->
                            <div class="deposite-box" id="mpesa" style="display: block;">
                                <div class="d-box">
                                    <div class="limit-txt">LIMITS:<span>Min 1 KSh (Testing Mode)</span></div>
                                    
                                    <input type="hidden" id="mpesa_min_recharge" value="1">
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="mpesa_phone" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10" 
                                                        id="mpesa_phone"
                                                        placeholder="2547XXXXXXXX"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                    <i class="Input_currency">
                                                        📱 Phone
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="mpesa_email" class="rounded-pill">
                                                    <input type="email" class="form-control text-i10" 
                                                        id="mpesa_email"
                                                        placeholder="your@email.com"
                                                        value="{{ user('email') ?? '' }}">
                                                    <i class="Input_currency">
                                                        📧 Email
                                                    </i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="login-controls mt-3 rounded-pill h42">
                                                <label for="mpesa_amount" class="rounded-pill">
                                                    <input type="text" class="form-control text-i10 amount"
                                                        id="mpesa_amount"
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
                                                id="mpesa_submit_btn"
                                                onclick="initiateMpesaDeposit()">
                                                PAY NOW
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="amount-tooltips">
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_amount').val('100')">100</button>
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_amount').val('500')">500</button>
                                        <button class="btn amount-tooltips-btn active" onclick="$('#mpesa_amount').val('1000')">1000</button>
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_amount').val('2000')">2000</button>
                                        <button class="btn amount-tooltips-btn" onclick="$('#mpesa_amount').val('5000')">5000</button>
                                    </div>
                                    
                                    <!-- Status Messages -->
                                    <div id="mpesa_status_box" style="display: none;" class="mt-3">
                                        <div id="mpesa_loading" class="alert alert-info" style="display: none;">
                                            <i class="mdi mdi-loading mdi-spin"></i> Please enter your M-Pesa PIN on your phone...
                                        </div>
                                        <div id="mpesa_success" class="alert alert-success" style="display: none;">
                                            <i class="mdi mdi-check-circle"></i> Payment successful! Your wallet has been credited.
                                        </div>
                                        <div id="mpesa_error" class="alert alert-danger" style="display: none;">
                                            <i class="mdi mdi-alert-circle"></i> <span id="mpesa_error_msg"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="deposite-blc mt-3">
                                        <div class="text-center">
                                            <img src="images/Mpesa-Logo.png" style="max-width: 150px; opacity: 0.7;">
                                            <p class="text-muted small mt-2">Instant M-Pesa Payment</p>
                                        </div>
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
    <script src="{{ url('user/mpesa-deposit.js') }}"></script>
    @isset($_GET['msg'])
    @if ($_GET['msg'] == 'Success')
        <script>
            toastr.success("Payment successful! Your wallet has been credited.")
        </script>
    @endif
    @endisset
@endsection
