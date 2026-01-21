/**
 * Integration of Socket.IO with existing Aviator game
 * This file bridges the socket client with the existing game logic
 * 
 * SYNCHRONIZED BET SIDEBAR:
 * - All bets are broadcast to all connected clients
 * - Sidebar displays identical data across all tabs/devices
 * - Bets clear at the start of each new round
 */

// Global bet tracking
window.currentRoundBets = [];
window.totalBetsCount = 0;

// Track tab visibility state for resync
window.tabWasHidden = false;
window.lastKnownMultiplier = 1.00;
window.lastMultiplierUpdateTime = Date.now();

// Track animation state to prevent duplicates
window.isAnimationInProgress = false;
window.currentAnimationGameId = null;

// Initialize socket when document is ready
(function() {
    // Wait for both DOM and jQuery to be ready
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded. Aviator Socket Integration requires jQuery.');
        return;
    }
    
    jQuery(document).ready(function($) {
        // Use SOCKET_SERVER_URL from global scope (defined in HTML)
        const serverUrl = window.SOCKET_SERVER_URL || 'http://localhost:3000';
        
        // Initialize socket client
        const socket = initAviatorSocket(serverUrl);
        
        // Setup socket event handlers
        setupSocketEventHandlers(socket);
        
        // Setup visibility change handler for tab resync
        setupVisibilityHandler(socket);
        
        console.log('Aviator Socket Integration initialized');
    });
})();

/**
 * Setup visibility change handler to resync game when tab becomes active
 * This prevents flickering and disorganized animations after tab was dormant
 */
function setupVisibilityHandler(socket) {
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Tab is becoming hidden - mark it
            window.tabWasHidden = true;
            window.hiddenTimestamp = Date.now();
            
            // IMPORTANT: Stop plane animations to prevent corrupted state
            if (typeof window.stopPlaneAnimations === 'function') {
                window.stopPlaneAnimations();
            }
            
            console.log('👁️ Tab hidden - animations stopped, will resync when visible');
        } else {
            // Tab is becoming visible again
            if (window.tabWasHidden) {
                const hiddenDuration = Date.now() - (window.hiddenTimestamp || 0);
                console.log(`👁️ Tab visible again after ${Math.round(hiddenDuration/1000)}s - requesting resync...`);
                
                // Immediately snap UI to last known state to prevent flicker
                resyncUIState();
                
                // DON'T call resumePlaneAnimation here - let the socket sync handle it
                // This prevents duplicate animation triggers
                // The onSyncGameInProgress handler will call showFlyingPlane which starts the animation
                
                // Request current game state from server
                // Server will respond with syncGameInProgress (if game is flying) or gamePhase
                if (socket && socket.isSocketConnected()) {
                    // Use the requestSync method on the socket client
                    if (typeof socket.requestSync === 'function') {
                        socket.requestSync();
                    } else {
                        // Fallback: access raw socket
                        const rawSocket = socket.getSocket ? socket.getSocket() : null;
                        if (rawSocket) {
                            rawSocket.emit('requestSync');
                            console.log('📡 Requested game state sync from server');
                        }
                    }
                }
                
                // Clear the hidden flag after a short delay to allow catch-up events to be skipped
                setTimeout(() => {
                    window.tabWasHidden = false;
                    console.log('✅ Tab resync complete - normal updates resumed');
                }, 200);
            }
        }
    });
    
    console.log('👁️ Visibility handler setup complete');
}

/**
 * Resync UI state after tab becomes visible
 * This resets ALL UI to a clean state - the socket sync will set the correct state
 */
function resyncUIState() {
    console.log('🔄 Resyncing UI state - hiding all overlays until sync completes...');
    
    // IMPORTANT: Hide ALL state-dependent UI elements first
    // The socket sync response will show the correct ones
    $('.loading-game').removeClass('show');      // Hide loading screen
    $('.flew_away_section').hide();              // Hide "flew away" message
    $('.blink-section').removeClass('blink-section-active'); // Stop blink
    
    // Stop any ongoing CSS animations temporarily
    const planeElements = document.querySelectorAll('.plane, .flying-plane, #plane, .bottom-left-plane, .rotateimage');
    planeElements.forEach(el => {
        el.style.transition = 'none';
        el.style.animation = 'none';
    });
    
    // DON'T clear the canvas here - let the sync handler do it
    // This prevents a flash of empty canvas
    
    // Re-enable CSS animations after a brief delay
    setTimeout(() => {
        planeElements.forEach(el => {
            el.style.transition = '';
            el.style.animation = '';
        });
    }, 100);
    
    // Update multiplier display to last known value (prevents showing 1.00x briefly)
    if (window.lastKnownMultiplier > 1.00) {
        updateMultiplierDisplay(window.lastKnownMultiplier);
        if (typeof incrementor === 'function') {
            incrementor(window.lastKnownMultiplier);
        }
    }
}

