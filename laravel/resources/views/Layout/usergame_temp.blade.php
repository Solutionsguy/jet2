<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betting company {{ env('APP_NAME') }} - online sports betting</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/jquery.mCustomScrollbar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pretty-checkbox.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/niceCountryInput.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery.ccpicker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile-improvements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rain.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rain-chat.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.dataTables.min.css') }}">
    @yield('css')
</head>
<body class="dark-bg-main">

@include('include.header')
@yield('content')

@include('include.chat-sidebar')

<input type="hidden" id="referral_code" value="">

    <input type="hidden" id="referral_code" value="">
    <!--====== Login Modal Start ======-->
    <div class="modal fade l-modal" id="login-modal" tabindex="-1" aria-labelledby="login-modal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header login-header">
                    <span class="material-symbols-outlined">
                        account_circle
                    </span>
                    <h5 class="modal-title" id="exampleModalLabel">SITE ENTRANCE</h5>
                </div>
                <div class="modal-body pt-1">
                    <form class="login-form" method="post" action="#"
                        name="loginForm" id="loginForm">
                        @csrf
                        <div class="login-controls">
                            <label for="Username">
                                <span class="material-symbols-outlined input-ico">
                                    person
                                </span>
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Your email/phone">
                            </label>
                        </div>

                        <div class="login-controls">
                            <label for="password">
                                <span class="material-symbols-outlined input-ico">
                                    lock
                                </span>
                                <input type="password" class="form-control" id="password" placeholder="password"
                                    name="password">

                                <span class="material-symbols-outlined input-ico" id="view_password">
                                    visibility_off
                                </span>
                            </label>
                        </div>

                        <div>
                            <label id="username-error" class="error" for="username" style="display: none;"></label>
                            <label id="password-error" class="error" for="password" style="display: none;"></label>
                            <label id="login-error" class="error"></label>
                        </div>

                        <div class="form-check form-switch md-switch d-flex align-items-center checkforlogin">
                            <input class="form-check-input me-1" type="checkbox" id="rememberme">
                            <label class="form-check-label f-14 ms-1" for="rememberme">Remember me</label>
                        </div>
                        <button class="btn green-btn md-btn custm-btn-2 mx-auto mt-3 mb-1"
                            id="loginSubmit">LOGIN</button>

                        <a href="javascript:void(0);" class="link-text f-14 d-flex justify-content-center"
                            data-bs-toggle="modal" data-bs-target="#forgot-modal" id="forgotPassword">Forgot your
                            password?</a>
                    </form>
                </div>
                <div class="login-footer mt-1">
                    <h4 class="f-14 d-flex justify-content-center reg_btn">Not registered yet?</h4>
                    <button class="btn orange-btn md-btn custm-btn-2 mx-auto mt-1 mb-2" data-bs-toggle="modal"
                        data-bs-target="#register-modal">REGISTER</button>
                </div>
            </div>
        </div>
    </div>
    <!--====== Login Modal End ======-->

    <!--====== Forgot Modal Start ======-->
    <div class="modal fade l-modal" id="forgot-modal" tabindex="-1" aria-labelledby="forgot-modal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header login-header">
                    <span class="material-symbols-outlined">
                        lock
                    </span>
                    <h5 class="modal-title" id="exampleModalLabel">PASSWORD RECOVERY</h5>
                </div>
                <div class="modal-body pt-0">
                    <label id="registerError" class="error"></label>
                    <p class="link-text f-14 email_text">To recover your password, enter your email or phone number
                        used during registration</p>
                    <form class="login-form" method="post" id="forgotPasswordForm">
                        <input type="hidden" name="otp_id" id="otp_id">
                        <div class="login-controls" id="user_name_div">
                            <label for="Username">
                                <input type="text" class="form-control text-indent-0" id="user_name"
                                    placeholder="Your email/phone" name="username" required>
                            </label>
                        </div>
                        <div class="login-controls" id="otp_div">
                            <label for="otp">
                                <input type="text" class="form-control text-indent-0" id="otp"
                                    placeholder="Verification Code" name="otp">
                            </label>
                        </div>
                        <div>
                            <label id="otp_error" class="error"></label>
                        </div>
                        <button class="btn green-btn md-btn custm-btn-2 mx-auto mt-3 mb-3 w-100"
                            id="processSubmit">PROCEED</button>
                        <a href="#" class="link-text f-14 d-flex justify-content-center"
                            data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#login-modal">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--====== Forgot Modal End ======-->


    <!--====== Password Modal Start ======-->
    <div class="modal fade l-modal" id="reset-password-modal" tabindex="-1" aria-labelledby="reset-password-modal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header login-header">
                    <span class="material-symbols-outlined">
                        account_circle
                    </span>
                    <h5 class="modal-title" id="exampleModalLabel">NEW PASSWORD CREATION</h5>
                </div>
                <div class="modal-body pt-0">
                    <form class="login-form" action="#" method="post" id="resetPasswordForm">
                        <input type="hidden" name="username" id="reset_username">
                        <div class="login-controls">
                            <label for="password">
                                <span class="material-symbols-outlined input-ico">
                                    lock
                                </span>
                                <input type="password" class="form-control" id="new_password" placeholder="Password"
                                    name="password" required>
                            </label>
                        </div>

                        <div class="login-controls">
                            <label for="password">
                                <span class="material-symbols-outlined input-ico">
                                    lock
                                </span>
                                <input type="password" class="form-control" id="confirm_password"
                                    placeholder="Confirm the password" name="confirm_password" required>
                            </label>
                        </div>
                        <div>
                            <label id="confirm_password-error" class="error" for="confirm_password"></label>
                            <label id="new_password-error" class="error" for="new_password"></label>
                        </div>
                        <button class="btn green-btn md-btn custm-btn-2 mx-auto mt-3 mb-3 w-100"
                            id="saveSubmit">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--====== Password Modal End ======-->

    <!--====== Register Modal Start ======-->
    <div class="modal fade l-modal register-modal-popup" id="register-modal" tabindex="-1"
        aria-labelledby="register-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="dice-ico modal-ico"></div>
                <div class="chip-ico modal-ico"></div>
                <div class="ychip-ico modal-ico"></div>
                <div class="ball-ico modal-ico"></div>
                <div class="custom-header">
                    <div class="pa-40 pb-0">
                        <button type="button" class="btn btn-transparent text-white p-0 close-absolute"
                            data-bs-dismiss="modal" aria-label="Close">
                            <span class="material-symbols-outlined">
                                close
                            </span>
                        </button>
                        <h5 class="register-title mb-3">REGISTER</h5>
                    </div>
                    <div class="register-tabs">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            {{-- <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="oneclick-tab" data-bs-toggle="tab"
                                    data-bs-target="#oneclick" type="button" role="tab"
                                    aria-controls="oneclick" aria-selected="true">
                                    <span class="material-symbols-outlined">
                                        pan_tool_alt
                                    </span>One Click
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link disabled" tabindex="-1" aria-disabled="true">
                                    <span class="material-symbols-outlined">
                                        phone_iphone
                                    </span>Via moblie phone
                                </button>
                            </li> --}}
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="via-email-tab" data-bs-toggle="tab"
                                    data-bs-target="#via-email" type="button" role="tab"
                                    aria-controls="via-email" aria-selected="false">
                                    <span class="material-symbols-outlined">
                                        mail
                                    </span>Via email
                                </button>
                            </li>
                            {{-- <li class="nav-item" role="presentation">
                                <button class="nav-link disabled" tabindex="-1" aria-disabled="true">
                                    <span class="material-symbols-outlined">
                                        forum
                                    </span>Via social network
                                </button>
                            </li> --}}
                        </ul>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div class="register-tabs">
                        <div class="tab-content" id="myTabContent">
                            {{-- <div class="tab-pane fade" id="oneclick" role="tabpanel"
                                aria-labelledby="oneclick-tab">
                                <form class="register-form row" action="http://52.71.176.55:8082/register_post"
                                    method="POST" name="registerForm" id="registerOneClickForm">
                                    <input type="hidden" name="country" id="countries" value="IN">
                                    <input type="hidden" name="register_type" id="register_type" value="1">
                                    <div class="col-md-6 col-12">
                                        <div class="input-group mb-3">
                                            <div class="niceCountryInputSelector" data-selectedcountry="IN"
                                                data-showspecial="false" data-showflags="true"
                                                data-i18nall="All selected" data-i18nnofilter="No selection"
                                                data-i18nfilter="Filter" data-onchangecallback="onChangeCallback">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="input-group flex-nowrap mb-3">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined">
                                                    payments
                                                </span>
                                            </span>
                                            <select class="form-select custom-select" id="inputGroupSelect01"
                                                name="currency">
                                                <option selected value="KSh">KES</option>
                                                <option value="$">USD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    settings
                                                </span>
                                            </span>
                                            <input type="text" class="form-control ps-0" id="promocode"
                                                name="promocode" placeholder="Promocode in case you have one">
                                            <label for="promocode" id="promocode_error"></label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="checks-bg">
                                            <div class="pretty p-svg p-thick">
                                                <input type="checkbox" checked id="one_click_check" />
                                                <div class="state">
                                                    <svg class="svg svg-icon" viewBox="0 0 20 20">
                                                        <path
                                                            d="M7.629,14.566c0.125,0.125,0.291,0.188,0.456,0.188c0.164,0,0.329-0.062,0.456-0.188l8.219-8.221c0.252-0.252,0.252-0.659,0-0.911c-0.252-0.252-0.659-0.252-0.911,0l-7.764,7.763L4.152,9.267c-0.252-0.251-0.66-0.251-0.911,0c-0.252,0.252-0.252,0.66,0,0.911L7.629,14.566z"
                                                            style="stroke: white;fill:white;"></path>
                                                    </svg>
                                                    <label>I confirm that I am of legal age and agree with the <a>site
                                                            rules</a></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="btn orange-btn md-btn custm-btn-2 mx-auto mt-3 mb-0 registerSubmit"
                                        data-bs-toggle="modal" data-bs-target="#userpassword-modal"
                                        id="one_click_register">START GAME</button>
                                </form>
                            </div> --}}
                            <div class="tab-pane fade show active" id="via-email" role="tabpanel"
                                aria-labelledby="via-email-tab">
                                <form class="register-form row" action="/auth/register" method="post"
                                    name="registerForm" id="registerViaEmailForm">
                                    <input type="hidden" name="country" id="countries" value="IN">
                                    <input type="hidden" name="register_type" id="register_type" value="3">
                                    @csrf
                                    <div class="col-md-6 col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    badge
                                                </span>
                                            </span>
                                            <input type="text" class="form-control ps-0" id="name"
                                                placeholder="Name" name="name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    male
                                                </span>
                                            </span>
                                            <select class="form-select custom-select" id="gender" name="gender">
                                                <option selected value="male">Male</option>
                                                <option value="female">Female</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    smartphone
                                                </span>
                                            </span>
                                            <input type="number" class="form-control ps-0" id="mobile"
                                                placeholder="Mobile" name="mobile">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    mail
                                                </span>
                                            </span>
                                            <input type="email" class="form-control ps-0" id="reg_email"
                                                placeholder="Email" name="email">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    lock
                                                </span>
                                            </span>
                                            <input type="password" class="form-control ps-0" id="regpassword"
                                                placeholder="Password" name="password">
                                            <span class="material-symbols-outlined input-ico"
                                                id="view_password_register">
                                                visibility_off
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="input-group flex-nowrap mb-3">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined">
                                                    payments
                                                </span>
                                            </span>
                                            <select class="form-select custom-select" id="currency" name="currency">
                                                <option selected value="KSh">KES</option>
                                                <option value="$">USD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="input-group mb-3">
                                            <div class="niceCountryInputSelector" data-selectedcountry="IN"
                                                data-showspecial="false" data-showflags="true"
                                                data-i18nall="All selected" data-i18nnofilter="No selection"
                                                data-i18nfilter="Filter" data-onchangecallback="onChangeCallback">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group flex-nowrap mb-3 promocode align-items-center">
                                            <span class="input-group-text" id="addon-wrapping">
                                                <span class="material-symbols-outlined bold-icon">
                                                    settings
                                                </span>
                                            </span>
                                            <input type="text" class="form-control ps-0" id="promo_code"
                                                name="promocode" placeholder="Enter Promocode" value="{{isset($_GET['refer']) ? $_GET['refer'] : ''}}">
                                            {{-- <!-- <div class="CheckButton-icon d-flex align-items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14" class="Icon_icon__2Th0s"><path d="M7 14c-.627 0-1.224-.109-1.802-.264L6.53 11.96c.156.014.309.041.469.041A5 5 0 007 2a4.937 4.937 0 00-3.519 1.481L6 6H0V0l2.055 2.055A6.961 6.961 0 017 0a7 7 0 110 14zM3.703 9.012l4.97-4.08 1.09 1.431-6.113 6.2-.005-.007-.007.006L.23 8.772l1.42-1.249z"></path></svg>
                                            </div>
                                            <button class="btn btn-transparent check-btn">Check</button> --> --}}
                                        </div>

                                    </div>
                                    <div class="col-12">
                                        <label for="promo_code" id="promo_code_error" class="error"></label>
                                    </div>

                                    <div class="col-12">
                                        <div class="checks-bg">
                                            <div class="pretty p-svg p-thick">
                                                <input type="checkbox" id="email_policy" checked />
                                                <div class="state">
                                                    <svg class="svg svg-icon" viewBox="0 0 20 20">
                                                        <path
                                                            d="M7.629,14.566c0.125,0.125,0.291,0.188,0.456,0.188c0.164,0,0.329-0.062,0.456-0.188l8.219-8.221c0.252-0.252,0.252-0.659,0-0.911c-0.252-0.252-0.659-0.252-0.911,0l-7.764,7.763L4.152,9.267c-0.252-0.251-0.66-0.251-0.911,0c-0.252,0.252-0.252,0.66,0,0.911L7.629,14.566z"
                                                            style="stroke: white;fill:white;"></path>
                                                    </svg>
                                                    <label>I confirm that I am of legal age and agree with the <a>site
                                                            rules</a></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit"
                                        class="btn orange-btn md-btn custm-btn-2 mx-auto mt-3 mb-0 registerSubmit"
                                        id="register_via_email">START GAME</button>

                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!--====== Register Modal End ======-->


