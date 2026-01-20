/**
 * Socket.IO Client for Aviator Game
 * Handles real-time communication with the game server
 */

class AviatorSocketClient {
    constructor(serverUrl = 'http://localhost:3000') {
        this.serverUrl = serverUrl;
        this.socket = null;
        this.isConnected = false;
        this.gameState = {
            gameId: null,
            status: 'waiting',
            multiplier: 1.00,
            bets: []
        };
        this.callbacks = {
            onGamePhase: [],
            onGameStarted: [],
            onSyncGameInProgress: [],
            onSyncBets: [],
            onMultiplierUpdate: [],
            onGameCrashed: [],
            onBetPlaced: [],
            onPlayerCashedOut: [],
            onConnectionChange: []
        };
    }

    /**
     * Initialize and connect to Socket.IO server
     */
    connect() {
        if (typeof io === 'undefined') {
            console.error('Socket.IO client library not loaded');
            return;
        }

        this.socket = io(this.serverUrl, {
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionDelay: 1000,
            reconnectionAttempts: 5
        });

        this.setupEventListeners();
    }

    /**
     * Setup all socket event listeners
     */
    setupEventListeners() {
        // Connection events
        this.socket.on('connect', () => {
            console.log('Connected to Socket.IO server');
            this.isConnected = true;
            this.triggerCallback('onConnectionChange', { connected: true });
            
            // Join game if user is logged in
            if (typeof user_id !== 'undefined' && typeof username !== 'undefined') {
                this.joinGame(user_id, username);
            }
        });

        this.socket.on('disconnect', () => {
            console.log('Disconnected from Socket.IO server');
            this.isConnected = false;
            this.triggerCallback('onConnectionChange', { connected: false });
        });

        this.socket.on('connect_error', (error) => {
            console.error('Connection error:', error);
            this.triggerCallback('onConnectionChange', { connected: false, error: error.message });
        });

        // Game state events
        this.socket.on('gameState', (data) => {
            console.log('Received game state:', data);
            this.gameState = {
                gameId: data.gameId,
                status: data.status,
                multiplier: data.multiplier,
                bets: data.players || []
            };
        });

        // Sync game in progress (for reconnecting clients)
        this.socket.on('syncGameInProgress', (data) => {
            console.log('🔄 Syncing to game in progress:', data.multiplier + 'x');
            this.gameState.gameId = data.gameId;
            this.gameState.status = 'flying';
            this.gameState.multiplier = data.multiplier;
            this.triggerCallback('onSyncGameInProgress', data);
        });
        
        // Sync bets list (for reconnecting clients)
        this.socket.on('syncBets', (data) => {
            console.log('🔄 Syncing bets list:', data.bets.length, 'bets');
            this.triggerCallback('onSyncBets', data);
        });

        // Game phase event (waiting/countdown) - SERVER CONTROLLED
        this.socket.on('gamePhase', (data) => {
            console.log('Game phase:', data.phase);
            this.gameState.status = data.phase;
            this.triggerCallback('onGamePhase', data);
        });

        // Game started event
        this.socket.on('gameStarted', (data) => {
            console.log('Game started:', data);
            this.gameState.gameId = data.gameId;
            this.gameState.status = 'flying';
            this.gameState.multiplier = 1.00;
            this.triggerCallback('onGameStarted', data);
        });

        // Multiplier update event
        this.socket.on('multiplierUpdate', (data) => {
            this.gameState.multiplier = data.multiplier;
            this.triggerCallback('onMultiplierUpdate', data);
        });

        // Game crashed event
        this.socket.on('gameCrashed', (data) => {
            console.log('Game crashed:', data);
            this.gameState.status = 'crashed';
            this.gameState.multiplier = data.crashMultiplier;
            this.triggerCallback('onGameCrashed', data);
        });

        // Game reset event
        this.socket.on('gameReset', (data) => {
            console.log('Game reset:', data);
            this.gameState.status = 'waiting';
            this.gameState.multiplier = 1.00;
            this.gameState.bets = [];
        });

        // Bet placed event
        this.socket.on('betPlaced', (data) => {
            console.log('Bet placed:', data);
            this.gameState.bets.push(data);
            this.triggerCallback('onBetPlaced', data);
        });

        // Player cashed out event
        this.socket.on('playerCashedOut', (data) => {
            console.log('Player cashed out:', data);
            this.triggerCallback('onPlayerCashedOut', data);
        });

        // Cash out success
        this.socket.on('cashOutSuccess', (data) => {
            console.log('Cash out successful:', data);
        });

        // Error events
        this.socket.on('betError', (data) => {
            console.error('Bet error:', data.message);
            if (typeof toastr !== 'undefined') {
                toastr.error(data.message);
            }
        });

        this.socket.on('cashOutError', (data) => {
            console.error('Cash out error:', data.message);
            if (typeof toastr !== 'undefined') {
                toastr.error(data.message);
            }
        });

        // Player events
        this.socket.on('playerJoined', (data) => {
            console.log('Player joined:', data);
        });

        this.socket.on('playerLeft', (data) => {
            console.log('Player left:', data);
        });

        // Players count
        this.socket.on('playersCount', (data) => {
            console.log('Players count:', data.count);
        });
    }

