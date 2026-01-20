/**
 * Integration of Socket.IO with existing Aviator game
 * This file bridges the socket client with the existing game logic
 */

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
        
        console.log('Aviator Socket Integration initialized');
    });
})();

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
 * Display a single bet in the sidebar
 */
function displayBetInSidebar(bet) {
    const container = $("#all_bets .mCSB_container");
    if (container.length === 0) {
        console.log('⚠️ Bet container not found');
        return;
    }
    
    // Check if bet already displayed
    if ($(`#bet-${bet.betId}`).length > 0) {
        return;
    }
    
    const avatar = bet.avatar || '/images/avtar/user.png';
    const username = bet.odapu || bet.username || 'Player';
    const amount = parseFloat(bet.amount).toFixed(2);
    
    const betHtml = `
        <div class="list-items" id="bet-${bet.betId}" data-bet-id="${bet.betId}">
            <div class="column-1 users fw-normal">
                <img src="${avatar}" class="user-avatar" style="width:20px;height:20px;border-radius:50%;margin-right:5px;">
                ${username}
            </div>
            <div class="column-2">
                <button class="btn btn-transparent previous-history d-flex align-items-center mx-auto fw-normal">
                    ${amount}₹
                </button>
            </div>
            <div class="column-3">
                <div class="bg1 custom-badge mx-auto bet-status">Betting</div>
            </div>
            <div class="column-4 fw-normal bet-winnings">-</div>
        </div>
    `;
    
    container.prepend(betHtml);
}

/**
 * Display all bets in sidebar (for sync)
 */
function displayAllBets(bets) {
    const container = $("#all_bets .mCSB_container");
    container.empty();
    
    bets.forEach(bet => {
        displayBetInSidebar(bet);
    });
    
    console.log(`📋 Displayed ${bets.length} bets in sidebar`);
}

/**
 * Update bet status when player cashes out
 */
function updateBetCashOut(betId, multiplier, winAmount) {
    const betElement = $(`#bet-${betId}`);
    if (betElement.length > 0) {
        betElement.find('.bet-status').removeClass('bg1').addClass('bg2').text(multiplier.toFixed(2) + 'x');
        betElement.find('.bet-winnings').text(winAmount.toFixed(2) + '₹');
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
    // Hide loading screen
    if (typeof hide_loading_game === 'function') {
        hide_loading_game();
    }
    $('.loading-game').removeClass('show');
    
    // Show game elements
    $("#auto_increment_number_div").show();
    
    // Reset visual elements
    if (typeof new_game_generated === 'function') {
        new_game_generated();
    }
    
    // Start plane animation - handle mid-game sync differently
    if (isMidGameSync && multiplier > 1.00 && typeof window.startPlaneAtMultiplier === 'function') {
        // Mid-game sync: Position plane at current multiplier position
        console.log('🔄 Syncing plane position to multiplier:', multiplier + 'x');
        window.startPlaneAtMultiplier(multiplier);
    } else {
        // New game: Start from beginning
        if (typeof lets_fly_one === 'function') {
            lets_fly_one();
        }
        if (typeof lets_fly === 'function') {
            lets_fly();
        }
    }
    
    // Update current game data
    if (typeof current_game_data !== 'undefined') {
        current_game_data = { id: gameId };
    }
    window.currentGameId = gameId;
    
    // Set multiplier display (important for reconnects)
    if (typeof incrementor === 'function') {
        incrementor(multiplier);
    }
    
    // Load current bets for this round (especially important for reconnects)
    loadCurrentBets();
    
    console.log('✈️ Plane flying - multiplier:', multiplier + 'x', isMidGameSync ? '(synced)' : '(new game)');
}

/**
 * Setup all socket event handlers
 */
function setupSocketEventHandlers(socket) {
    // Connection status handler
    socket.on('onConnectionChange', (data) => {
        if (data.connected) {
            console.log('✓ Connected to game server');
            if (typeof toastr !== 'undefined') {
                toastr.success('Connected to game server');
            }
        } else {
            console.log('✗ Disconnected from game server');
            if (typeof toastr !== 'undefined') {
                toastr.warning('Disconnected from game server');
            }
        }
    });

    // Game phase handler - SERVER controls phases
    socket.on('onGamePhase', (data) => {
        console.log('🎮 [SERVER] Game phase:', data.phase);
        
        if (data.phase === 'waiting') {
            // Waiting for bets phase - NEW ROUND STARTING
            $('.loading-game').addClass('show');
            $("#auto_increment_number_div").hide();
            console.log('⏳ Waiting for next round... (' + (data.duration/1000) + 's)');
            
            // Clear old bets - new bets will come via socket betPlaced events
            $("#all_bets .mCSB_container").empty();
            
        } else if (data.phase === 'countdown') {
            // Countdown phase before game starts
            console.log('🕐 Game starting in ' + (data.duration/1000) + ' seconds...');
            $('.loading-game').addClass('show');
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
        showFlyingPlane(data.gameId, data.multiplier, true);
    });
    
    // Sync bets list - for clients that connect/reconnect
    socket.on('onSyncBets', (data) => {
        console.log('🔄 [SYNC] Received bets list:', data.bets.length, 'bets');
        displayAllBets(data.bets);
    });
    
    // New bet placed - broadcast to ALL clients
    socket.on('onBetPlaced', (data) => {
        console.log('💰 [SOCKET] Bet placed:', data.username, data.amount + '₹');
        displayBetInSidebar(data);
    });
    
    // Player cashed out - update UI for ALL clients
    socket.on('onPlayerCashedOut', (data) => {
        console.log('💸 [SOCKET] Player cashed out:', data.username, 'at', data.multiplier + 'x');
        updateBetCashOut(data.betId, data.multiplier, data.winAmount);
    });

    // Multiplier update handler - ALL TABS receive the SAME multiplier
    socket.on('onMultiplierUpdate', (data) => {
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
    });

    // Game crashed handler - ALL clients receive this from server
    socket.on('onGameCrashed', (data) => {
        console.log('💥 [SERVER] Game crashed at', data.crashMultiplier + 'x');
        
        // Show crash animation
        if (typeof crash_plane === 'function') {
            crash_plane(data.crashMultiplier);
        }
        
        // Update game over
        if (typeof gameover === 'function') {
            gameover(data.crashMultiplier);
        }
        
        // DON'T clear bets here - let the new round handler do it
        // $("#all_bets .mCSB_container").empty();
        
        // Refresh bet history
        refreshBetHistory();
        
        // Update wallet balance
        updateWalletBalance();
        
        // Server will automatically start the next game cycle
        // No client action needed - just wait for gamePhase event
        console.log('⏳ Waiting for server to start next round...');
    });

    // Bet placed handler
    socket.on('onBetPlaced', (data) => {
        console.log('💰 Bet placed:', data);
        
        // Update all bets display
        addBetToDisplay(data);
        
        // Update bet count
        updateBetCount();
    });

    // Player cashed out handler
    socket.on('onPlayerCashedOut', (data) => {
        console.log('💸 Player cashed out:', data);
        
        // Show cash out notification
        if (typeof toastr !== 'undefined') {
            toastr.success(`${data.username} cashed out at ${data.multiplier}x!`);
        }
        
        // Update display
        updateCashOutDisplay(data);
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
                                ${data[i].amount}₹
                            </button>
                        </div>
                        <div class="column-3">
                            <div class="bg3 custom-badge mx-auto">
                                ${data[i].cashout_multiplier}x
                            </div>
                        </div>
                        <div class="column-4 fw-normal">
                            ${Math.round(data[i].cashout_multiplier * data[i].amount)}₹
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
