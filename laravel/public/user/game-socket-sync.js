/**
 * Game Socket Synchronization Layer
 * 
 * NOTE: This file is now DEPRECATED for the server-controlled game loop.
 * The server (socket-server.js) now controls the game loop automatically.
 * All clients are passive listeners - no master/slave needed.
 * 
 * Event handling is done in aviator-socket-integration.js
 */

// DEPRECATED - No longer needed with server-controlled game
let isSocketMaster = false; 
let socketSyncEnabled = false;
let currentGameData = { id: null, status: 'waiting' };

/**
 * Initialize socket synchronization
 * DEPRECATED - Server controls the game now, no sync needed
 */
function initSocketSync() {
    console.log('ℹ️ game-socket-sync.js: Server-controlled mode active');
    console.log('ℹ️ No master/slave sync needed - server is source of truth');
    
    // Don't set up duplicate handlers - aviator-socket-integration.js handles events
    socketSyncEnabled = true;
}

/**
 * Check if this tab should be the master
 * Master tab controls game start and coordinates with server
 */
function checkIfMaster() {
    // Use localStorage to coordinate between tabs
    const masterTab = localStorage.getItem('aviator_master_tab');
    const now = Date.now();
    
    if (!masterTab || now - parseInt(masterTab) > 5000) {
        // No master or master is stale, claim master
        localStorage.setItem('aviator_master_tab', now.toString());
        localStorage.setItem('aviator_master_id', generateTabId());
        isSocketMaster = true;
    } else {
        isSocketMaster = false;
    }

    // Keep master status alive
    setInterval(() => {
        if (isSocketMaster) {
            localStorage.setItem('aviator_master_tab', Date.now().toString());
        }
    }, 2000);
}

/**
 * Generate unique tab ID
 */
function generateTabId() {
    return 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Setup synchronization handlers
 * DEPRECATED - Event handlers are now in aviator-socket-integration.js
 * This function is kept empty to prevent errors if called
 */
function setupSyncHandlers(socket) {
    // DISABLED - All event handling moved to aviator-socket-integration.js
    // to prevent duplicate handlers and flickering
    console.log('ℹ️ setupSyncHandlers: Disabled - using aviator-socket-integration.js');
}

/**
 * DEPRECATED UI functions - now handled in aviator-socket-integration.js
 */
function triggerGameStartUI() {
    // DISABLED - handled in aviator-socket-integration.js
}

function updateMultiplierUI(multiplier) {
    // DISABLED - handled in aviator-socket-integration.js
}

function triggerGameCrashUI(crashMultiplier) {
    // DISABLED - handled in aviator-socket-integration.js
}

/**
 * Intercept bet placement to broadcast via socket
 */
function placeBetWithSocket(betData) {
    const socket = getAviatorSocket();
    
    if (!socket || !socket.isSocketConnected()) {
        console.error('Socket not connected');
        return Promise.reject('Socket not connected');
    }

    // First save bet to database via Laravel
    return $.ajax({
        url: '/game/add_bet',
        type: "POST",
        data: {
            _token: hash_id,
            all_bets: [betData]
        },
        dataType: "json"
    }).then(function(response) {
        if (response.isSuccess && response.data.socket_data) {
            // Now broadcast to all clients via socket
            const socketData = response.data.socket_data[0];
            socket.placeBet(socketData);
        }
        return response;
    });
}

/**
 * Intercept cash out to broadcast via socket
 * SECURITY: Uses POST method to prevent CSRF and URL exposure
 */
function cashOutWithSocket(gameId, betId, multiplier) {
    const socket = getAviatorSocket();
    
    // First process cash out on server (SECURITY: Using POST instead of GET)
    return $.ajax({
        url: '/cash_out',
        type: "POST",
        data: {
            _token: hash_id,  // CSRF token
            game_id: gameId,
            bet_id: betId,
            win_multiplier: multiplier
        },
        dataType: "json"
    }).then(function(response) {
        if (response.isSuccess && response.data.socket_data && socket && socket.isSocketConnected()) {
            // Broadcast to all clients
            socket.cashOut(
                response.data.socket_data.betId,
                response.data.socket_data.userId,
                response.data.socket_data.username,
                response.data.socket_data.multiplier
            );
        }
        return response;
    });
}

/**
 * DEPRECATED - Server now controls game start automatically
 */
function startNewGameViaSocket(gameId, targetMultiplier) {
    // DISABLED - Server controls game loop
    console.log('ℹ️ startNewGameViaSocket: Disabled - server controls game');
}

// DISABLED - Don't override game_existence, server controls everything
// const originalGameExistence = window.game_existence;
// window.game_existence = function() { ... };

// Initialize on document ready - just log that we're in server mode
(function() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    
    jQuery(document).ready(function($) {
        console.log('ℹ️ game-socket-sync.js loaded (server-controlled mode)');
    });
})();

// No cleanup needed - server controls everything