/**
 * Load current bets from server for the running round
 * NOTE: This is now a fallback - bets are synced via socket
 */
function loadCurrentBets() {
    // Bets are now synced via socket (syncBets event)
    // This function is kept as a fallback
    console.log('📋 Bets are synced via socket');
}

/**
 * Restore user's active bets after page refresh
 * This ensures the user can still cash out if they refresh mid-game
 */
function restoreMyActiveBets() {
    $.ajax({
        url: '/game/my_active_bets',
        type: 'POST',
        data: {
            _token: typeof hash_id !== 'undefined' ? hash_id : $('meta[name="csrf-token"]').attr('content')
        },
        success: function(result) {
            if (result.isSuccess && result.data.active_bets && result.data.active_bets.length > 0) {
                console.log('🔄 Restoring', result.data.active_bets.length, 'active bet(s)');
                
                result.data.active_bets.forEach(function(bet) {
                    // Add bet to bet_array if not already present
                    if (typeof bet_array !== 'undefined') {
                        // Check if bet already exists
                        const existingBet = bet_array.find(b => b.bet_id === bet.bet_id);
                        if (!existingBet) {
                            bet_array.push({
                                bet_id: bet.bet_id,
                                bet_amount: bet.bet_amount,
                                section_no: bet.section_no,
                                bet_type: bet.bet_type,
                                is_bet: 1  // Mark as already placed
                            });
                        }
                    }
                    
                    // Update UI to show cash out button for this bet
                    const sectionId = bet.section_no == 0 ? '#main_bet_section' : '#extra_bet_section';
                    
                    // Show cash out button, hide bet button
                    $(sectionId).find('#bet_button').hide();
                    $(sectionId).find('#cancle_button').hide();
                    $(sectionId).find('#cashout_button').show();
                    $(sectionId).find('.controls').addClass('bet-border-yellow');
                    
                    // Update the bet amount display
                    $(sectionId).find('#bet_amount').val(bet.bet_amount);
                    
                    // Store bet_id in hidden input fields (used by cash_out_now function)
                    if (bet.section_no == 0) {
                        $('#main_bet_id').val(bet.bet_id);
                    } else {
                        $('#extra_bet_id').val(bet.bet_id);
                    }
                    
                    // Update cash out amount display with current multiplier
                    if (typeof incrementor !== 'undefined' && incrementor > 0) {
                        const cashOutAmount = (bet.bet_amount * incrementor).toFixed(2);
                        $(sectionId).find('#cash_out_amount').text(cashOutAmount + currency_symbol);
                    }
                    
                    console.log('✅ Restored bet:', bet.bet_id, 'Amount:', bet.bet_amount, 'Section:', bet.section_no);
                });
                
                // Update game_id for cash out
                if (result.data.game_id) {
                    game_id = result.data.game_id;
                }
            } else {
                console.log('📋 No active bets to restore');
            }
        },
        error: function(err) {
            console.error('❌ Failed to restore active bets:', err);
        }
    });
}

/**
 * Clear all bets from sidebar - called at start of new round
 */
function clearBetsSidebar() {
    // Try multiple container selectors for compatibility
    const container = $("#all_bets .mCSB_container");
    if (container.length > 0) {
        container.empty();
    } else {
        $("#all_bets").empty();
    }
    
    // Reset tracking
    window.currentRoundBets = [];
    window.totalBetsCount = 0;
    
    // Update the counter display
    updateBetCountDisplay(0);
    
    console.log('🧹 Bets sidebar cleared for new round');
}

/**
 * Update the total bets counter display
 */
function updateBetCountDisplay(count) {
    window.totalBetsCount = count;
    $("#total_bets").text(count);
    $(".bet-count").text(count);
}

/**
 * Display a single bet in the sidebar
 * Called when a new bet is placed (broadcast from server)
 */
