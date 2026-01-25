function gameover(lastint) {
    $.ajax({
        url: '/game/game_over',
        type: "POST",
        data: {
            _token: hash_id,
            "last_time": lastint
        },
        dataType: "text",
        success: function (result) {
            $("#wallet_balance").text(currency_symbol + result);
            $("#header_wallet_balance").text(currency_symbol + result); // Show Header Wallet Balance
            for(let i=0;i < bet_array.length; i++){
                if(bet_array[i] && bet_array[i].is_bet){
                    bet_array.splice(i, 1);
                }
            }
            // bet_array = [];
        }
    });
}
function currentid() {
    $.ajax({
        url: '/game/currentid',
        type: "post",
        data: {
            _token: hash_id
        },
        dataType: "json",
        success: function (result) {
        }
    });
}

/**
 * Flag to prevent multiple game loops
 */
let gameLoopInitialized = false;

/**
 * Flag to track if legacy mode is running (for stopping it when socket connects)
 */
let legacyModeInterval = null;
let legacyModeActive = false;

/**
 * Get the badge color class based on multiplier value (for legacy mode)
 * Matches the glow animation color scheme:
 * - bg3 (Cyan): ≤ 2x
 * - bg1 (Purple): 2x - 10x  
 * - bg2 (Pink/Magenta): ≥ 10x
 */
function getMultiplierBadgeClass(multiplier) {
    const m = parseFloat(multiplier);
    if (m <= 2) {
        return 'bg3'; // Cyan - low multiplier
    } else if (m < 10) {
        return 'bg1'; // Purple - medium multiplier
    } else {
        return 'bg2'; // Pink/Magenta - high multiplier
    }
}

/**
 * Check if socket is connected
 */
function isSocketControlled() {
    if (typeof getAviatorSocket === 'function') {
        const socket = getAviatorSocket();
        return socket && socket.isSocketConnected();
    }
    return false;
}

/**
 * Stop legacy mode if it's running (called when socket connects)
 */
function stopLegacyMode() {
    if (legacyModeInterval) {
        clearInterval(legacyModeInterval);
        legacyModeInterval = null;
        console.log('🛑 Legacy mode interval stopped');
    }
    legacyModeActive = false;
}

/**
 * Socket-aware game generator
 * If socket is connected, SERVER controls the game - client just listens
 * Otherwise, fall back to AJAX polling (legacy mode)
 */
function gamegenerate() {
    // Prevent multiple initializations
    if (gameLoopInitialized) {
        console.log('⚠️ Game loop already initialized, skipping...');
        return;
    }
    gameLoopInitialized = true;
    
    console.log('🎮 Checking socket connection...');
    
    // Hide preloader immediately - socket events will control game UI
    $(".load-txt").hide();
    
    // Check socket connection with longer delay to ensure socket has time to connect
    // Increased from 500ms to 1500ms to prevent race condition
    setTimeout(() => {
        if (isSocketControlled()) {
            console.log('✅ Socket connected - SERVER controls the game');
            console.log('👀 This client is a passive listener - NO local game loop');
            stopLegacyMode(); // Make sure legacy mode is stopped
            initSocketListenerMode();
        } else {
            // Fallback to original AJAX-based game loop
            console.log('⚠️ Socket not connected - using AJAX fallback');
            legacyModeActive = true;
            legacyGamegenerate();
        }
    }, 1500);
}

/**
 * Initialize socket listener mode
 * Client just listens to server events - NO local game control
 * IMPORTANT: This does NOT start any intervals or loops!
 */
function initSocketListenerMode() {
    console.log('📡 Initializing socket listener mode...');
    console.log('📡 All game events come from server via socket');
    console.log('📡 NO local game loop running - pure listener mode');
    
    // Show loading initially - socket events will update UI
    $('.loading-game').addClass('show');
    $("#auto_increment_number_div").hide();
    
    // IMPORTANT: We do NOT start any intervals here!
    // The socket event handlers in aviator-socket-integration.js
    // will handle all UI updates when server sends events:
    // - gamePhase (waiting/countdown)
    // - gameStarted 
    // - multiplierUpdate
    // - gameCrashed
    
    console.log('⏳ Ready - waiting for game events from server...');
}

/**
 * Legacy AJAX-based game generator (fallback when socket not available)
 * NOTE: Delays reduced for faster loading - was 1500ms + 5000ms, now 300ms + 1500ms
 * IMPORTANT: This mode is STOPPED when socket connects (see stopLegacyMode)
 */