@include('include.footer')

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jquery.mCustomScrollbar.js') }}"></script>
<script src="{{ asset('js/niceCountryInput.js') }}"></script>
<script src="{{ asset('js/jquery.ccpicker.js') }}"></script>
<script src="{{ asset('js/anime.min.js') }}"></script>
<script src="{{ asset('js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('js/toastr.min.js') }}"></script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/dataTables.responsive.min.js') }}"></script>
<script src="https://unpkg.com/sweetalert@2.1.2/dist/sweetalert.min.js"></script>

<script>
    window.APP_URL = "{{ url('/') }}";
</script>

<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
});

$.ajax({
    url: window.APP_URL + '/get_user_details',
    type: 'GET',
    success: function (result) {
        if (result.isSuccess) {
            $("#avatar_img").attr('src', result.data.avatar);
            $("#username").text(result.data.username);
            if (result.data.notification) {
                swal('Notification', result.data.notification, 'success')
                    .then(() => {
                        $.post(window.APP_URL + '/update_is_notify', {
                            id: result.data.id,
                            request_type: result.data.request_type
                        });
                    });
            }
        }
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap === 'undefined') return;
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl, { autoClose: true });
    });
    dropdownElementList.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = bootstrap.Dropdown.getOrCreateInstance(this);
            dropdown.toggle();
        });
    });
});
</script>