function displayBetInSidebar(bet) {
    // Try multiple container selectors for compatibility
    let container = $("#all_bets .mCSB_container");
    if (container.length === 0) {
        container = $("#all_bets");
    }
    
    if (container.length === 0) {
        console.log('⚠️ Bet container not found');
        return;
    }
    
    // Check if bet already displayed
    if ($(`#bet-${bet.betId}`).length > 0) {
        return;
    }
    
    const avatar = bet.avatar || '/images/avtar/av-1.png';
    const username = bet.odapu || bet.username || 'Player';
    const amount = parseFloat(bet.amount).toFixed(2);
    const status = bet.status || 'active';
    const cashOutMultiplier = bet.cashOutMultiplier;
    
    // Determine status display
    let statusClass = 'bg1';
    let statusText = '-';
    let winnings = '-';
    
    if (status === 'cashed_out' && cashOutMultiplier) {
        statusClass = 'bg2';
        statusText = cashOutMultiplier.toFixed(2) + 'x';
        winnings = (parseFloat(bet.amount) * cashOutMultiplier).toFixed(2) + 'KSh';
    }
    
    const betHtml = `
        <div class="list-items" id="bet-${bet.betId}" data-bet-id="${bet.betId}" data-user-id="${bet.odapuId || bet.userId}">
            <div class="column-1 users fw-normal">
                <img src="${avatar}" class="user-avatar" style="width:20px;height:20px;border-radius:50%;margin-right:5px;">
                ${username}
            </div>
            <div class="column-2">
                <button class="btn btn-transparent previous-history d-flex align-items-center mx-auto fw-normal">
                    ${amount}KSh
                </button>
            </div>
            <div class="column-3">
                <div class="${statusClass} custom-badge mx-auto bet-status">${statusText}</div>
            </div>
            <div class="column-4 fw-normal bet-winnings">${winnings}</div>
        </div>
    `;
    
    container.prepend(betHtml);
    
    // Track the bet
    window.currentRoundBets.push(bet);
    
    // Update counter
    updateBetCountDisplay(window.currentRoundBets.length);
}

/**
 * Display all bets in sidebar (for sync when client connects/reconnects)
 */
function displayAllBets(bets) {
    // Clear existing bets first
    clearBetsSidebar();
    
    // Add all bets
    bets.forEach(bet => {
        displayBetInSidebar(bet);
    });
    
    console.log(`📋 Synced ${bets.length} bets to sidebar`);
}

/**
 * Update bet status when player cashes out
 */
function updateBetCashOut(betId, multiplier, winAmount) {
    const betElement = $(`#bet-${betId}`);
    if (betElement.length > 0) {
        betElement.find('.bet-status')
            .removeClass('bg1')
            .addClass('bg2')
            .text(multiplier.toFixed(2) + 'x');
        betElement.find('.bet-winnings').text(winAmount.toFixed(2) + 'KSh');
        
        // Add highlight animation
        betElement.addClass('cashed-out-highlight');
        setTimeout(() => {
            betElement.removeClass('cashed-out-highlight');
        }, 2000);
    }
    
    // Update tracking
    const bet = window.currentRoundBets.find(b => b.betId === betId);
    if (bet) {
        bet.status = 'cashed_out';
        bet.cashOutMultiplier = multiplier;
        bet.winAmount = winAmount;
    }
}

/**
 * Show flying plane animation and sync UI
 * Used both for new game start and reconnecting mid-game
 * @param {string} gameId - The current game ID
 * @param {number} multiplier - The current multiplier (1.00 for new game, higher for mid-game sync)
 * @param {boolean} isMidGameSync - Whether this is a mid-game sync (page refresh/reconnect)
 */