function legacyGamegenerate() {
    // Check if socket connected while we were waiting - if so, abort legacy mode
    if (isSocketControlled()) {
        console.log('🔄 Socket connected during legacy startup - aborting legacy mode');
        stopLegacyMode();
        return;
    }
    
    setTimeout(() => {
        // Double-check socket before continuing
        if (isSocketControlled() || !legacyModeActive) {
            console.log('🔄 Socket connected or legacy mode stopped - aborting');
            stopLegacyMode();
            return;
        }
        
        $("#auto_increment_number_div").hide();
        $('.loading-game').addClass('show');
        setTimeout(() => {
            // Triple-check socket before the main game loop
            if (isSocketControlled() || !legacyModeActive) {
                console.log('🔄 Socket connected or legacy mode stopped - aborting');
                stopLegacyMode();
                return;
            }
            
            hide_loading_game();

            $.ajax({
                url: '/game/new_game_generated',
                type: "POST",
                data: {
                    _token: hash_id
                },
                beforeSend: function () {
                },
                dataType: "json",
                success: function (result) {
                    // Check socket again before processing
                    if (isSocketControlled() || !legacyModeActive) {
                        console.log('🔄 Socket connected - stopping legacy game cycle');
                        stopLegacyMode();
                        return;
                    }
                    
                    stage_time_out = 1;
                    if (bet_array.length > 0) {
                        place_bet_now();
                    }
                    $.ajax({
                        url: '/game/currentlybet',
                        type: "POST",
                        data: {
                            _token: hash_id
                        },
                        dataType: "json",
                        success: function (intialData) {
                            info_data(intialData);
                        }
                    });
                    current_game_data = result;
                    hide_loading_game();
                    new_game_generated();
                    lets_fly_one();
                    lets_fly();
                    let currentbet = 0;
                    let a =1.0;
                        $.ajax({
                            url: '/game/increamentor',
                            type: "POST",
                            data: {
                                _token: hash_id
                            },
                            dataType: "json",
                            success: function (data) {
                                currentbet = data.result;
                            
                        $.ajax({
                        url: '/game/currentlybet',
                        type: "POST",
                        data: {
                            _token: hash_id
                        },
                        dataType: "json",
                        success: function (intialData) {
                            info_data(intialData);
                        }
                        });
                    
                    // Store the interval so it can be stopped if socket connects
                    legacyModeInterval = setInterval(() => {
                        // Check if socket connected - if so, stop legacy mode
                        if (isSocketControlled() || !legacyModeActive) {
                            console.log('🔄 Socket connected mid-game - stopping legacy incrementor');
                            stopLegacyMode();
                            return;
                        }
                        
                        if ( a >= currentbet ) {
                            let res = parseFloat(a).toFixed(2);
                            let result = res;
                            crash_plane(result);
                            incrementor(res);
                            gameover(result);
                            $("#all_bets .mCSB_container").empty();
                            $.ajax({
                                url: '/game/my_bets_history',
                                type: "POST",
                                data: {
                                    _token: hash_id
                                },
                                dataType: "json",
                                success: function (data) {
                                    $("#my_bet_list").empty();
                                    for (let $i = 0; $i < data.length; $i++) {
                                        let date = new Date(data[$i].created_at);
                                        $("#my_bet_list").append(`
                                    <div class="list-items">
                                    <div class="column-1 users fw-normal">
                                        `+date.getHours()+`:`+date.getMinutes()+`
                                    </div>
                                    <div class="column-2">
                                        <button
                                            class="btn btn-transparent previous-history d-flex align-items-center mx-auto fw-normal">
                                            `+data[$i].amount+`KSh
                                        </button>
                                    </div>
                                    <div class="column-3">

                                        <div class="${getMultiplierBadgeClass(data[$i].cashout_multiplier)} custom-badge mx-auto">
                                            `+data[$i].cashout_multiplier+`x</div>
                                    </div>
                                    <div class="column-4 fw-normal">
                                        `+Math.round(data[$i].cashout_multiplier*data[$i].amount)+`KSh
                                    </div>
                                </div>
                                `);
                                    }
                                }
                            });
                            clearInterval(legacyModeInterval);
                            legacyModeInterval = null;
                            
                            // Only continue legacy loop if socket still not connected
                            if (!isSocketControlled() && legacyModeActive) {
                                legacyGamegenerate();
                            }
                        } else {
                            a = parseFloat(a) + 0.01;
                            incrementor(parseFloat(a).toFixed(2));
                        }
                    }, 100);
                            }
                        });
                }
            });
        }, 1500);  // Reduced from 5000ms for faster loading
    }, 300);       // Reduced from 1500ms for faster loading
}

function check_game_running(event) {
    
}

$(document).ready(function () {
    check_game_running("check");
    // gamegenerate();
});
