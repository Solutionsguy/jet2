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
    gameLoopRunning: false,
    phaseStartTime: null,      // Track when current phase started
    phaseDuration: 0           // Duration of current phase
};

// Connected clients
let connectedClients = new Set();

/**
 * Generate a random crash multiplier with house edge control
 * 
 * ADAPTIVE ALGORITHM:
 * - When NO real bets exist: Use relaxed config (allows higher multipliers for display)
 * - When REAL bets exist: Use aggressive config (crash at lower multipliers to protect house)
 * 
 * HOUSE EDGE CONFIGURATION:
 * - instantCrashChance: % of games that crash at 1.00x (instant loss)
 * - lowCrashChance: % of games that crash between 1.01x - 1.50x
 * - mediumCrashChance: % of games that crash between 1.51x - 3.00x
 * - Remaining % allows for higher multipliers (3.01x - maxMultiplier)
 */
function generateCrashPoint(minMultiplier = 1.00) {
    // DEBUG: Log all bets to see what's in the Map
    console.log('🔍 DEBUG: Total bets in gameState.bets:', gameState.bets.size);
    console.log('🔍 DEBUG: Minimum multiplier (current game position):', minMultiplier);
    
    const allBets = Array.from(gameState.bets.values());
    const realBets = allBets.filter(bet => !bet.isFake);
    const fakeBets = allBets.filter(bet => bet.isFake);
    
    console.log('🔍 DEBUG: Fake bets count:', fakeBets.length);
    console.log('🔍 DEBUG: Real bets count:', realBets.length);
    
    // Log each real bet for debugging
    if (realBets.length > 0) {
        console.log('🔍 DEBUG: Real bets details:');
        realBets.forEach((bet, index) => {
            console.log(`   [${index}] betId: ${bet.betId}, username: ${bet.username}, amount: ${bet.amount}, isFake: ${bet.isFake}`);
        });
    } else {
        // Log a sample of bets to see their isFake status
        console.log('🔍 DEBUG: Sample of first 3 bets:');
        allBets.slice(0, 3).forEach((bet, index) => {
            console.log(`   [${index}] betId: ${bet.betId}, username: ${bet.username}, isFake: ${bet.isFake}, typeof isFake: ${typeof bet.isFake}`);
        });
    }
    
    // Count REAL bets (not fake bets used for display)
    const realBetsCount = realBets.length;
    const hasRealBets = realBetsCount > 0;
    
    // Calculate total real bet amount for logging
    const totalRealBetAmount = realBets.reduce((sum, bet) => sum + bet.amount, 0);
    
    // ========== HOUSE EDGE CONFIGURATION ==========
    // Both configs use the SAME structure for consistency
    // All crash points are calculated as ABSOLUTE values (not relative to minMultiplier)
    // minMultiplier is only used as a floor to prevent crashes below current position
    
    // AGGRESSIVE CONFIG: Used when real bets exist (protects house)
    // Lower multipliers = house wins more often
    const AGGRESSIVE_CONFIG = {
        instantCrashChance: 0.90,    // 8% chance of crash at 1.00x - 1.20x (instant loss)
        lowCrashChance: 0.10,        // 45% chance of crash at 1.20x - 1.80x (low multiplier)
        mediumCrashChance: 0.0,     // 35% chance of crash at 1.80x - 3.00x (medium)
        // Remaining 12% chance of crash at 3.00x - 5.00x (high)
        instantMax: 1.0,
        lowMax: 1.5,
        mediumMax: 1.75,
        maxAllowed: 2.00
    };
    
    // RELAXED CONFIG: Used when no real bets (just for display/entertainment)
    // Higher multipliers make the game look exciting for spectators
    const RELAXED_CONFIG = {
        instantCrashChance: 0.02,    // 2% chance of crash at 1.00x - 1.50x (rare instant)
        lowCrashChance: 0.0,        // 13% chance of crash at 1.50x - 3.00x (low)
        mediumCrashChance: 0.98,     // 35% chance of crash at 3.00x - 7.00x (medium)
        // Remaining 50% chance of crash at 7.00x - 15.00x (high - exciting!)
        instantMax: 1.0,
        lowMax: 3.00,
        mediumMax: 7.00,
        maxAllowed: 15.00
    };
    // ==============================================
    
    // Select config based on whether real bets exist
    const CONFIG = hasRealBets ? AGGRESSIVE_CONFIG : RELAXED_CONFIG;
    
    const random = Math.random();
    let crashPoint;
    
    // Generate crash point based on probability ranges
    // All values are ABSOLUTE (not relative to minMultiplier)
    if (random < CONFIG.instantCrashChance) {
        // Instant/very low crash
        crashPoint = 1.00 + (Math.random() * (CONFIG.instantMax - 1.00));
    }
    else if (random < CONFIG.instantCrashChance + CONFIG.lowCrashChance) {
        // Low crash range
        crashPoint = CONFIG.instantMax + (Math.random() * (CONFIG.lowMax - CONFIG.instantMax));
    }
    else if (random < CONFIG.instantCrashChance + CONFIG.lowCrashChance + CONFIG.mediumCrashChance) {
        // Medium crash range
        crashPoint = CONFIG.lowMax + (Math.random() * (CONFIG.mediumMax - CONFIG.lowMax));
    }
    else {
        // High crash range (the remaining percentage)
        // Use exponential distribution to make max values rarer
        crashPoint = CONFIG.mediumMax + (Math.pow(Math.random(), 1.5) * (CONFIG.maxAllowed - CONFIG.mediumMax));
    }
    
    // IMPORTANT: Ensure crash point is above current game position
    // This handles the 800ms delay where game has already started
    crashPoint = Math.max(crashPoint, minMultiplier + 0.05);
    
    // Logging
    if (hasRealBets) {
        console.log(`🎯 AGGRESSIVE MODE: ${realBetsCount} real bets (₹${totalRealBetAmount}) - Crash point: ${crashPoint.toFixed(2)}x (min was ${minMultiplier}x)`);
    } else {
        console.log(`🎲 RELAXED MODE: No real bets - Crash point: ${crashPoint.toFixed(2)}x (min was ${minMultiplier}x)`);
    }
    
    return parseFloat(crashPoint.toFixed(2));
}