function showFlyingPlane(gameId, multiplier, isMidGameSync = false) {
    console.log('🎮 showFlyingPlane called:', { gameId, multiplier, isMidGameSync });
    
    // PREVENT DUPLICATE ANIMATIONS for same game (but allow mid-game sync)
    if (!isMidGameSync) {
        if (window.isAnimationInProgress && window.currentAnimationGameId === gameId) {
            console.log('⚠️ Animation already in progress for game', gameId, '- skipping duplicate');
            return;
        }
    }
    
    // Mark animation as in progress
    window.isAnimationInProgress = true;
    window.currentAnimationGameId = gameId;
    
    // IMPORTANT: First hide ALL conflicting UI elements to prevent overlap
    $('.loading-game').removeClass('show');      // Hide loading screen
    $('.flew_away_section').hide();              // Hide "flew away" message
    $('#auto_increment_number').removeClass('text-danger'); // Reset multiplier color
    
    // Show game elements
    $("#auto_increment_number_div").show();
    
    // Update current game data FIRST (needed for bet placement)
    if (typeof current_game_data !== 'undefined') {
        current_game_data = { id: gameId };
    }
    window.currentGameId = gameId;
    
    // Handle animation based on whether this is mid-game sync or new game
    if (isMidGameSync && multiplier > 1.00) {
        // ============ MID-GAME SYNC ============
        // User is returning to tab during active game
        // Position plane at correct multiplier WITHOUT starting from bottom
        console.log('🔄 Mid-game sync: Positioning plane at', multiplier + 'x');
        
        // Stop ALL existing animations first
        if (typeof window.stopPlaneAnimations === 'function') {
            window.stopPlaneAnimations();
        }
        
        // Add rotating background (in case it was stopped)
        $(".rotateimage").addClass('rotatebg');
        
        // Position plane at current multiplier (NOT starting new animation from bottom)
        if (typeof window.startPlaneAtMultiplier === 'function') {
            // Small delay to ensure clean state after stopping animations
            setTimeout(() => {
                window.startPlaneAtMultiplier(multiplier);
            }, 50);
        }
        
        // Restore user's active bets so they can still cash out
        restoreMyActiveBets();
        
    } else {
        // ============ NEW GAME START ============
        // Fresh game starting from beginning
        console.log('🆕 New game: Starting plane animation from beginning');
        
        // 1. Reset visual elements (UI state, buttons, etc)
        if (typeof new_game_generated === 'function') {
            new_game_generated();
        }
        
        // 2. Place any pending bets when the game starts
        if (typeof bet_array !== 'undefined' && bet_array.length > 0) {
            console.log('💰 Placing pending bets:', bet_array.length, 'bet(s)');
            if (typeof place_bet_now === 'function') {
                place_bet_now();
            }
        }
        
        // 3. Start plane animation sequence from beginning
        // lets_fly_one adds blink effect, lets_fly starts the actual animation
        if (typeof lets_fly_one === 'function') {
            lets_fly_one();
        }
        if (typeof lets_fly === 'function') {
            lets_fly();
        }
    }
    
    // Set multiplier display
    if (typeof incrementor === 'function') {
        incrementor(multiplier);
    }
    updateMultiplierDisplay(multiplier);
    
    // Load current bets for this round
    loadCurrentBets();
    
    console.log('✈️ Plane flying - multiplier:', multiplier + 'x', isMidGameSync ? '(synced)' : '(new game)');
}

/**
 * Setup all socket event handlers
 * These handlers ensure all tabs/devices show the same bet data
 */