    /**
     * Join the game
     */
    joinGame(userId, username) {
        if (!this.isConnected) {
            console.warn('Not connected to server');
            return;
        }

        this.socket.emit('playerJoin', {
            userId: userId,
            username: username
        });
    }

    /**
     * Place a bet
     */
    placeBet(betData) {
        if (!this.isConnected) {
            console.warn('Not connected to server');
            return Promise.reject('Not connected to server');
        }

        return new Promise((resolve, reject) => {
            this.socket.emit('placeBet', betData);
            resolve(betData);
        });
    }

    /**
     * Cash out a bet
     */
    cashOut(betId, userId, username, multiplier) {
        if (!this.isConnected) {
            console.warn('Not connected to server');
            return Promise.reject('Not connected to server');
        }

        return new Promise((resolve, reject) => {
            this.socket.emit('cashOut', {
                betId: betId,
                userId: userId,
                username: username,
                multiplier: multiplier
            });
            resolve();
        });
    }

    /**
     * Start a new game (admin/server function)
     */
    startGame(gameId, targetMultiplier) {
        if (!this.isConnected) {
            console.warn('Not connected to server');
            return;
        }

        this.socket.emit('startGame', {
            gameId: gameId,
            targetMultiplier: targetMultiplier
        });
    }

    /**
     * Get players count
     */
    getPlayersCount() {
        if (!this.isConnected) {
            console.warn('Not connected to server');
            return;
        }

        this.socket.emit('getPlayersCount');
    }

    /**
     * Register a callback for specific events
     */
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }

    /**
     * Trigger callbacks for specific events
     */
    triggerCallback(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} callback:`, error);
                }
            });
        }
    }

    /**
     * Get current game state
     */
    getGameState() {
        return this.gameState;
    }

    /**
     * Check if connected
     */
    isSocketConnected() {
        return this.isConnected;
    }

    /**
     * Disconnect from server
     */
    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.isConnected = false;
        }
    }
}

// Initialize global socket client instance
let aviatorSocket = null;

/**
 * Initialize the socket client
 */
function initAviatorSocket(serverUrl = 'http://localhost:3000') {
    if (!aviatorSocket) {
        aviatorSocket = new AviatorSocketClient(serverUrl);
        aviatorSocket.connect();
    }
    return aviatorSocket;
}

/**
 * Get the socket client instance
 */
function getAviatorSocket() {
    if (!aviatorSocket) {
        console.warn('Socket client not initialized. Call initAviatorSocket() first.');
    }
    return aviatorSocket;
}
