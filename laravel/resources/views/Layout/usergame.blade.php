<!DOCTYPE html>
<html lang="en" prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary SEO & Search Engine Optimization -->
    <title>JetMtaa | JetMtaa Aviator, Casino & Premium Crash Gaming</title>
    <meta name="description" content="JetMtaa.com is Kenya's #1 destination for Aviator, Casino games and premium crash gaming. Join JetMtaa and win big!">
    <meta name="keywords" content="JetMtaa, JetMtaa Aviator, JetMtaa Casino, JetMtaa.com, JetMtaa Kenya">
    
    <!-- Open Graph Tags (Facebook / WhatsApp / Telegram) -->
    <meta property="og:site_name" content="JetMtaa">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://jetmtaa.com/">
    <meta property="og:title" content="JetMtaa - The Best Aviator & Casino in Kenya!">
    <meta property="og:description" content="Sign up now at JetMtaa.com and win big. Premium Crash Games, instant M-Pesa deposits and fast withdrawals!">
    <meta property="og:image" content="https://jetmtaa.com/images/promo-banner.png">
    <meta property="og:image:secure_url" content="https://jetmtaa.com/images/promo-banner.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Schema.org for Google/WhatsApp -->
    <meta itemprop="name" content="JetMtaa">
    <meta itemprop="description" content="Premium Crash Gaming in Kenya. Join now and win big!">
    <meta itemprop="image" content="https://jetmtaa.com/images/promo-banner.png">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="JetMtaa - Win Big Today">
    <meta name="twitter:image" content="https://jetmtaa.com/images/promo-banner.png">

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
    <link rel="stylesheet" href="{{ asset('css/auth-redesign.css') }}">
    
    <style>
        label.error {
            color: #ef4444;
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }
    </style>
    @stack('style-lib')
    @stack('style')
    @yield('css')
</head>
<body class="dark-bg-main">

@include('include.header')
<div class="main-container" style="min-height: 100vh;">
    <div class="container-fluid px-3">
        @yield('content')
    </div>
</div>
@include('include.chat-sidebar')

@php
    $isGameInterface = Request::is('play/*') || Request::is('crash') || Request::is('aviator');
@endphp

@if($isGameInterface)
<!--====== Mobile Bottom Navbar ======-->
<nav class="mobile-bottom-nav d-md-none">
    <div class="nav-item" onclick="window.location.href='{{ url('/dashboard') }}'">
        <span class="material-symbols-outlined">sports_esports</span>
        <span class="nav-label">Games</span>
    </div>
    <div class="nav-item {{ Request::is('deposit') ? 'active' : '' }}" onclick="window.location.href='{{ url('/deposit') }}'">
        <div class="deposit-icon-wrapper">
            <span class="material-symbols-outlined">add_circle</span>
        </div>
        <span class="nav-label">Deposit</span>
    </div>
    <div class="nav-item {{ Request::is('deposit_withdrawals') ? 'active' : '' }}" onclick="window.location.href='{{ url('/deposit_withdrawals') }}'">
        <span class="material-symbols-outlined">history</span>
        <span class="nav-label">My Bets</span>
    </div>
</nav>