function setupSocketEventHandlers(socket) {
    // Connection status handler
    socket.on('onConnectionChange', (data) => {
        if (data.connected) {
            console.log('✓ Connected to game server');
            // Don't show toastr for connection - too noisy
        } else {
            console.log('✗ Disconnected from game server');
            if (typeof toastr !== 'undefined') {
                toastr.warning('Disconnected from game server. Reconnecting...');
            }
        }
    });

    // Game phase handler - SERVER controls phases
    socket.on('onGamePhase', (data) => {
        console.log('🎮 [SERVER] Game phase:', data.phase);
        
        if (data.phase === 'waiting') {
            // Waiting for bets phase - NEW ROUND STARTING
            // Hide any previous game elements
            $('.flew_away_section').hide();
            $('#auto_increment_number').removeClass('text-danger');
            $("#auto_increment_number_div").hide();
            
            // Show loading screen
            $('.loading-game').addClass('show');
            
            // IMPORTANT: Reset animation state for new round
            window.isAnimationInProgress = false;
            window.currentAnimationGameId = null;
            window.lastKnownMultiplier = 1.00;
            
            // Stop any running plane animations
            if (typeof window.stopPlaneAnimations === 'function') {
                window.stopPlaneAnimations();
            }
            
            console.log('⏳ Waiting for next round... (' + (data.duration/1000) + 's)');
            
        } else if (data.phase === 'countdown') {
            // Countdown phase before game starts
            // Keep loading screen visible during countdown
            $('.loading-game').addClass('show');
            $('.flew_away_section').hide();
            
            console.log('🕐 Game starting in ' + (data.duration/1000) + ' seconds...');
            
        } else if (data.phase === 'crashed') {
            // Game just crashed - show flew away
            $('.loading-game').removeClass('show');
            $('.flew_away_section').show();
            console.log('💥 Game crashed');
        }
    });

    // Game started handler - ALL TABS receive this
    socket.on('onGameStarted', (data) => {
        console.log('🎮 [SOCKET EVENT] Game started with ID:', data.gameId);
        console.log('📡 All tabs should now show the same game!');
        
        // New game starting - plane starts from beginning
        showFlyingPlane(data.gameId, 1.00, false);
    });
    
    // Sync game in progress - for clients that reconnect mid-game
    socket.on('onSyncGameInProgress', (data) => {
        console.log('🔄 [SYNC] Joining game in progress at', data.multiplier + 'x');
        
        // Mid-game sync - position plane at current multiplier
        // restoreMyActiveBets is called inside showFlyingPlane for mid-game sync
        showFlyingPlane(data.gameId, data.multiplier, true);
    });
    
    // Sync bets list - for clients that connect/reconnect
    // Server event name is 'syncBets', client receives as 'onSyncBets' via socket-client.js wrapper
    socket.on('onSyncBets', (data) => {
        console.log('🔄 [SYNC] Received bets list:', data.bets.length, 'bets');
        displayAllBets(data.bets);
    });
    
    // New bet placed - broadcast to ALL clients
    // This ensures all tabs/devices see the same bets
    socket.on('onBetPlaced', (data) => {
        console.log('💰 [SOCKET] Bet placed:', data.username || data.odapu, data.amount + 'KSh');
        displayBetInSidebar(data);
    });
    
    // Player cashed out - update UI for ALL clients
    socket.on('onPlayerCashedOut', (data) => {
        console.log('💸 [SOCKET] Player cashed out:', data.username, 'at', data.multiplier + 'x', data.isAutoCashout ? '(AUTO)' : '');
        updateBetCashOut(data.betId, data.multiplier, data.winAmount);
    });
    
    // Auto cash-out triggered by server - update UI for this specific player
    socket.on('onAutoCashoutTriggered', (data) => {
        console.log('🤖 [AUTO CASH-OUT] Server triggered auto cash-out for bet', data.betId, 'at', data.multiplier + 'x');
        
        // Play cash-out sound
        if (data.sectionNo == 0) {
            if (typeof cashOutSound === 'function') cashOutSound();
        } else {
            if (typeof cashOutSoundOtherSection === 'function') cashOutSoundOtherSection();
        }
        
        // Update the wallet balance - fetch fresh balance from server
        updateWalletBalance();
        
        // Show the cash-out toaster notification
        const amt = (parseFloat(data.winAmount)).toFixed(2);
        if (data.sectionNo == 0) {
            $(".cashout-toaster1 .stop-number").html(data.multiplier + 'x');
            $(".cashout-toaster1 .out-amount").html(amt + currency_symbol);
            $(".cashout-toaster1").addClass('show');
            if (typeof firstToastr === 'function') firstToastr();
            
            // Reset main section UI
            $("#main_bet_section").find("#bet_button").show();
            $("#main_bet_section").find("#cancle_button").hide();
            $("#main_bet_section").find("#cashout_button").hide();
            $("#main_bet_section .controls").removeClass('bet-border-yellow');
        } else if (data.sectionNo == 1) {
            $(".cashout-toaster2 .stop-number").html(data.multiplier + 'x');
            $(".cashout-toaster2 .out-amount").html(amt + currency_symbol);
            $(".cashout-toaster2").addClass('show');
            if (typeof secondToastr === 'function') secondToastr();
            
            // Reset extra section UI
            $("#extra_bet_section").find("#bet_button").show();
            $("#extra_bet_section").find("#cancle_button").hide();
            $("#extra_bet_section").find("#cashout_button").hide();
            $("#extra_bet_section .controls").removeClass('bet-border-yellow');
        }
        
        // Remove bet from local bet_array
        if (typeof bet_array !== 'undefined') {
            for (let i = bet_array.length - 1; i >= 0; i--) {
                if (bet_array[i].section_no == data.sectionNo) {
                    bet_array.splice(i, 1);
                    break;
                }
            }
        }
    });

    // Multiplier update handler - ALL TABS receive the SAME multiplier
    socket.on('onMultiplierUpdate', (data) => {
        const now = Date.now();
        const timeSinceLastUpdate = now - window.lastMultiplierUpdateTime;
        
        // Track last known multiplier for resync
        window.lastKnownMultiplier = data.multiplier;
        window.lastMultiplierUpdateTime = now;
        
        // If tab was hidden and we're getting rapid updates (catch-up), skip intermediate ones
        // This prevents the flickering caused by processing queued events
        if (document.hidden) {
            // Tab is hidden - just track state, don't update UI
            return;
        }
        
        // If we're catching up (updates coming faster than 50ms apart after being hidden)
        // Skip to prevent animation overload
        if (timeSinceLastUpdate < 50 && window.tabWasHidden) {
            return;
        }
        
        // Log every 0.5x increase for debugging
        if (Math.floor(data.multiplier * 2) !== Math.floor((data.multiplier - 0.01) * 2)) {
            console.log('📈 [SYNC] Multiplier:', data.multiplier.toFixed(2) + 'x');
        }
        
        // Update multiplier display
        if (typeof incrementor === 'function') {
            incrementor(data.multiplier);
        }
        
        // Update DOM elements
        updateMultiplierDisplay(data.multiplier);
        
        // Update cash out amounts for active bets in sidebar
        updateActiveBetsCashOutAmounts(data.multiplier);
    });

    // Game crashed handler - ALL clients receive this from server
    socket.on('onGameCrashed', (data) => {
        console.log('💥 [SERVER] Game crashed at', data.crashMultiplier + 'x');
        
        // IMPORTANT: Reset animation state so next game can start
        window.isAnimationInProgress = false;
        window.currentAnimationGameId = null;
        window.lastKnownMultiplier = 1.00; // Reset multiplier tracker
        
        // Hide loading screen (in case it was showing)
        $('.loading-game').removeClass('show');
        
        // Mark all remaining active bets as lost in the sidebar
        markActiveBetsAsLost();
        
        // Show crash animation - this handles:
        // - Playing crash sound
        // - Showing "FLEW AWAY" message
        // - Stopping plane animation
        if (typeof crash_plane === 'function') {
            crash_plane(data.crashMultiplier);
        }
        
        // Update game over display
        if (typeof gameover === 'function') {
            gameover(data.crashMultiplier);
        }
        
        // Refresh bet history (My Bets tab)
        refreshBetHistory();
        
        // Update wallet balance
        updateWalletBalance();
        
        // Server will automatically start the next game cycle
        // The 'waiting' phase event will hide flew_away and show loading
        console.log('⏳ Waiting for server to start next round...');
    });
    
    // Game reset handler - prepare for new round
    socket.on('onGameReset', (data) => {
        console.log('🔄 [SERVER] Game reset - preparing for new round');
        clearBetsSidebar();
    });
}