<script src="{{ asset('user/login.js') }}"></script>
<script src="{{ asset('js/dropdown-fix.js') }}"></script>

@if(session()->has('userlogin'))
<!-- Socket.IO -->
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>

<!-- User Balance Variables for Rain System -->
<script>
    var hash_id = '{{ csrf_token() }}';
    var currency_id = '{{ user('currency') }}';
    var currency_symbol = '{{ user('currency') }}';
    var wallet_balance = '{{ wallet(user('id')) }}';
    var freebet_balance = '{{ \App\Models\Wallet::where('userid', user('id'))->first()->freebet_amount ?? 0 }}';
    var member_id = '{{ user('id') }}';
    var current_wallet_type = 'money'; // Default wallet type
    
    console.log('💰 Dashboard wallet balance initialized:', wallet_balance);
    console.log('🎁 Dashboard freebet balance initialized:', freebet_balance);
    console.log('👤 Member ID initialized:', member_id);
    
    // Wallet Balance Update Function (needed for rain system)
    window.updateWalletBalance = function() {
        console.log('🔔 updateWalletBalance() called!');
        console.log('📥 Current values - wallet_balance:', wallet_balance, 'freebet_balance:', freebet_balance);
        
        var balance = 0;
        
        if (current_wallet_type === 'freebet') {
            // Handle freebet_balance
            if (typeof freebet_balance === 'string') {
                balance = parseFloat(freebet_balance.replace(/,/g, '')) || 0;
            } else {
                balance = parseFloat(freebet_balance) || 0;
            }
        } else {
            // Handle wallet_balance
            if (typeof wallet_balance === 'string') {
                balance = parseFloat(wallet_balance.replace(/,/g, '')) || 0;
            } else {
                balance = parseFloat(wallet_balance) || 0;
            }
        }
        
        var formattedBalance = balance.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        console.log('📊 Calculated balance:', balance, '→ Formatted:', formattedBalance);
        
        // Update header wallet balance display
        var oldValue = $('#header_wallet_balance').text();
        $('#header_wallet_balance').html(currency_symbol + formattedBalance);
        var newValue = $('#header_wallet_balance').text();
        
        console.log('💵 Updated display - Old:', oldValue, '→ New:', newValue);
        console.log('🎯 Element found:', $('#header_wallet_balance').length > 0 ? 'YES' : 'NO');
    };
    
    // Initialize wallet balance on page load
    $(document).ready(function() {
        window.updateWalletBalance();
    });
