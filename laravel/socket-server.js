/**
 * Socket.IO Server for Aviator Game
 * SERVER-CONTROLLED GAME LOOP - All clients are passive listeners
 * The server is the single source of truth for game state
 */

const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.SOCKET_PORT || 3000;

// Game configuration
const GAME_CONFIG = {
    minMultiplier: 1.01,      // Minimum crash point
    maxMultiplier: 10.00,     // Maximum crash point (reduced from 100 for faster games)
    incrementSpeed: 100,      // Milliseconds between increments
    incrementValue: 0.01,     // How much to add each tick
    waitingTime: 5000,        // Time between games (ms)
    countdownTime: 3000       // Countdown before game starts (ms)
};

// Game state management
let gameState = {
    currentGameId: null,
    gameStatus: 'waiting', // waiting, countdown, flying, crashed
    currentMultiplier: 1.00,
    targetMultiplier: 1.00,
    players: new Map(),
    bets: new Map(),
    startTime: null,
    gameLoopRunning: false
};

// Connected clients
let connectedClients = new Set();

/**
 * Generate a random crash multiplier using provably fair algorithm
 * Distribution: Most crashes are low (1.x - 2.x), occasional high ones
 */
function generateCrashPoint() {
    // House edge of 4% (casino advantage)
    const houseEdge = 0.04;
    const random = Math.random();
    
    // Instant crash chance (1.00x) - about 4% of games
    if (random < houseEdge) {
        return 1.00;
    }
    
    // Crash point formula with house edge
    // This creates an exponential distribution where:
    // ~50% crash below 2x
    // ~33% crash below 1.5x
    // ~10% crash above 5x
    let crashPoint = (1 - houseEdge) / (1 - random);
    
    // Clamp between min and max
    crashPoint = Math.max(GAME_CONFIG.minMultiplier, Math.min(GAME_CONFIG.maxMultiplier, crashPoint));
    
    return parseFloat(crashPoint.toFixed(2));
}

/**
 * Generate unique game ID
 */
function generateGameId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
}

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);
    connectedClients.add(socket.id);

    // Send current game state to newly connected client
    socket.emit('gameState', {
        gameId: gameState.currentGameId,
        status: gameState.gameStatus,
        multiplier: gameState.currentMultiplier,
        players: Array.from(gameState.players.values())
    });
    
    // SYNC: If game is in progress, immediately sync the client
    if (gameState.gameStatus === 'flying') {
        console.log(`Syncing new client ${socket.id} to game in progress at ${gameState.currentMultiplier}x`);
        socket.emit('syncGameInProgress', {
            gameId: gameState.currentGameId,
            multiplier: gameState.currentMultiplier,
            status: 'flying'
        });
    } else if (gameState.gameStatus === 'waiting' || gameState.gameStatus === 'countdown') {
        socket.emit('gamePhase', {
            phase: gameState.gameStatus,
            gameId: gameState.currentGameId,
            duration: 0
        });
    }
    
    // SYNC: Send current bets list to newly connected client
    const currentBets = Array.from(gameState.bets.values()).map(bet => ({
        odapuId: bet.odapuId,
        username: bet.username,
        amount: bet.amount,
        betId: bet.betId,
        avatar: bet.avatar,
        status: bet.status,
        cashOutMultiplier: bet.cashOutMultiplier || null
    }));
    socket.emit('syncBets', { bets: currentBets });

    // Handle player joining
    socket.on('playerJoin', (data) => {
        console.log('Player joined:', data);
        gameState.players.set(socket.id, {
            userId: data.userId,
            username: data.username,
            socketId: socket.id
        });
        
        io.emit('playerJoined', {
            userId: data.userId,
            username: data.username
        });
    });

    // Handle new bet placement
    socket.on('placeBet', (data) => {
        console.log('Bet placed:', data);
        
        if (gameState.gameStatus !== 'waiting' && gameState.gameStatus !== 'countdown') {
            socket.emit('betError', { message: 'Cannot place bet while game is in progress' });
            return;
        }

        const betData = {
            odapuId: data.odapuId || data.userId,
            odapu: data.odapu || data.username,
            username: data.username,
            amount: data.amount,
            betId: data.betId,
            socketId: socket.id,
            avatar: data.avatar || null,
            status: 'active',
            cashOutMultiplier: null
        };
        
        gameState.bets.set(data.betId, betData);

        // Broadcast bet to ALL clients so everyone sees the same bets
        io.emit('betPlaced', {
            odapuId: betData.odapuId,
            odapu: betData.odapu,
            username: betData.username,
            amount: betData.amount,
            betId: betData.betId,
            avatar: betData.avatar
        });
        
        console.log(`Total bets this round: ${gameState.bets.size}`);
    });

    // Handle cash out
    socket.on('cashOut', (data) => {
        console.log('Cash out requested:', data);
        
        const bet = gameState.bets.get(data.betId);
        if (bet && bet.status === 'active') {
            bet.status = 'cashed_out';
            bet.cashOutMultiplier = gameState.currentMultiplier;
            bet.winAmount = bet.amount * gameState.currentMultiplier;

            // Notify all clients about the cash out
            io.emit('playerCashedOut', {
                userId: data.userId,
                username: data.username,
                betId: data.betId,
                multiplier: gameState.currentMultiplier,
                winAmount: bet.winAmount
            });

            // Send confirmation to the player
            socket.emit('cashOutSuccess', {
                betId: data.betId,
                multiplier: gameState.currentMultiplier,
                winAmount: bet.winAmount
            });
        } else {
            socket.emit('cashOutError', { message: 'Invalid bet or already cashed out' });
        }
    });

    // Handle game start (triggered by server/admin)
    socket.on('startGame', (data) => {
        if (gameState.gameStatus !== 'waiting') {
            socket.emit('gameError', { message: 'Game already in progress' });
            return;
        }

        gameState.currentGameId = data.gameId;
        gameState.targetMultiplier = data.targetMultiplier;
        gameState.gameStatus = 'flying';
        gameState.currentMultiplier = 1.00;
        gameState.startTime = Date.now();

        // Notify all clients that game has started
        io.emit('gameStarted', {
            gameId: data.gameId,
            timestamp: gameState.startTime
        });

        // Start multiplier increment
        startMultiplierLoop(data.targetMultiplier);
    });

    // Handle disconnection
    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
        connectedClients.delete(socket.id);
        
        const player = gameState.players.get(socket.id);
        if (player) {
            gameState.players.delete(socket.id);
            io.emit('playerLeft', { userId: player.userId });
        }

        // Remove player's bets if they disconnect during waiting phase
        if (gameState.gameStatus === 'waiting') {
            for (let [betId, bet] of gameState.bets) {
                if (bet.socketId === socket.id) {
                    gameState.bets.delete(betId);
                }
            }
        }
    });

    // Get current players count
    socket.on('getPlayersCount', () => {
        socket.emit('playersCount', {
            count: connectedClients.size
        });
    });

    // Admin: Force crash game
    socket.on('adminCrashGame', (data) => {
        if (gameState.gameStatus === 'flying') {
            endGame(gameState.currentMultiplier);
        }
    });
});