/**
 * Update the potential cash out amounts displayed for active bets
 */
function updateActiveBetsCashOutAmounts(multiplier) {
    // Update each active bet's potential winnings display
    window.currentRoundBets.forEach(bet => {
        if (bet.status === 'active') {
            const betElement = $(`#bet-${bet.betId}`);
            if (betElement.length > 0 && !betElement.hasClass('cashed-out')) {
                const potentialWin = (parseFloat(bet.amount) * multiplier).toFixed(2);
                // Optionally show potential winnings - uncomment if desired
                // betElement.find('.bet-winnings').text(potentialWin + 'KSh');
            }
        }
    });
}

/**
 * Mark all active bets as lost when game crashes
 */
function markActiveBetsAsLost() {
    window.currentRoundBets.forEach(bet => {
        if (bet.status === 'active') {
            const betElement = $(`#bet-${bet.betId}`);
            if (betElement.length > 0) {
                betElement.find('.bet-status')
                    .removeClass('bg1 bg2')
                    .addClass('bg3')
                    .text('Lost');
                betElement.find('.bet-winnings').text('-');
                betElement.addClass('bet-lost');
            }
            bet.status = 'lost';
        }
    });
}

/**
 * Update multiplier display
 */
function updateMultiplierDisplay(multiplier) {
    const formattedMultiplier = parseFloat(multiplier).toFixed(2);
    
    // Update main multiplier display
    $('#current_multiplier').text(formattedMultiplier + 'x');
    $('.multiplier-value').text(formattedMultiplier + 'x');
    
    // Update any other multiplier elements
    $('.game-multiplier').text(formattedMultiplier);
}

