<!--====== Header Start ======-->
<style>
    /* Wagering Bar Mobile Styles */
    @media (max-width: 767px) {
        #wagering_container {
            position: absolute !important;
            top: 45px !important; /* Move it lower under logo/wallet */
            left: 50% !important;
            transform: translateX(-50%) !important;
            min-width: 75px !important;
            width: 40% !important;
            z-index: 100 !important;
        }
        #wagering_container .wagering-text-row {
            display: none !important; /* Hide "WAGERING" and "KSh 0/0" */
        }
        #wagering_container .progress {
            height: 2px !important; /* Slimmer bar */
            margin-top: 2px !important;
        }
        #wagering_container .mobile-info-icon {
            display: block !important;
            position: absolute;
            right: -20px;
            top: -5px;
        }
    }
    @media (min-width: 768px) {
        .mobile-info-icon {
            display: none !important;
        }
    }
    .wagering-hidden {
        display: none !important;
    }

    /* Server Status Bulb Styles */
    .server-status {
        display: flex;
        align-items: center;
        gap: 6px;
        background: rgba(0,0,0,0.3);
        padding: 4px 10px;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.1);
        height: 26px;
    }
    .status-bulb {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    .status-bulb.connected {
        background: #00ff88;
        box-shadow: 0 0 8px #00ff88;
    }
    .status-bulb.disconnected {
        background: #ff3232;
        box-shadow: 0 0 8px #ff3232;
        animation: statusBlink 1s infinite;
    }
    @keyframes statusBlink {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.1); }
        100% { opacity: 1; transform: scale(1); }
    }
    .status-text {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>
<header>
    <div class="header-top">
        <div class="header-left d-flex align-items-center">
            <a href="dashboard">
                <img src="../../images/logo.png" class="logo1" />
            </a>
        </div>

        <!-- Wagering Progress UI - Moved to top for visibility -->
        <div id="wagering_container" class="header-center d-flex flex-column align-items-center wagering-hidden" style="min-width: 60px; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);">
            <div style="background: rgba(0,0,0,0.8); padding: 1px 6px; border-radius: 6px 6px 0 0; border: 1px solid rgba(255,149,0,0.5); border-bottom: none; width: 100%; position: relative;">
                <div class="d-flex justify-content-between align-items-center mb-0 wagering-text-row" style="gap: 5px;">
                    <span style="color: #ff9500; font-weight: 800; font-size: 6.5px; letter-spacing: 0.2px; display: flex; align-items: center; white-space: nowrap;">
                        WAG
                        <span class="material-symbols-outlined ms-1" style="font-size: 8px; cursor: pointer; color: #fff;" data-bs-toggle="modal" data-bs-target="#freebet-rules-modal">info</span>
                    </span>
                    <span id="wagering_text" style="color: #fff; font-size: 7px; font-weight: 600; white-space: nowrap;">0/0</span>
                </div>
                <div class="progress" style="height: 2px; background: #000; border-radius: 4px; overflow: hidden; margin-bottom: 1px;">
                    <div id="wagering_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="header-right d-flex align-items-center">
            <!-- Wagering Info Icon for Mobile - Next to header-right items -->
            <span id="mobile_wagering_info" class="material-symbols-outlined me-2 wagering-hidden d-md-none" style="font-size: 20px; cursor: pointer; color: #ff9500;" data-bs-toggle="modal" data-bs-target="#freebet-rules-modal">info</span>
            
            <!-- Admin Debug: isadmin={{ user('isadmin') ?? 'NULL' }}, type={{ gettype(user('isadmin')) }} -->
            @if(user('isadmin') == '1' || user('isadmin') == 1)
            <!-- ADMIN BUTTON SHOULD SHOW HERE -->
            <a href="/manage_jet_secure">
                <button class="admin-shortcut-btn d-flex align-items-center me-2" title="Admin Panel">
                    <span class="material-symbols-outlined"> admin_panel_settings </span>
                </button>
            </a>
            @else
            <!-- ADMIN BUTTON HIDDEN: isadmin is {{ user('isadmin') ?? 'NULL' }} -->
            @endif
            <a href="/deposit">
                <button class="deposite-btn d-flex align-items-center me-2">
                    <span class="material-symbols-outlined me-2"> account_balance_wallet </span>
                    <!-- <span>$</span> -->
                    <span class="me-2" id="header_wallet_balance">KSh{{ wallet(user('id')) }}</span>
                    DEPOSIT
                </button>
            </a>
            <!-- Removed Settings group from here -->

            <div class="btn-group">
                <button type="button"
                    onclick="$(this).next('.dropdown-menu').toggleClass('show'); $(this).parent().toggleClass('show'); event.stopPropagation();"
                    class="btn btn-transparent p-0 d-flex align-items-center justify-content-center caret-none">
                    <span class="material-symbols-outlined f-24 menu-icon text-white">
                        menu
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark profile-dropdown p-0">
                        <li class="profile-head d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="{{user('image')}}" class="avtar-ico" id="avatar_img">
                                <div>
                                    <div class="profile-name mb-1">{{ user('email') }} </div>
                                    <div class="profile-name" id="username">{{ user('id') }}</div>
                                </div>

                            </div>
                        </li>
					
					<li class="divider"> </li>
                        <li>
                            <a href="/crash" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        flight_takeoff
                                    </span>
                                    <img src="../../images/logo.svg" class="side_logo">
                                </div>
                            </a>
                        </li>

                        <!-- Debug: isadmin = {{ user('isadmin') ?? 'NULL' }} -->
                        @if(user('isadmin') == '1' || user('isadmin') == 1)
                        <li>
                            <a href="/manage_jet_secure" class="f-12 justify-content-between" style="background: linear-gradient(90deg, #FF9500, #FFA500); color: #000; font-weight: 600;">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        admin_panel_settings
                                    </span>ADMIN PANEL
                                </div>
                            </a>
                        </li>
                        @endif

                        <li>
                            <a href="/deposit" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>DEPOSIT FUNDS
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/withdraw" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>WITHDRAW FUNDS FROM THE ACCOUNT
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/amount-transfer" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>AMOUNT TRANSFER
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/profile" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        account_circle
                                    </span>PERSONAL DETAILS
                                </div>
                            </a>
                        </li>
                        {{-- <li>
                            <a href="#" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>TRANSFER FUNDS
                                </div>
                            </a>
                        </li> --}}
                        <li>
                            <a href="/deposit_withdrawals" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>TRANSACTION HISTORY
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/level-management" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>LEVEL MANAGEMENT
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/referal" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>REFERRAL
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/logout" class="f-12 justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="material-symbols-outlined ico f-20">
                                        payments
                                    </span>SIGN OUT
                                </div>
                            </a>
                        </li>
                    </ul>
            </div>




        </div>
    </div>
    <div class="header-bottom flex-wrap">
        <div class="header-left">
            <img src="../../images/logo.svg" class="logo" />
        </div>
        
        <!-- Wallet Toggle Button - Center -->
        <div class="header-center">
            <div class="wallet-toggle-container">
                <button class="wallet-toggle-btn active" data-wallet="money" onclick="switchWalletType('money')">
                    Money
                </button>
                <button class="wallet-toggle-btn" data-wallet="freebet" onclick="switchWalletType('freebet')">
                    Use Freebet
                </button>
            </div>
        </div>
        
        <div class="header-right d-flex align-items-center">
            <!-- Game Settings Dropdown Moved Here -->
            <div class="btn-group me-2">
                <button type="button"
                    onclick="$(this).next('.dropdown-menu').toggleClass('show'); $(this).parent().toggleClass('show'); event.stopPropagation();"
                    class="btn btn-transparent p-0 d-flex align-items-center justify-content-center caret-none"
                    title="Game Settings">
                    <span class="material-symbols-outlined f-22 text-white" style="opacity: 0.8;">
                        settings
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark profile-dropdown p-0">
                    <li class="profile-head text-center py-2" style="background: rgba(255,255,255,0.05);">
                        <div class="f-12 fw-bold text-uppercase" style="color: #ff9500;">Game Settings</div>
                    </li>
					<li>
                <a class="f-12 justify-content-between">
                    <div class="d-flex align-items-center">
                        <span class="material-symbols-outlined ico">
                            volume_mute
                        </span>SOUND
                        
                    </div>
                    <div>
                        <div class="form-check form-switch lg-switch">
                            <input class="form-check-input audio-toggle" type="checkbox" role="switch" id="sound" data-type="sound">
                            <label class="form-check-label" for="sound"></label>
                        </div>
                    </div>
                </a>
            </li>
					<li>
                <a class="f-12 justify-content-between">
                    <div class="d-flex align-items-center">
                        <span class="material-symbols-outlined ico f-22">
                            music_note
                        </span>MUSIC
                    </div>
                    <div>
                        <div class="form-check form-switch lg-switch">
                            <input class="form-check-input audio-toggle" type="checkbox" role="switch" id="music" data-type="music">
                            <label class="form-check-label" for="music"></label>
                        </div>
                    </div>
                </a>
            </li>
            
            <script>
                /**
                 * Persistent Audio Logic
                 * Saves preferences to LocalStorage so they stay the same after refresh
                 */
                document.addEventListener('DOMContentLoaded', function() {
                    const soundToggle = document.getElementById('sound');
                    const musicToggle = document.getElementById('music');

                    // 1. SET DEFAULTS (Sound ON, Music OFF)
                    if (localStorage.getItem('jet_sound_pref') === null) {
                        localStorage.setItem('jet_sound_pref', 'on');
                        localStorage.setItem('jet_music_pref', 'off');
                    }

                    // 2. APPLY SAVED PREFERENCE TO SWITCHES
                    soundToggle.checked = (localStorage.getItem('jet_sound_pref') === 'on');
                    musicToggle.checked = (localStorage.getItem('jet_music_pref') === 'on');

                    // 3. LISTEN FOR CHANGES
                    const handleToggle = (e) => {
                        const type = e.target.getAttribute('data-type');
                        const isChecked = e.target.checked;
                        localStorage.setItem('jet_' + type + '_pref', isChecked ? 'on' : 'off');
                        
                        // Immediately apply to background music if it exists
                        const bgMusic = document.getElementById('background_Audio');
                        if (type === 'music' && bgMusic) {
                            if (isChecked) {
                                bgMusic.play().catch(err => console.log("User must interact first"));
                            } else {
                                bgMusic.pause();
                            }
                        }
                    };

                    soundToggle.addEventListener('change', handleToggle);
                    musicToggle.addEventListener('change', handleToggle);
                });
            </script>
					<li>
                <a class="f-12 justify-content-between">
                    <div class="d-flex align-items-center">
                        <span class="material-symbols-outlined ico f-20">
                            mode_fan
                        </span>ANIMATION
                    </div>
                    <div>
                        <div class="form-check form-switch lg-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="animation" checked="">
                            <label class="form-check-label" for="animation"></label>
                        </div>
                    </div>
                </a>
            </li>
            <li class="divider"> </li>
            <li>
                <a class="f-12 justify-content-between" data-bs-toggle="modal" data-bs-target="#game-rule" style="cursor:pointer;">
                    <div class="d-flex align-items-center">
                        <span class="material-symbols-outlined ico f-20">
                            help
                        </span>GAME RULES
                    </div>
                </a>
            </li>
                </ul>
            </div>

            <!-- <button class="btn btn-warning m-font-0 rounded-pill py-1 px-2 f-14 d-flex align-items-center h-26" data-bs-toggle="modal" data-bs-target="#how-to-play">
            <span class="material-symbols-outlined f-18 me-1">
                help
            </span> How to Play
        </button> -->
            <div class="server-status h-26" title="Game Server Connection">
                <div id="socket_bulb" class="status-bulb disconnected"></div>
                <span id="socket_status_text" class="status-text text-danger">Offline</span>
            </div>
        </div>
    </div>
</header>
<!--====== Header End ======-->

<!--====== Freebet Wagering Rules Modal ======-->
<div class="modal fade" id="freebet-rules-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="freebet-rules-title">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title secondary-font" id="freebet-rules-title" style="color: #ff9500;">FREEBET WAGERING RULES</h5>
                <button type="button" class="btn btn-transparent text-white p-0" data-bs-dismiss="modal" aria-label="Close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-4 text-center">
                    <div class="p-3 mb-2" style="background: rgba(255,149,0,0.1); border-radius: 12px; border: 1px dashed #ff9500;">
                        <h2 class="mb-0" style="color: #ff9500; font-weight: 800;">{{ setting('freebet_wagering_multiplier', 10) }}x</h2>
                        <div class="small text-uppercase" style="letter-spacing: 1px;">Wagering Requirement</div>
                    </div>
                    <p class="text-grey small">Play through your bonus amount to unlock it as real cash!</p>
                </div>

                <ul class="list-unstyled">
                    <li class="d-flex mb-3">
                        <span class="material-symbols-outlined text-warning me-3">trending_up</span>
                        <div>
                            <strong class="d-block">Minimum Multiplier: {{ setting('freebet_min_multiplier', 1.50) }}x</strong>
                            <span class="text-grey small">Only bets cashed out at or above this multiplier count towards wagering.</span>
                        </div>
                    </li>
                    <li class="d-flex mb-3">
                        <span class="material-symbols-outlined text-warning me-3">cash_refresh</span>
                        <div>
                            <strong class="d-block">Auto-Conversion</strong>
                            <span class="text-grey small">Once progress reaches 100%, your Freebet balance is automatically moved to your real money wallet.</span>
                        </div>
                    </li>
                    <li class="d-flex mb-3">
                        <span class="material-symbols-outlined text-warning me-3">account_balance_wallet</span>
                        <div>
                            <strong class="d-block">Wallet Switching</strong>
                            <span class="text-grey small">Use the "Use Freebet" toggle in the header to switch between your real cash and bonus funds.</span>
                        </div>
                    </li>
                </ul>
                
                <div class="mt-3 p-2 bg-black rounded border border-secondary text-center">
                    <span class="text-grey small">Current conversion rate: 1.00 Freebet = 1.00 Real Cash</span>
                </div>
            </div>
        </div>
    </div>
</div>