/**
 * Start the multiplier increment loop
 */
function startMultiplierLoop(targetMultiplier) {
    const increment = 0.01;
    const interval = 100; // 100ms = 0.1 second

    const multiplierInterval = setInterval(() => {
        if (gameState.gameStatus !== 'flying') {
            clearInterval(multiplierInterval);
            return;
        }

        gameState.currentMultiplier += increment;
        gameState.currentMultiplier = parseFloat(gameState.currentMultiplier.toFixed(2));

        // Broadcast multiplier update to all clients
        io.emit('multiplierUpdate', {
            multiplier: gameState.currentMultiplier
        });

        // Check if target multiplier reached
        if (gameState.currentMultiplier >= targetMultiplier) {
            clearInterval(multiplierInterval);
            endGame(targetMultiplier);
        }
    }, interval);
}

/**
 * End the current game
 */
function endGame(crashMultiplier) {
    gameState.gameStatus = 'crashed';
    gameState.currentMultiplier = crashMultiplier;

    // Calculate results for all active bets
    const results = [];
    for (let [betId, bet] of gameState.bets) {
        if (bet.status === 'active') {
            bet.status = 'lost';
            results.push({
                betId: betId,
                userId: bet.userId,
                status: 'lost',
                amount: bet.amount
            });
        } else if (bet.status === 'cashed_out') {
            results.push({
                betId: betId,
                userId: bet.userId,
                status: 'won',
                amount: bet.amount,
                winAmount: bet.winAmount,
                multiplier: bet.cashOutMultiplier
            });
        }
    }

    // Notify all clients that game crashed
    io.emit('gameCrashed', {
        gameId: gameState.currentGameId,
        crashMultiplier: crashMultiplier,
        results: results
    });

    // Reset game state after a delay
    setTimeout(() => {
        resetGameState();
        io.emit('gameReset', {
            status: 'waiting'
        });
    }, 5000); // 5 second delay before next game
}

/**
 * Reset game state for next round
 */
function resetGameState() {
    gameState.gameStatus = 'waiting';
    gameState.currentMultiplier = 1.00;
    gameState.targetMultiplier = 1.00;
    gameState.bets.clear();
    gameState.startTime = null;
    // Keep players connected
}

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        connections: connectedClients.size,
        gameStatus: gameState.gameStatus,
        currentMultiplier: gameState.currentMultiplier
    });
});

// Get current game state endpoint
app.get('/game-state', (req, res) => {
    res.json({
        gameId: gameState.currentGameId,
        status: gameState.gameStatus,
        multiplier: gameState.currentMultiplier,
        targetMultiplier: gameState.targetMultiplier,
        betsCount: gameState.bets.size,
        playersCount: gameState.players.size
    });
});