</script>

<!-- Rain System -->
<script src="{{ asset('js/rain.js') }}"></script>
<!-- Chat System -->
<script src="{{ asset('js/chat.js') }}"></script>
<script src="{{ asset('js/emoji-picker.js') }}"></script>
<script>
// Handle clicks outside chat to close it
function handleClickOutside(event) {
    const chatSidebar = document.getElementById('chat-sidebar-popup');
    const toggleBtn = document.getElementById('chat-toggle-btn');
    
    // Check if chat is open
    if (!chatSidebar || !chatSidebar.classList.contains('open')) {
        document.removeEventListener('click', handleClickOutside);
        return;
    }
    
    // Check if click is outside chat and toggle button
    if (!chatSidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
        // Close the chat
        chatSidebar.classList.remove('open');
        toggleBtn.querySelector('.material-symbols-outlined').textContent = 'chat';
        
        // Remove this listener
        document.removeEventListener('click', handleClickOutside);
    }
}

// Toggle chat with icon change and click-outside behavior
function toggleChat() {
    const chatSidebar = document.getElementById('chat-sidebar-popup');
    const toggleBtn = document.getElementById('chat-toggle-btn');
    const unreadBadge = document.getElementById('chat-unread-badge');
    
    chatSidebar.classList.toggle('open');
    
    // Change icon and behavior based on state
    if (chatSidebar.classList.contains('open')) {
        // Chat is opening
        unreadBadge.style.display = 'none';
        unreadBadge.textContent = '0';
        toggleBtn.querySelector('.material-symbols-outlined').textContent = 'close';
        
        // Add click-outside listener when chat opens
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100); // Small delay to prevent immediate close
    } else {
        // Chat is closing
        toggleBtn.querySelector('.material-symbols-outlined').textContent = 'chat';
        
        // Remove click-outside listener when chat closes
        document.removeEventListener('click', handleClickOutside);
    }
}
</script>
@endif

@yield('js')
</body>
</html>