/**
 * Generate crash point with FORCED AGGRESSIVE mode
 * Used when betIntent is received - always uses aggressive config
 * This ensures instant crashes at 1.00x are possible
 */
function generateCrashPointAggressive(minMultiplier = 1.00) {
    console.log('🎯 generateCrashPointAggressive called - FORCING AGGRESSIVE MODE');
    
    // AGGRESSIVE CONFIG: Always used (ignores real bets check)
    const CONFIG = {
        instantCrashChance: 0.50,    // 100% chance of crash at 1.00x - 1.05x
        lowCrashChance: 0.50,
        mediumCrashChance: 0.0,
        instantMax: 1.0,
        lowMax: 1.5,
        mediumMax: 1.75,
        maxAllowed: 2.00
    };
    
    const random = Math.random();
    let crashPoint;
    
    if (random < CONFIG.instantCrashChance) {
        // Instant crash - 1.00x to 1.05x
        crashPoint = 1.00 + (Math.random() * (CONFIG.instantMax - 1.00));
    }
    else if (random < CONFIG.instantCrashChance + CONFIG.lowCrashChance) {
        crashPoint = CONFIG.instantMax + (Math.random() * (CONFIG.lowMax - CONFIG.instantMax));
    }
    else if (random < CONFIG.instantCrashChance + CONFIG.lowCrashChance + CONFIG.mediumCrashChance) {
        crashPoint = CONFIG.lowMax + (Math.random() * (CONFIG.mediumMax - CONFIG.lowMax));
    }
    else {
        crashPoint = CONFIG.mediumMax + (Math.pow(Math.random(), 1.5) * (CONFIG.maxAllowed - CONFIG.mediumMax));
    }
    
    // For instant crashes (< 1.01), do NOT apply minimum floor - allow true 1.00x
    if (crashPoint >= 1.01) {
        crashPoint = Math.max(crashPoint, minMultiplier + 0.01);
    }
    
    console.log(`🎯 AGGRESSIVE crash point: ${crashPoint.toFixed(2)}x`);
    
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
        // Calculate remaining time in current phase
        const elapsed = Date.now() - (gameState.phaseStartTime || Date.now());
        const remaining = Math.max(0, gameState.phaseDuration - elapsed);
        
        console.log(`Syncing new client ${socket.id} to ${gameState.gameStatus} phase (${remaining}ms remaining)`);
        
        socket.emit('gamePhase', {
            phase: gameState.gameStatus,
            gameId: gameState.currentGameId,
            duration: remaining
        });
    } else if (gameState.gameStatus === 'crashed') {
        // Game just crashed, new round will start soon
        console.log(`New client ${socket.id} connected during crashed phase - next round starting soon`);
        socket.emit('gamePhase', {
            phase: 'crashed',
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

    // ========== BET INTENT HANDLER ==========
    // Receives signal IMMEDIATELY when user clicks bet button
    // This triggers AGGRESSIVE mode BEFORE the AJAX call completes
    // Validates using wallet balance sent from client UI
    socket.on('betIntent', (data) => {
        const betAmount = parseFloat(data.betAmount) || 0;
        const walletBalance = parseFloat(data.walletBalance) || 0;
        
        console.log(`⚡ BET INTENT from ${data.username}: ${betAmount} (wallet: ${walletBalance})`);
        
        // Quick validation: Check if user has enough balance
        if (betAmount <= 0) {
            console.log(`❌ BET INTENT rejected - invalid bet amount: ${betAmount}`);
            socket.emit('betError', { message: 'Invalid bet amount. Please enter a valid amount.' });
            return;
        }
        
        if (walletBalance < betAmount) {
            console.log(`❌ BET INTENT rejected - insufficient balance: ${walletBalance} < ${betAmount}`);
            socket.emit('betError', { message: 'Insufficient balance. Please deposit more funds.' });
            return;
        }
        
        // Mark that we have a real bet intent - this triggers AGGRESSIVE mode
        gameState.hasRealBetIntent = true;
        gameState.betIntentTimestamp = Date.now();
        gameState.activeBetIntents = (gameState.activeBetIntents || 0) + 1;
        
        console.log(`✅ BET INTENT ACCEPTED - AGGRESSIVE MODE ACTIVATED (Active intents: ${gameState.activeBetIntents})`);
        
        // CRITICAL: If game is in countdown or just started flying, recalculate crash point NOW
        if (gameState.gameStatus === 'countdown' || 
            (gameState.gameStatus === 'flying' && !gameState.crashPointCalculated)) {
            
            console.log(`🔄 RECALCULATING crash point with AGGRESSIVE mode (betIntent received)`);
            
            // Calculate at 1.00x base to allow instant crashes
            const oldTarget = gameState.targetMultiplier;
            gameState.targetMultiplier = generateCrashPointAggressive(1.00);
            gameState.crashPointCalculated = true;
            
            console.log(`🎯 Crash point changed: ${oldTarget}x → ${gameState.targetMultiplier}x (AGGRESSIVE)`);
            
            // If instant crash (1.00x) and game is flying, crash immediately
            if (gameState.gameStatus === 'flying' && gameState.targetMultiplier <= gameState.currentMultiplier) {
                console.log(`💥 INSTANT CRASH! Target ${gameState.targetMultiplier}x <= Current ${gameState.currentMultiplier}x`);
                // Crash will happen on next multiplier tick
            }
        }
    });
    // ==========================================
    
    // ========== BET CANCEL HANDLER ==========
    // Receives signal when user cancels their bet
    // If no more active bets/intents, switches back to RELAXED mode
    socket.on('betCancel', (data) => {
        console.log(`🚫 BET CANCEL from ${data.username}`);
        
        // Decrement active bet intents count
        gameState.activeBetIntents = Math.max(0, (gameState.activeBetIntents || 0) - 1);
        
        // Remove any bets from this user that haven't been placed yet
        // (They might have a pending bet in bet_array that was canceled before place_bet_now())
        for (let [betId, bet] of gameState.bets) {
            if (bet.odapuId === data.odapuId && bet.status === 'active' && !bet.isFake) {
                // Mark as cancelled instead of deleting (for tracking)
                bet.status = 'cancelled';
                console.log(`🗑️ Bet ${betId} marked as cancelled`);
            }
        }
        
        // Count remaining real bets and intents
        const realBetsCount = Array.from(gameState.bets.values())
            .filter(bet => !bet.isFake && bet.status === 'active').length;
        const totalActiveIntents = gameState.activeBetIntents || 0;
        
        console.log(`📊 After cancel: Real bets: ${realBetsCount}, Active intents: ${totalActiveIntents}`);
        
        // If no more real bets AND no active intents, switch back to RELAXED mode
        if (realBetsCount === 0 && totalActiveIntents === 0) {
            console.log(`🔄 No active bets/intents - switching back to RELAXED mode`);
            gameState.hasRealBetIntent = false;
            
            // If game hasn't started flying yet, recalculate crash point with RELAXED mode
            if (gameState.gameStatus === 'waiting' || gameState.gameStatus === 'countdown') {
                console.log(`🎲 RELAXED MODE: Crash point will be recalculated at game start`);
                gameState.crashPointCalculated = false;
            }
        }
    });
    // ========================================

    // Handle new bet placement
    socket.on('placeBet', (data) => {
        console.log('Bet placed:', data);
        console.log('🔍 DEBUG placeBet: gameStatus =', gameState.gameStatus, ', betId =', data.betId);
        
        // Allow bets during waiting, countdown, AND at the very start of flying
        // This is needed because clients receive gameStarted event and then try to place bets
        // There's a small race condition where status becomes 'flying' before bet is placed
        if (gameState.gameStatus !== 'waiting' && gameState.gameStatus !== 'countdown' && gameState.gameStatus !== 'flying') {
            // Just log - don't emit error to client (this is expected during crashed phase)
            console.log('⚠️ Bet ignored - game status:', gameState.gameStatus, '(expected during phase transitions)');
            return;
        }
        
        // If game is flying but within bet window (< 2 seconds), allow the bet
        // This handles the race condition where bet is placed right as game starts
        // Must match the 2000ms bet window in startFlying()
        if (gameState.gameStatus === 'flying') {
            const gameRunningTime = Date.now() - gameState.startTime;
            if (gameRunningTime > 2000) {
                // Just log - don't emit error to client (bet window timing issue, not user error)
                console.log('⚠️ Bet ignored - game running for', gameRunningTime, 'ms (bet window closed)');
                return;
            }
            console.log('⚠️ Late bet accepted (game running for', gameRunningTime, 'ms, within bet window)');
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
            cashOutMultiplier: null,
            // AUTO CASH-OUT: Store the target multiplier for server-side auto cash-out
            autoCashOutAt: data.autoCashOutAt ? parseFloat(data.autoCashOutAt) : null,
            sectionNo: data.sectionNo || 0,
            // IMPORTANT: Mark as real bet (not fake) so aggressive mode is triggered
            isFake: false
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
        
        // DEBUG: Log auto cash-out setting
        console.log(`🔍 DEBUG placeBet: betId=${data.betId}, autoCashOutAt=${data.autoCashOutAt}, stored=${betData.autoCashOutAt}, currentMultiplier=${gameState.currentMultiplier}x`);
        
        if (betData.autoCashOutAt) {
            console.log(`💰 Bet ${data.betId} has AUTO CASH-OUT set at ${betData.autoCashOutAt}x`);
            
            // IMPORTANT: If bet arrives late and multiplier has ALREADY passed the auto-cashout target,
            // trigger auto-cashout immediately!
            if (gameState.gameStatus === 'flying' && gameState.currentMultiplier >= betData.autoCashOutAt) {
                console.log(`⚡ IMMEDIATE AUTO CASH-OUT: Multiplier ${gameState.currentMultiplier}x already >= target ${betData.autoCashOutAt}x`);
                
                // Process this bet's auto-cashout immediately
                betData.status = 'cashed_out';
                betData.cashOutMultiplier = betData.autoCashOutAt;
                betData.winAmount = betData.amount * betData.autoCashOutAt;
                
                // Call Laravel API
                callLaravelCashout(betData.betId, betData.autoCashOutAt)
                    .then(() => console.log(`✅ Immediate auto cash-out processed for bet ${betData.betId}`))
                    .catch(err => console.error(`❌ Immediate auto cash-out failed for bet ${betData.betId}:`, err.message));
                
                // Broadcast to all clients
                io.emit('playerCashedOut', {
                    odapuId: betData.odapuId,
                    username: betData.username || betData.odapu,
                    betId: betData.betId,
                    multiplier: betData.autoCashOutAt,
                    winAmount: betData.winAmount,
                    isAutoCashout: true
                });
                
                // Notify the player
                socket.emit('autoCashoutTriggered', {
                    betId: betData.betId,
                    multiplier: betData.autoCashOutAt,
                    winAmount: betData.winAmount,
                    sectionNo: betData.sectionNo
                });
            }
        } else {
            console.log(`⚠️ Bet ${data.betId} has NO auto cash-out (autoCashOutAt was: ${data.autoCashOutAt})`);
        }
        
        // Log real bet placement for debugging aggressive mode
        const realBetsCount = Array.from(gameState.bets.values()).filter(bet => !bet.isFake).length;
        console.log(`✅ REAL BET placed: ${data.username} - ${data.amount} (Total real bets: ${realBetsCount}, Total bets: ${gameState.bets.size})`);
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

    // Handle resync request (when tab becomes visible after being hidden)
    socket.on('requestSync', () => {
        console.log(`🔄 Resync requested by ${socket.id}`);
        
        // Send current game state
        if (gameState.gameStatus === 'flying') {
            socket.emit('syncGameInProgress', {
                gameId: gameState.currentGameId,
                multiplier: gameState.currentMultiplier,
                status: 'flying'
            });
        } else {
            socket.emit('gamePhase', {
                phase: gameState.gameStatus,
                gameId: gameState.currentGameId,
                duration: 0
            });
        }
        
        // Send current bets
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
        
        console.log(`✅ Resync sent to ${socket.id} - Status: ${gameState.gameStatus}, Multiplier: ${gameState.currentMultiplier}x`);
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
// SECURITY: targetMultiplier removed to prevent players from knowing crash point in advance
app.get('/game-state', (req, res) => {
    res.json({
        gameId: gameState.currentGameId,
        status: gameState.gameStatus,
        multiplier: gameState.currentMultiplier,
        // targetMultiplier intentionally omitted for security
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
 * Generate fake bets to populate the sidebar
 * Creates realistic-looking bot players for display
 */
function generateFakeBets() {
    const fakeBets = [];
    const numFakeBets = Math.floor(Math.random() * 500) + 400; // 400-900 fake bets
    
    for (let i = 0; i < numFakeBets; i++) {
        const odapuId = Math.floor(Math.random() * 40000) + 10000;
        const betId = 'fake_' + Date.now() + '_' + i;
        const amount = Math.floor(Math.random() * 9000) + 100; // 100-9100
        const avatarNum = Math.floor(Math.random() * 72) + 1;
        
        const fakeBet = {
            odapuId: odapuId,
            odapu: 'd***' + Math.floor(Math.random() * 900 + 100),
            username: 'd***' + Math.floor(Math.random() * 900 + 100),
            amount: amount,
            betId: betId,
            avatar: '/images/avtar/av-' + avatarNum + '.png',
            status: 'active',
            isFake: true,
            cashOutMultiplier: null
        };
        
        fakeBets.push(fakeBet);
        gameState.bets.set(betId, fakeBet);
    }
    
    return fakeBets;
}

/**
 * Run a single game cycle: waiting -> countdown -> flying -> crashed -> repeat
 */
function runGameCycle() {
    // Phase 1: Waiting for bets
    gameState.gameStatus = 'waiting';
    gameState.currentMultiplier = 1.00;
    gameState.bets.clear();
    
    // Reset bet intent flag for new round
    gameState.hasRealBetIntent = false;
    gameState.betIntentTimestamp = null;
    gameState.crashPointCalculated = false;
    gameState.activeBetIntents = 0;  // Reset active bet intent counter
    
    // Track phase timing for new client sync
    gameState.phaseStartTime = Date.now();
    gameState.phaseDuration = GAME_CONFIG.waitingTime;
    
    console.log('⏳ Waiting phase - accepting bets...');
    
    io.emit('gamePhase', {
        phase: 'waiting',
        duration: GAME_CONFIG.waitingTime
    });
    
    // Generate fake bets and broadcast to all clients
    const fakeBets = generateFakeBets();
    console.log(`🤖 Generated ${fakeBets.length} fake bets for display`);
    
    // Broadcast all fake bets to populate sidebar on all clients
    io.emit('syncBets', { bets: fakeBets });
    
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
    // NOTE: Crash point will be generated at the END of countdown (in startFlying)
    // This ensures we check for real bets AFTER players have had time to place them
    
    // Track phase timing for new client sync
    gameState.phaseStartTime = Date.now();
    gameState.phaseDuration = GAME_CONFIG.countdownTime;
    
    console.log(`🕐 Countdown starting - Game ${gameState.currentGameId}`);
    
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
    
    // Track phase timing (flying phase has no fixed duration)
    gameState.phaseStartTime = Date.now();
    gameState.phaseDuration = 0; // No fixed duration for flying
    
    // Check if there are already real bets OR bet intent (placed during waiting/countdown phase)
    const existingRealBets = Array.from(gameState.bets.values()).filter(bet => !bet.isFake).length;
    const hasRealActivity = existingRealBets > 0 || gameState.hasRealBetIntent;
    
    // Determine if this will be an instant crash
    let isInstantCrash = false;
    
    if (hasRealActivity) {
        // Real bets or bet intent exists - use AGGRESSIVE mode
        // If crash point already calculated by betIntent handler, keep it
        if (!gameState.crashPointCalculated) {
            gameState.targetMultiplier = generateCrashPointAggressive(1.00);
            gameState.crashPointCalculated = true;
        }
        console.log(`🛫 Game ${gameState.currentGameId} started with AGGRESSIVE mode! Crash point: ${gameState.targetMultiplier}x`);
        
        // Check for instant crash (< 1.01x)
        if (gameState.targetMultiplier < 1.01) {
            isInstantCrash = true;
            gameState.targetMultiplier = 1.00; // Force exact 1.00x
            console.log(`💥 INSTANT CRASH DETECTED! Will crash at exactly 1.00x`);
        }
    } else {
        // No real bets yet - use RELAXED mode initially, will recalculate if bets arrive
        gameState.targetMultiplier = generateCrashPoint(1.00);
        gameState.crashPointCalculated = false; // Allow recalculation if real bets arrive
        console.log(`🛫 Game ${gameState.currentGameId} started! Initial crash point: ${gameState.targetMultiplier}x (may recalculate if bets arrive)`);
    }
    
    // Notify all clients - include isInstantCrash flag so client can skip incrementor
    io.emit('gameStarted', {
        gameId: gameState.currentGameId,
        timestamp: gameState.startTime,
        isInstantCrash: isInstantCrash,
        crashPoint: isInstantCrash ? 1.00 : null
    });
    
    // If instant crash, crash immediately without starting multiplier loop
    if (isInstantCrash) {
        console.log(`💥 INSTANT CRASH executing at 1.00x - NO incrementor!`);
        
        // IMPORTANT: Even for instant crash, try to process auto-cashouts first
        // This gives users with very low auto-cashout (like 1.00x) a chance
        // However, realistically 1.00x auto-cashout won't help since crash is also 1.00x
        processAutoCashouts(1.00);
        
        setTimeout(() => {
            crashGame();
        }, 100); // Small delay to ensure gameStarted event is received
        return;
    }
    
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
        
        // IMPORTANT: Process auto cash-outs for real players (works even when tab is inactive)
        processAutoCashouts(gameState.currentMultiplier);
        
        // Simulate fake cashouts randomly (makes it look more realistic)
        simulateFakeCashouts(gameState.currentMultiplier);
        
        // Check if target reached
        if (gameState.currentMultiplier >= gameState.targetMultiplier) {
            clearInterval(multiplierInterval);
            crashGame();
        }
    }, GAME_CONFIG.incrementSpeed);
    
    // IMPORTANT: Recalculate crash point AFTER bet window if real bets arrived late
    // Timeline:
    // - Server emits gameStarted event
    // - Client receives event (~50-100ms network latency)
    // - Client calls place_bet_now() which makes AJAX to Laravel (~200-500ms)
    // - Laravel processes bet and returns response
    // - Client sends socket.placeBet() (~50-100ms)
    // Total: Could be 500-1000ms before socket receives the bet
    // We use 2000ms (2 seconds) to be safe
    setTimeout(() => {
        if (gameState.gameStatus === 'flying') {
            const realBetsCount = Array.from(gameState.bets.values()).filter(bet => !bet.isFake).length;
            
            if (!gameState.crashPointCalculated && realBetsCount > 0) {
                // Real bets arrived during bet window - recalculate with AGGRESSIVE mode
                // But use current multiplier as minimum (can't crash in the past)
                const currentMultiplierAtCalculation = gameState.currentMultiplier;
                
                console.log(`⏰ Bet window closed. Real bets received: ${realBetsCount} - RECALCULATING crash point`);
                
                gameState.targetMultiplier = generateCrashPoint(currentMultiplierAtCalculation);
                gameState.crashPointCalculated = true;
                console.log(`🎯 Crash point RECALCULATED: ${gameState.targetMultiplier}x (current: ${currentMultiplierAtCalculation}x, AGGRESSIVE mode)`);
            } else if (!gameState.crashPointCalculated) {
                // No real bets - keep the RELAXED crash point but mark as calculated
                gameState.crashPointCalculated = true;
                console.log(`⏰ Bet window closed. No real bets - keeping RELAXED crash point: ${gameState.targetMultiplier}x`);
            } else {
                // Crash point was already calculated at game start (had real bets waiting)
                console.log(`⏰ Bet window closed. Crash point was pre-calculated: ${gameState.targetMultiplier}x`);
            }
        }
    }, 2000); // 2000ms (2 seconds) bet window to account for Laravel API processing
}

/**
 * Process auto cash-outs for real player bets
 * This runs on EVERY multiplier tick to ensure auto cash-outs work even when tab is inactive
 */
async function processAutoCashouts(currentMultiplier) {
    // DEBUG: Log all real bets with auto-cashout settings (only on specific multipliers to reduce spam)
    if (currentMultiplier === 1.01 || currentMultiplier === 1.05) {
        const realBetsWithAutoCashout = Array.from(gameState.bets.values())
            .filter(bet => !bet.isFake && bet.autoCashOutAt);
        console.log(`🔍 DEBUG processAutoCashouts at ${currentMultiplier}x:`);
        console.log(`   Real bets with autoCashOutAt: ${realBetsWithAutoCashout.length}`);
        realBetsWithAutoCashout.forEach((bet, i) => {
            console.log(`   [${i}] betId: ${bet.betId}, autoCashOutAt: ${bet.autoCashOutAt}x, status: ${bet.status}, amount: ${bet.amount}`);
        });
    }
    
    // Get all active REAL bets (not fake) that have auto cash-out enabled
    const autoCashoutBets = Array.from(gameState.bets.values())
        .filter(bet => 
            !bet.isFake && 
            bet.status === 'active' && 
            bet.autoCashOutAt && 
            currentMultiplier >= bet.autoCashOutAt
        );
    
    for (const bet of autoCashoutBets) {
        console.log(`🤖 AUTO CASH-OUT triggered for bet ${bet.betId} at ${bet.autoCashOutAt}x (current: ${currentMultiplier}x)`);
        
        // Update bet status in socket server
        bet.status = 'cashed_out';
        bet.cashOutMultiplier = bet.autoCashOutAt; // Use the target multiplier, not current
        bet.winAmount = bet.amount * bet.autoCashOutAt;
        
        // Call Laravel API to process the actual cash-out (update database and wallet)
        try {
            await callLaravelCashout(bet.betId, bet.autoCashOutAt);
        } catch (error) {
            console.error(`❌ Failed to process auto cash-out for bet ${bet.betId}:`, error.message);
        }
        
        // Broadcast the cash-out to ALL clients
        io.emit('playerCashedOut', {
            odapuId: bet.odapuId,
            username: bet.username || bet.odapu,
            betId: bet.betId,
            multiplier: bet.autoCashOutAt,
            winAmount: bet.winAmount,
            isAutoCashout: true
        });
        
        // Notify the specific player's socket about their auto cash-out
        const playerSocket = io.sockets.sockets.get(bet.socketId);
        if (playerSocket) {
            playerSocket.emit('autoCashoutTriggered', {
                betId: bet.betId,
                multiplier: bet.autoCashOutAt,
                winAmount: bet.winAmount,
                sectionNo: bet.sectionNo
            });
        }
    }
}

/**
 * Call Laravel API to process cash-out (update database and wallet)
 * SECURITY: Includes shared secret for authentication
 * 
 * CONFIGURATION: Set LARAVEL_URL environment variable or use default
 * For production: Set LARAVEL_URL=http://your-domain.com or use internal IP
 */
async function callLaravelCashout(betId, multiplier) {
    const http = require('http');
    const https = require('https');
    const url = require('url');
    
    // SECURITY: Shared secret - must match the one in Laravel Gamesetting.php
    const SOCKET_SERVER_SECRET = 'aviator_socket_secret_2024_xK9mP2nQ';
    
    // CONFIGURATION: Laravel API URL
    // Default to localhost:8000 for development
    // In production, set LARAVEL_URL environment variable
    const LARAVEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
    const apiUrl = `${LARAVEL_URL}/api/auto-cashout`;
    
    console.log(`🔄 Calling Laravel auto-cashout API: ${apiUrl} for bet ${betId} at ${multiplier}x`);
    
    return new Promise((resolve, reject) => {
        const postData = JSON.stringify({
            bet_id: betId,
            win_multiplier: multiplier,
            is_auto_cashout: true
        });
        
        const parsedUrl = url.parse(apiUrl);
        const isHttps = parsedUrl.protocol === 'https:';
        const httpModule = isHttps ? https : http;
        
        const options = {
            hostname: parsedUrl.hostname,
            port: parsedUrl.port || (isHttps ? 443 : 80),
            path: parsedUrl.path,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData),
                'X-Socket-Secret': SOCKET_SERVER_SECRET  // SECURITY: Authentication header
            }
        };
        
        console.log(`📡 Request options: ${JSON.stringify({ hostname: options.hostname, port: options.port, path: options.path })}`);
        
        const req = httpModule.request(options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                console.log(`📥 Laravel response (${res.statusCode}): ${data.substring(0, 200)}`);
                try {
                    const result = JSON.parse(data);
                    if (result.isSuccess) {
                        console.log(`✅ Auto cash-out processed for bet ${betId}: +${result.data.cash_out_amount}`);
                        resolve(result);
                    } else {
                        console.error(`❌ Auto cash-out failed for bet ${betId}: ${result.message}`);
                        reject(new Error(result.message || 'Cash-out failed'));
                    }
                } catch (e) {
                    console.error(`❌ Invalid JSON response from Laravel: ${data.substring(0, 100)}`);
                    reject(new Error('Invalid response from Laravel'));
                }
            });
        });
        
        req.on('error', (e) => {
            console.error(`❌ Laravel API error for bet ${betId}: ${e.message}`);
            reject(e);
        });
        
        req.setTimeout(5000, () => {
            req.destroy();
            console.error(`❌ Laravel API timeout for bet ${betId}`);
            reject(new Error('Request timeout'));
        });
        
        req.write(postData);
        req.end();
    });
}

/**
 * Simulate fake players cashing out at various multipliers
 * This makes the game look more active and realistic
 */
function simulateFakeCashouts(currentMultiplier) {
    // Only process fake cashouts occasionally (every ~500ms on average)
    if (Math.random() > 0.2) return;
    
    // Get active fake bets
    const activeFakeBets = Array.from(gameState.bets.values())
        .filter(bet => bet.isFake && bet.status === 'active');
    
    if (activeFakeBets.length === 0) return;
    
    // Randomly select 1-5 fake bets to cash out
    const numCashouts = Math.floor(Math.random() * 5) + 1;
    
    for (let i = 0; i < numCashouts && activeFakeBets.length > 0; i++) {
        // Higher multipliers = fewer cashouts (more realistic)
        const cashoutChance = Math.max(0.1, 1 - (currentMultiplier / 10));
        if (Math.random() > cashoutChance) continue;
        
        const randomIndex = Math.floor(Math.random() * activeFakeBets.length);
        const bet = activeFakeBets[randomIndex];
        
        if (bet && bet.status === 'active') {
            bet.status = 'cashed_out';
            bet.cashOutMultiplier = currentMultiplier;
            bet.winAmount = bet.amount * currentMultiplier;
            
            // Broadcast the fake cashout to all clients
            io.emit('playerCashedOut', {
                odapuId: bet.odapuId,
                username: bet.username || bet.odapu,
                betId: bet.betId,
                multiplier: currentMultiplier,
                winAmount: bet.winAmount
            });
            
            // Remove from active list for next iteration
            activeFakeBets.splice(randomIndex, 1);
        }
    }
}

/**
 * Crash the game
 */
function crashGame() {
    gameState.gameStatus = 'crashed';
    
    // Track crashed phase timing (2 second delay before next round)
    gameState.phaseStartTime = Date.now();
    gameState.phaseDuration = 2000;
    
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
