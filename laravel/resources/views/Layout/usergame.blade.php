<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script>
        // GLOBAL DEBUG TOGGLE
        window.DEBUG_MODE = false;
        if (!window.DEBUG_MODE) {
            console.log = function() {};
            console.info = function() {};
            console.warn = function() {};
        }
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betting company {{ env('APP_NAME') }} - online sports betting</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;display=swap" rel="stylesheet">
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
    
    <style>
        label.error {
            color: #ff9500;
            font-size: 14px;
            font-weight: 500;
        }
        #view_password, #view_password_register {
            cursor: pointer;
            color: #ff9500;
        }
        /* Themed Modal Styles */
        .modal-content {
            background: rgba(25, 26, 27, 0.98) !important;
            border: 1px solid #2a2b2e !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8) !important;
            overflow: hidden;
        }
        .modal-header {
            border-bottom: 1px solid #2a2b2e !important;
            background: transparent !important;
            padding: 20px 30px !important;
        }
        .modal-header .btn-close {
            filter: invert(1) !important;
            opacity: 0.8;
        }
        .modal-body {
            padding: 30px !important;
        }
        .modal-footer-custom {
            border-top: 1px solid #2a2b2e !important;
            background: transparent !important;
            padding: 20px 30px !important;
            text-align: center;
        }
        .modal-title {
            color: #ff9500;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Ultra-Minimalist Input Style */
        .input-field-row {
            display: flex !important;
            align-items: center !important;
            border: none !important;
            border-bottom: 1px solid #333 !important; /* Naked underline */
            margin-bottom: 30px !important;
            padding: 5px 0 !important;
            transition: border-color 0.3s ease;
            width: 100% !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        .input-field-row:focus-within {
            border-bottom-color: #F59E0B !important; /* Only color on focus */
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
            box-shadow: none !important;
            outline: none !important;
        }
        .field-icon span {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
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
        #view_password, #view_password_register {
            color: #444 !important;
            cursor: pointer !important;
            margin-left: 10px !important;
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
            display: flex !important;
            align-items: center !important;
        }
        .input-field-row:focus-within #view_password, 
        .input-field-row:focus-within #view_password_register {
            color: #F59E0B !important;
        }

        .theme-btn {
            width: 100%;
            background: #ff9500 !important;
            color: #000 !important;
            border: none !important;
            padding: 15px !important;
            border-radius: 10px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px;
            transition: all 0.2s ease !important;
        }
        .theme-btn:hover {
            background: #fa5e00 !important;
            transform: translateY(-2px);
        }
        .link-themed {
            color: #ff9500 !important;
            text-decoration: none;
            font-weight: 600;
        }
        .text-grey {
            color: #888 !important;
        }

        /* Tabs */
        .pay-tabs {
            background: #111 !important;
            border: 1px solid #333 !important;
            padding: 2px !important;
        }
        .pay-tabs .btn {
            padding: 5px 20px !important;
            font-size: 12px !important;
            color: #888 !important;
            font-weight: 700;
        }
        .pay-tabs .btn.bg-warning {
            background: #ff9500 !important;
            color: #000 !important;
        }

        /* Country Summary */
        .country-summary {
            background: rgba(255, 149, 0, 0.1) !important;
            border: 1px dashed #ff9500 !important;
            border-radius: 12px !important;
            padding: 15px !important;
            margin-bottom: 25px !important;
        }
        .country-flag {
            width: 35px;
            height: 22px;
            object-fit: cover;
            border-radius: 3px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .country-info .value {
            color: #ff9500 !important;
        }

        /* Mobile Optimization for Modals */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 10px !important;
                max-width: calc(100% - 20px) !important;
            }
            .modal-body {
                padding: 20px 20px !important;
            }
            .input-field-row {
                margin-bottom: 20px !important;
            }
            .modal-header {
                padding: 20px 20px 0 !important;
            }
            .modal-footer-custom {
                padding: 15px !important;
            }
            .country-summary {
                padding: 10px !important;
                margin-bottom: 20px !important;
            }
            .theme-btn {
                padding: 12px !important;
                font-size: 14px !important;
            }
        }
    </style>
    @yield('css')
</head>
<body class="dark-bg-main">

@include('include.header')
@yield('content')

@include('include.chat-sidebar')