/**
 * Add bet to display
 */
function addBetToDisplay(betData) {
    const betHtml = `
        <div class="bet-item" data-bet-id="${betData.betId}">
            <div class="bet-user">
                <img src="${betData.avatar || '/images/avtar/av-1.png'}" class="avatar" />
                <span class="username">${betData.username || 'Player ' + betData.userId}</span>
            </div>
            <div class="bet-amount">
                ${currency_symbol}${betData.amount}
            </div>
        </div>
    `;
    
    $("#all_bets .mCSB_container").prepend(betHtml);
}

/**
 * Update cash out display
 */
function updateCashOutDisplay(data) {
    const betElement = $(`.bet-item[data-bet-id="${data.betId}"]`);
    if (betElement.length) {
        betElement.addClass('cashed-out');
        betElement.append(`
            <div class="cashout-info">
                <span class="cashout-multiplier">${data.multiplier}x</span>
                <span class="cashout-amount">${currency_symbol}${data.winAmount.toFixed(2)}</span>
            </div>
        `);
    }
}

/**
 * Refresh bet history
 */
function refreshBetHistory() {
    $.ajax({
        url: '/game/my_bets_history',
        type: "POST",
        data: {
            _token: hash_id
        },
        dataType: "json",
        success: function (data) {
            $("#my_bet_list").empty();
            for (let i = 0; i < data.length; i++) {
                let date = new Date(data[i].created_at);
                $("#my_bet_list").append(`
                    <div class="list-items">
                        <div class="column-1 users fw-normal">
                            ${date.getHours()}:${date.getMinutes()}
                        </div>
                        <div class="column-2">
                            <button class="btn btn-transparent previous-history d-flex align-items-center mx-auto fw-normal">
                                ${data[i].amount}KSh
                            </button>
                        </div>
                        <div class="column-3">
                            <div class="bg3 custom-badge mx-auto">
                                ${data[i].cashout_multiplier}x
                            </div>
                        </div>
                        <div class="column-4 fw-normal">
                            ${Math.round(data[i].cashout_multiplier * data[i].amount)}KSh
                        </div>
                    </div>
                `);
            }
        }
    });
}

/**
 * Update wallet balance
 */
function updateWalletBalance() {
    $.ajax({
        url: '/get_user_details',
        type: "GET",
        dataType: "json",
        success: function(result) {
            if (result && result.wallet) {
                $("#wallet_balance").text(currency_symbol + result.wallet);
                $("#header_wallet_balance").text(currency_symbol + result.wallet);
            }
        }
    });
}

/**
 * Update bet count
 */
function updateBetCount() {
    const betCount = $("#all_bets .mCSB_container .bet-item").length;
    $('.bet-count').text(betCount);
}

/**
 * Place bet via socket
 */
function placeSocketBet(betAmount, betType, sectionNo) {
    const socket = getAviatorSocket();
    
    if (!socket || !socket.isSocketConnected()) {
        console.error('Socket not connected');
        if (typeof toastr !== 'undefined') {
            toastr.error('Not connected to game server');
        }
        return Promise.reject('Socket not connected');
    }

    const betData = {
        userId: user_id,
        username: username,
        amount: betAmount,
        betId: Date.now() + '_' + user_id,
        avatar: user_avatar || '/images/avtar/av-1.png',
        betType: betType,
        sectionNo: sectionNo
    };

    return socket.placeBet(betData);
}

/**
 * Cash out via socket
 */
function cashOutSocket(betId, multiplier) {
    const socket = getAviatorSocket();
    
    if (!socket || !socket.isSocketConnected()) {
        console.error('Socket not connected');
        if (typeof toastr !== 'undefined') {
            toastr.error('Not connected to game server');
        }
        return Promise.reject('Socket not connected');
    }

    return socket.cashOut(betId, user_id, username, multiplier);
}

/**
 * Start game via socket (admin/server function)
 */
function startSocketGame(gameId, targetMultiplier) {
    const socket = getAviatorSocket();
    
    if (!socket || !socket.isSocketConnected()) {
        console.error('Socket not connected');
        return;
    }

    socket.startGame(gameId, targetMultiplier);
}