// HTTP endpoint to start game (for server-side control)
app.post('/start-game', express.json(), (req, res) => {
    const { gameId, targetMultiplier } = req.body;
    
    if (gameState.gameStatus !== 'waiting') {
        return res.status(400).json({ 
            success: false, 
            message: 'Game already in progress' 
        });
    }

    gameState.currentGameId = gameId;
    gameState.targetMultiplier = targetMultiplier || (Math.random() * 9 + 2); // 2-11x
    gameState.gameStatus = 'flying';
    gameState.currentMultiplier = 1.00;
    gameState.startTime = Date.now();

    // Notify all clients that game has started
    io.emit('gameStarted', {
        gameId: gameId,
        timestamp: gameState.startTime
    });

    // Start multiplier increment
    startMultiplierLoop(gameState.targetMultiplier);

    res.json({ 
        success: true, 
        gameId: gameId,
        targetMultiplier: gameState.targetMultiplier 
    });
});

/**
 * ==========================================
 * SERVER-CONTROLLED GAME LOOP
 * This runs automatically - no client control
 * ==========================================
 */

/**
 * Start the automatic game loop
 */
function startGameLoop() {
    if (gameState.gameLoopRunning) {
        console.log('Game loop already running');
        return;
    }
    
    gameState.gameLoopRunning = true;
    console.log('🎮 Starting automatic game loop...');
    
    runGameCycle();
}

/**
 * Run a single game cycle: waiting -> countdown -> flying -> crashed -> repeat
 */
function runGameCycle() {
    // Phase 1: Waiting for bets
    gameState.gameStatus = 'waiting';
    gameState.currentMultiplier = 1.00;
    gameState.bets.clear();
    
    console.log('⏳ Waiting phase - accepting bets...');
    
    io.emit('gamePhase', {
        phase: 'waiting',
        duration: GAME_CONFIG.waitingTime
    });
    
    // After waiting time, start countdown
    setTimeout(() => {
        startCountdown();
    }, GAME_CONFIG.waitingTime);
}

/**
 * Start countdown phase
 */
function startCountdown() {
    gameState.gameStatus = 'countdown';
    gameState.currentGameId = generateGameId();
    gameState.targetMultiplier = generateCrashPoint();
    
    console.log(`🕐 Countdown starting - Game ${gameState.currentGameId}, Target: ${gameState.targetMultiplier}x`);
    
    // Emit countdown event
    io.emit('gamePhase', {
        phase: 'countdown',
        gameId: gameState.currentGameId,
        duration: GAME_CONFIG.countdownTime
    });
    
    // After countdown, start flying
    setTimeout(() => {
        startFlying();
    }, GAME_CONFIG.countdownTime);
}

/**
 * Start the flying (multiplier increasing) phase
 */
function startFlying() {
    gameState.gameStatus = 'flying';
    gameState.currentMultiplier = 1.00;
    gameState.startTime = Date.now();
    
    console.log(`🛫 Game ${gameState.currentGameId} started! Target crash: ${gameState.targetMultiplier}x`);
    
    // Notify all clients
    io.emit('gameStarted', {
        gameId: gameState.currentGameId,
        timestamp: gameState.startTime
    });
    
    // Start multiplier loop
    const multiplierInterval = setInterval(() => {
        if (gameState.gameStatus !== 'flying') {
            clearInterval(multiplierInterval);
            return;
        }
        
        gameState.currentMultiplier += GAME_CONFIG.incrementValue;
        gameState.currentMultiplier = parseFloat(gameState.currentMultiplier.toFixed(2));
        
        // Broadcast to ALL clients
        io.emit('multiplierUpdate', {
            multiplier: gameState.currentMultiplier
        });
        
        // Check if target reached
        if (gameState.currentMultiplier >= gameState.targetMultiplier) {
            clearInterval(multiplierInterval);
            crashGame();
        }
    }, GAME_CONFIG.incrementSpeed);
}

/**
 * Crash the game
 */
function crashGame() {
    gameState.gameStatus = 'crashed';
    
    console.log(`💥 Game ${gameState.currentGameId} crashed at ${gameState.currentMultiplier}x`);
    
    // Calculate results for all active bets
    const results = [];
    for (let [betId, bet] of gameState.bets) {
        if (bet.status === 'active') {
            bet.status = 'lost';
            results.push({
                betId: betId,
                odapuId: bet.userId,
                status: 'lost',
                amount: bet.amount
            });
        } else if (bet.status === 'cashed_out') {
            results.push({
                betId: betId,
                userId: bet.userId,
                status: 'won',
                amount: bet.amount,
                winAmount: bet.winAmount,
                multiplier: bet.cashOutMultiplier
            });
        }
    }
    
    // Notify ALL clients
    io.emit('gameCrashed', {
        gameId: gameState.currentGameId,
        crashMultiplier: gameState.currentMultiplier,
        results: results
    });
    
    // Wait and start next cycle
    setTimeout(() => {
        runGameCycle();
    }, 2000); // Short pause before next round
}

// Start server
http.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
    console.log(`Health check available at http://localhost:${PORT}/health`);
    console.log('');
    console.log('🎮 AUTO-STARTING GAME LOOP...');
    
    // Auto-start the game loop when server starts
    setTimeout(() => {
        startGameLoop();
    }, 2000);
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('Shutting down gracefully...');
    gameState.gameLoopRunning = false;
    io.close(() => {
        console.log('Socket.IO server closed');
        process.exit(0);
    });
});

module.exports = { io, gameState };