<input type="hidden" id="referral_code" value="">

    <!--====== Login Modal Start ======-->
    <div class="modal fade l-modal" id="login-modal" tabindex="-1" aria-labelledby="login-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center mt-2">
                        <div class="pay-tabs d-inline-flex bg-dark p-1 rounded-pill" style="border: 1px solid #333;">
                            <a href="javascript:void(0);" class="btn rounded-pill text-grey" onclick="switchModal('register')">REGISTER</a>
                            <a href="javascript:void(0);" class="btn rounded-pill bg-warning text-black">LOGIN</a>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-800 text-white uppercase mb-1" style="letter-spacing: 1px;">Welcome Back</h4>
                        <p class="text-grey small">Log in to your Kenyan account</p>
                    </div>

                    <form class="login-form" method="post" action="#" name="loginForm" id="loginForm">
                        @csrf
                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">person</span>
                            </span>
                            <input type="text" class="field-input" id="username" name="username" placeholder="Email or Mobile Number" required>
                        </div>

                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">lock</span>
                            </span>
                            <input type="password" class="field-input" id="password" name="password" placeholder="Password" required>
                            <span class="material-symbols-outlined" id="view_password">visibility_off</span>
                        </div>

                        <div class="mb-3">
                            <label id="login-error" class="error" style="color: #ff4d4d; font-size: 13px;"></label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="rememberme">
                                <label class="form-check-label text-grey small" for="rememberme">Remember me</label>
                            </div>
                            <a href="javascript:void(0);" class="link-themed small" data-bs-toggle="modal" data-bs-target="#forgot-modal">Forgot Password?</a>
                        </div>

                        <button class="theme-btn" id="loginSubmit">Sign In</button>
                    </form>
                </div>
                <div class="modal-footer-custom">
                    <span class="text-grey">Not registered yet?</span>
                    <a href="javascript:void(0);" class="link-themed ms-2" onclick="switchModal('register')">Create Account</a>
                </div>
            </div>
        </div>
    </div>
    <!--====== Login Modal End ======-->

    <!--====== Register Modal Start ======-->
    <div class="modal fade l-modal" id="register-modal" tabindex="-1" aria-labelledby="register-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center mt-2">
                        <div class="pay-tabs d-inline-flex bg-dark p-1 rounded-pill" style="border: 1px solid #333;">
                            <a href="javascript:void(0);" class="btn rounded-pill bg-warning text-black">REGISTER</a>
                            <a href="javascript:void(0);" class="btn rounded-pill text-grey" onclick="switchModal('login')">LOGIN</a>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <!-- Country/Currency Summary -->
                    <div class="country-summary">
                        <img src="https://flagcdn.com/w80/ke.png" class="country-flag" alt="Kenya Flag">
                        <div class="country-info">
                            <span class="label">REGISTRATION COUNTRY</span>
                            <span class="value">KENYA (KSh / KES)</span>
                        </div>
                        <div class="ms-auto">
                            <span class="material-symbols-outlined text-success" style="font-size: 24px;">verified_user</span>
                        </div>
                    </div>

                    <form class="register-form" action="/auth/register" method="post" name="registerForm" id="registerViaEmailForm">
                        @csrf
                        <input type="hidden" name="country" id="countries" value="KE">
                        <input type="hidden" name="currency" value="KES">
                        <input type="hidden" name="gender" value="male">
                        <input type="hidden" name="register_type" id="register_type" value="3">

                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">badge</span>
                            </span>
                            <input type="text" class="field-input" id="name" name="name" placeholder="Full Name" required>
                        </div>

                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">smartphone</span>
                            </span>
                            <input type="tel" class="field-input" id="mobile" name="mobile" placeholder="Mobile (e.g. 2547XXXXXXXX)" required>
                        </div>

                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">mail</span>
                            </span>
                            <input type="email" class="field-input" id="reg_email" name="email" placeholder="Email Address" required>
                        </div>

                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">lock</span>
                            </span>
                            <input type="password" class="field-input" id="regpassword" name="password" placeholder="Password" required>
                            <span class="material-symbols-outlined" id="view_password_register">visibility_off</span>
                        </div>

                        <div class="input-field-row">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">confirmation_number</span>
                            </span>
                            <input type="text" class="field-input" id="promo_code" name="promocode" placeholder="Promo Code (Optional)" value="{{isset($_GET['refer']) ? $_GET['refer'] : ''}}">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="email_policy" checked required>
                            <label class="form-check-label text-grey small" for="email_policy">
                                I confirm legal age & agree with <a href="#" class="link-themed">site rules</a>
                            </label>
                        </div>

                        <button type="submit" class="theme-btn" id="register_via_email">Create Account</button>
                    </form>
                </div>
                <div class="modal-footer-custom">
                    <span class="text-grey">Already have an account?</span>
                    <a href="javascript:void(0);" class="link-themed ms-2" onclick="switchModal('login')">Login Here</a>
                </div>
            </div>
        </div>
    </div>
    <!--====== Register Modal End ======-->

    <!--====== Forgot Modal Start ======-->
    <div class="modal fade l-modal" id="forgot-modal" tabindex="-1" aria-labelledby="forgot-modal" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" style="font-size: 18px;">Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-grey mb-4">Enter your email or phone number to recover your account.</p>
                    <form class="login-form" method="post" id="forgotPasswordForm">
                        <input type="hidden" name="otp_id" id="otp_id">
                        <div class="input-field-row" id="user_name_div">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">person</span>
                            </span>
                            <input type="text" class="field-input" id="user_name" name="username" placeholder="Email or Phone" required>
                        </div>
                        <div class="input-field-row" id="otp_div" style="display:none;">
                            <span class="field-icon">
                                <span class="material-symbols-outlined">verified</span>
                            </span>
                            <input type="text" class="field-input" id="otp" name="otp" placeholder="Verification Code">
                        </div>
                        <div class="mb-3">
                            <label id="otp_error" class="error"></label>
                        </div>
                        <button class="theme-btn" id="processSubmit">Proceed</button>
                    </form>
                </div>
                <div class="modal-footer-custom">
                    <a href="javascript:void(0);" class="link-themed" data-bs-toggle="modal" data-bs-target="#login-modal">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
    <!--====== Forgot Modal End ======-->

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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: window.APP_URL + '/get_user_details',
        type: 'GET',
        success: function (result) {
            if (result.isSuccess) {
                $("#avatar_img").attr('src', result.data.avatar);
                $("#username_header").text(result.data.username);
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

<script src="{{ asset('user/login.js') }}"></script>
<script src="{{ asset('js/dropdown-fix.js') }}"></script>

<script>
    function switchModal(type) {
        if (type === 'login') {
            $('#register-modal').modal('hide');
            setTimeout(() => { $('#login-modal').modal('show'); }, 300);
        } else {
            $('#login-modal').modal('hide');
            setTimeout(() => { $('#register-modal').modal('show'); }, 300);
        }
    }

    $(document).ready(function() {
        $('#view_password').on('click', function() {
            let input = $('#password');
            let icon = $(this);
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.text('visibility');
            } else {
                input.attr('type', 'password');
                icon.text('visibility_off');
            }
        });

        $('#view_password_register').on('click', function() {
            let input = $('#regpassword');
            let icon = $(this);
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.text('visibility');
            } else {
                input.attr('type', 'password');
                icon.text('visibility_off');
            }
        });
    });
</script>

@if(session()->has('userlogin'))
<!-- Socket.IO & Rain System Scripts -->
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script>
    var hash_id = '{{ csrf_token() }}';
    var currency_id = '{{ user('currency') }}';
    var currency_symbol = '{{ user('currency') }}';
    var wallet_balance = '{{ wallet(user('id')) }}';
    var freebet_balance = '{{ \App\Models\Wallet::where('userid', user('id'))->first()->freebet_amount ?? 0 }}';
    var member_id = '{{ user('id') }}';
    var current_wallet_type = 'money';
    
    window.updateWalletBalance = function() {
        var balance = 0;
        if (current_wallet_type === 'freebet') {
            balance = parseFloat(freebet_balance) || 0;
        } else {
            balance = parseFloat(wallet_balance) || 0;
        }
        var formattedBalance = balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        $('#header_wallet_balance').html(currency_symbol + formattedBalance);
    };
    $(document).ready(function() { window.updateWalletBalance(); });
</script>
<script src="{{ asset('js/rain.js') }}"></script>
<script src="{{ asset('js/chat.js') }}"></script>
<script src="{{ asset('js/emoji-picker.js') }}"></script>
@endif

@yield('js')
</body>
</html>