<style>
    .mobile-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 70px;
        background: rgba(17, 18, 19, 0.98);
        backdrop-filter: blur(10px);
        display: flex;
        justify-content: space-around;
        align-items: center;
        z-index: 10000;
        border-top: 1px solid rgba(255, 149, 0, 0.2);
        padding-bottom: env(safe-area-inset-bottom);
    }
    .mobile-bottom-nav .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: #888;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        flex: 1;
    }
    .mobile-bottom-nav .nav-item.active {
        color: #ff9500;
    }
    .mobile-bottom-nav .nav-item .material-symbols-outlined {
        font-size: 26px;
        margin-bottom: 4px;
    }
    .mobile-bottom-nav .nav-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .deposit-icon-wrapper {
        background: linear-gradient(135deg, #ff9500, #ffb700);
        color: #000;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: -30px;
        box-shadow: 0 4px 15px rgba(255, 149, 0, 0.4);
        border: 4px solid #111213;
    }
    .deposit-icon-wrapper .material-symbols-outlined {
        margin-bottom: 0 !important;
        font-size: 30px !important;
    }

    /* Local Override: Shift chat button up ONLY when navbar is present */
    @media (max-width: 768px) {
        .chat-toggle-btn {
            bottom: 80px !important;
        }
        .chat-sidebar {
            bottom: 140px !important;
        }
    }
</style>
@endif

<input type="hidden" id="referral_code" value="">

    <!--====== Login Modal Start ======-->
    <div class="modal fade l-modal" id="login-modal" tabindex="-1" aria-labelledby="login-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center mt-3">
                        <div class="auth-tabs-container">
                            <button class="auth-tab-btn" onclick="switchModal('register')">Register</button>
                            <button class="auth-tab-btn active">Login</button>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-white mb-1">Welcome Back</h3>
                        <p class="text-secondary small">Access your account to start playing</p>
                    </div>

                    <form class="login-form" method="post" action="#" name="loginForm" id="loginForm">
                        @csrf
                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">person</span>
                                <input type="text" class="auth-input-field" id="username" name="username" placeholder="Email or Mobile Number" required>
                            </div>
                        </div>

                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">lock</span>
                                <input type="password" class="auth-input-field" id="password" name="password" placeholder="Password" required>
                                <span class="material-symbols-outlined auth-visibility-toggle" id="view_password">visibility_off</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label id="login-error" class="auth-error-msg"></label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="rememberme">
                                <label class="form-check-label text-secondary small" for="rememberme">Keep me logged in</label>
                            </div>
                            <a href="javascript:void(0);" class="auth-link small" data-bs-toggle="modal" data-bs-target="#forgot-modal">Forgot Password?</a>
                        </div>

                        <button class="auth-btn-primary" id="loginSubmit">Sign In</button>
                    </form>
                </div>
                <div class="text-center p-4 border-top border-secondary border-opacity-10">
                    <span class="text-secondary small">New here?</span>
                    <a href="javascript:void(0);" class="auth-link small ms-2" onclick="switchModal('register')">Create an Account</a>
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
                    <div class="w-100 text-center mt-3">
                        <div class="auth-tabs-container">
                            <button class="auth-tab-btn active">Register</button>
                            <button class="auth-tab-btn" onclick="switchModal('login')">Login</button>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="auth-country-badge">
                        <img src="https://flagcdn.com/w80/ke.png" style="width: 28px; height: 18px; border-radius: 2px;" alt="Kenya">
                        <div class="auth-country-info">
                            <span class="auth-country-label">Registering from</span>
                            <span class="auth-country-value">KENYA (KSh / KES)</span>
                        </div>
                        <div class="ms-auto">
                            <span class="material-symbols-outlined text-success" style="font-size: 20px;">verified</span>
                        </div>
                    </div>

                    <form class="register-form" action="/auth/register" method="post" name="registerForm" id="registerViaEmailForm">
                        @csrf
                        <input type="hidden" name="country" id="countries" value="KE">
                        <input type="hidden" name="currency" value="KES">
                        <input type="hidden" name="gender" value="male">
                        <input type="hidden" name="register_type" id="register_type" value="3">

                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">badge</span>
                                <input type="text" class="auth-input-field" id="name" name="name" placeholder="Full Name" required>
                            </div>
                        </div>

                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">smartphone</span>
                                <input type="tel" class="auth-input-field" id="mobile" name="mobile" placeholder="Mobile Number" required>
                            </div>
                        </div>

                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">mail</span>
                                <input type="email" class="auth-input-field" id="reg_email" name="email" placeholder="Email Address" required>
                            </div>
                        </div>

                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">lock</span>
                                <input type="password" class="auth-input-field" id="regpassword" name="password" placeholder="Create Password" required>
                                <span class="material-symbols-outlined auth-visibility-toggle" id="view_password_register">visibility_off</span>
                            </div>
                        </div>

                        <div class="auth-input-group">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">confirmation_number</span>
                                <input type="text" class="auth-input-field" id="promo_code" name="promocode" placeholder="Promo Code (Optional)" value="{{isset($_GET['refer']) ? $_GET['refer'] : ''}}">
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="email_policy" checked required>
                            <label class="form-check-label text-secondary small" for="email_policy">
                                I am 18+ and I agree to the <a href="#" class="auth-link">Terms of Service</a>
                            </label>
                        </div>

                        <button type="submit" class="auth-btn-primary" id="register_via_email">Create Account</button>
                    </form>
                </div>
                <div class="text-center p-4 border-top border-secondary border-opacity-10">
                    <span class="text-secondary small">Already have an account?</span>
                    <a href="javascript:void(0);" class="auth-link small ms-2" onclick="switchModal('login')">Login here</a>
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

    <!--====== Reset Password Modal Start ======-->
    <div class="modal fade l-modal" id="reset-password-modal" tabindex="-1" aria-labelledby="reset-password-modal" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" style="font-size: 18px;">Set New Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form class="login-form" method="post" id="resetPasswordForm">
                        <input type="hidden" name="email" id="reset_email">
                        <input type="hidden" name="otp" id="reset_otp">
                        
                        <div class="auth-input-group mb-3">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">lock</span>
                                <input type="password" class="auth-input-field" id="reset_password" name="password" placeholder="New Password" required>
                            </div>
                        </div>

                        <div class="auth-input-group mb-4">
                            <div class="auth-input-wrapper">
                                <span class="material-symbols-outlined auth-input-icon">lock_reset</span>
                                <input type="password" class="auth-input-field" id="reset_password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label id="reset_error" class="error text-danger small"></label>
                        </div>
                        
                        <button type="submit" class="auth-btn-primary" id="resetSubmit">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--====== Reset Password Modal End ======-->

    <div class="win-loss-popup">
        <div class="win-loss-popup__bg">
            <div class="win-loss-popup__inner">
                <div class="win-loss-popup__body">
                    <img class="img-glow lose d-none"
                        src="{{ asset('assets/xaxino/images/play/lose-message.png') }}"
                        alt="lose message image">
                    <img class="img-glow win d-none"
                        src="{{ asset('assets/xaxino/images/play/win-message.png') }}" alt="win message image">
                </div>
                <div class="win-loss-popup__footer">
                    <h2 class="result-text">@lang('The result is') <span class="data-result"></span></h2>
                    <h5></h5>
                </div>
            </div>
        </div>
    </div>

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
    
    function notify(status, message) {
        if (typeof message == 'string') {
            toastr[status](message);
        } else {
            $.each(message, (i, val) => toastr[status](val));
        }
    }

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
    var is_demo = '{{ @$isDemo }}' === 'demo';
    var wallet_balance = is_demo ? '{{ user('demo_balance', user('id')) }}' : '{{ wallet(user('id')) }}';
    var freebet_balance = '{{ \App\Models\Wallet::where('userid', user('id'))->first()->freebet_amount ?? 0 }}';
    var member_id = '{{ user('id') }}';
    var current_wallet_type = 'money';
    
    window.updateWalletBalance = function(newBalance) {
        if (newBalance !== undefined) {
            if (current_wallet_type === 'freebet') {
                freebet_balance = newBalance;
            } else {
                wallet_balance = newBalance;
            }
        }
        var balance = 0;
        if (current_wallet_type === 'freebet') {
            balance = parseFloat(freebet_balance) || 0;
        } else {
            // If it's a string with commas, remove them first
            var clean_wallet_balance = typeof wallet_balance === 'string' ? wallet_balance.replace(/,/g, '') : wallet_balance;
            balance = parseFloat(clean_wallet_balance) || 0;
        }
        var formattedBalance = balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        var display_prefix = is_demo ? '<small class="text-warning">DEMO</small> ' : '';
        $('#header_wallet_balance').html(display_prefix + currency_symbol + formattedBalance);
    };
    $(document).ready(function() { 
        window.updateWalletBalance(); 
        
        $(document).on('click touchstart', function(e) {
            if ($(e.target).closest('.win-loss-popup__inner').length === 0) {
                $('.win-loss-popup').removeClass('active');
            }
        });
    });
</script>
<script src="{{ asset('js/rain.js') }}"></script>
<script src="{{ asset('js/chat.js') }}"></script>
<script src="{{ asset('js/emoji-picker.js') }}"></script>
@endif

@stack('script-lib')
@stack('script')
@yield('js')
</body>
</html>
