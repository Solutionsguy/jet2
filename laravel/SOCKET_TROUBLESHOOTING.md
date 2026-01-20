# Socket.IO Troubleshooting Guide

## Issue: Two Tabs Show Different Events

This happens because your tabs are running independently. Here's how to fix it:

### ✅ Solution Implemented

I've created a **master-slave architecture** where:
- One tab becomes the "master" and controls game timing
- All tabs listen to the same Socket.IO server
- All game events are synchronized through sockets

### 📋 Checklist - Verify These Steps

#### 1. Socket Server is Running
```bash
# Check if socket server is running
curl http://localhost:3000/health

# Expected response:
{
  "status": "ok",
  "connections": 2,  # Number of tabs connected
  "gameStatus": "waiting",
  "currentMultiplier": 1.00
}
```

#### 2. Both Tabs Are Connected
Open browser console (F12) in **both tabs** and verify:
```
✓ Connected to game server
Socket sync initialized. Master: true/false
```

One tab should show `Master: true`, others `Master: false`

#### 3. Check Game State Endpoint
```bash
curl http://localhost:3000/game-state

# Response shows current game:
{
  "gameId": 123,
  "status": "flying",
  "multiplier": 2.34,
  "targetMultiplier": 5.5,
  "betsCount": 3,
  "playersCount": 2
}
```

#### 4. Verify Scripts Are Loaded
In browser console, type:
```javascript
getAviatorSocket()
```
Should return an object, not undefined.

#### 5. Check Laravel Routes
Verify these routes work:
```bash
curl -X POST http://127.0.0.1:8000/game/new_game_generated \
  -H "Content-Type: application/json" \
  -d '{"_token": "your-token"}'
```

### 🔧 Common Issues & Fixes

#### Issue 1: "Socket not connected" in console
**Cause**: Socket server not running or wrong URL

**Fix**:
1. Check `.env` has: `SOCKET_SERVER_URL=http://localhost:3000`
2. Restart socket server: `node socket-server.js`
3. Check firewall isn't blocking port 3000

#### Issue 2: Tabs still showing different multipliers
**Cause**: Old JavaScript is cached or scripts not loaded

**Fix**:
1. Hard refresh both tabs: `Ctrl+Shift+R` (Windows) or `Cmd+Shift+R` (Mac)
2. Clear browser cache
3. Check browser console for JavaScript errors
4. Verify all 3 scripts are loaded:
   - `socket-client.js`
   - `aviator-socket-integration.js`
   - `game-socket-sync.js`

#### Issue 3: Game starts in one tab but not others
**Cause**: Master tab coordination issue

**Fix**:
1. Close all tabs
2. Clear localStorage: In console run `localStorage.clear()`
3. Open fresh tabs
4. First tab opened becomes master

#### Issue 4: Bets not showing in other tabs
**Cause**: Socket events not broadcasting

**Fix**:
1. Check server console for "Bet placed:" logs
2. Verify Laravel is returning `socket_data` in response
3. Check browser console for socket errors

#### Issue 5: Multiplier not updating smoothly
**Cause**: Network latency or CPU throttling

**Fix**:
1. Check network tab for slow socket messages
2. Ensure socket server has adequate resources
3. Check for JavaScript errors in console

### 🧪 Testing Steps

#### Test 1: Basic Connection
1. Start socket server: `node socket-server.js`
2. Start Laravel: `php artisan serve`
3. Open game in Tab 1
4. Open browser console, check for: "✓ Connected to game server"
5. Open game in Tab 2
6. Check socket server console: Should show 2 connections

#### Test 2: Game Synchronization
1. Open game in 2 tabs side by side
2. Wait for game to start
3. **Both tabs should show**:
   - Same "STARTING SOON" countdown
   - Same plane animation starting
   - Same multiplier increasing
   - Same crash point

#### Test 3: Bet Synchronization
1. In Tab 1: Place a bet
2. In Tab 2: Should immediately see the bet appear
3. In Tab 1: Cash out
4. In Tab 2: Should see cash out notification

#### Test 4: Multiple Users
1. Open game in Chrome (User 1)
2. Open game in Firefox (User 2)
3. Place bets in both browsers
4. Both should see each other's bets
5. Cash out in one browser
6. Other browser should see the notification

### 📊 Debug Commands

#### Check Socket Server Status
```bash
# Health check
curl http://localhost:3000/health

# Game state
curl http://localhost:3000/game-state

# Check port is listening
netstat -ano | findstr :3000   # Windows
lsof -i :3000                  # Linux/Mac
```

#### Browser Console Commands
```javascript
// Get socket instance
const socket = getAviatorSocket();

// Check connection status
socket.isSocketConnected()

// Get current game state
socket.getGameState()

// Check if this tab is master
console.log('Is Master:', isSocketMaster);

// Manually emit test event
socket.socket.emit('getPlayersCount');
```

#### Server Console
The socket server logs should show:
```
Client connected: xyz123
Player joined: { userId: 1, username: 'John' }
Bet placed: { amount: 100, userId: 1 }
Game started: { gameId: 456 }
Cash out requested: { betId: 789 }
Game crashed at: 3.45x
```

### 🔍 Detailed Log Analysis

#### What to Look For in Browser Console:

**Good (Synchronized)**:
```
✓ Connected to game server
Socket sync initialized. Master: true
🎮 [SOCKET] Game started: 123
💰 [SOCKET] Bet placed by: John
🎮 [MASTER] Starting new game: 124 target: 5.5x
💥 [SOCKET] Game crashed at: 5.5x
```

**Bad (Not Synchronized)**:
```
✗ Disconnected from game server
Socket not connected
Socket not initialized
```

#### What to Look For in Socket Server Console:

**Good**:
```
Client connected: abc123
Player joined: { userId: 1, username: 'John' }
Bet placed: { userId: 1, amount: 100 }
```

**Bad**:
```
connect_error: ...
disconnect: ...
Error: ...
```

### 🐛 Advanced Debugging

#### Enable Verbose Socket Logging
Add to `socket-client.js` after line 20:
```javascript
this.socket = io(this.serverUrl, {
    transports: ['websocket', 'polling'],
    reconnection: true,
    reconnectionDelay: 1000,
    reconnectionAttempts: 5,
    debug: true  // Add this line
});
```

#### Monitor All Socket Events
In browser console:
```javascript
const socket = getAviatorSocket();
const originalEmit = socket.socket.emit;
socket.socket.emit = function(...args) {
    console.log('EMIT:', args[0], args[1]);
    return originalEmit.apply(this, args);
};

const originalOn = socket.socket.on;
socket.socket.on = function(...args) {
    console.log('ON:', args[0]);
    return originalOn.apply(this, args);
};
```

#### Check Network Tab
1. Open DevTools → Network tab
2. Filter by "WS" (WebSocket)
3. Click on socket.io connection
4. View Messages tab
5. Should see messages flowing in real-time

### ✅ Verification Checklist

Before reporting issues, verify:

- [ ] Socket server is running (`curl http://localhost:3000/health`)
- [ ] Laravel server is running (`http://127.0.0.1:8000` loads)
- [ ] Both tabs show "Connected to game server" in console
- [ ] Browser console has no red errors
- [ ] Socket server console shows 2 connections
- [ ] `.env` has correct `SOCKET_SERVER_URL`
- [ ] Hard refreshed browser (`Ctrl+Shift+R`)
- [ ] Cleared browser cache and localStorage
- [ ] No firewall blocking port 3000
- [ ] Node.js version is 14 or higher (`node --version`)

### 📞 Still Not Working?

If you've tried everything above and it's still not working:

1. **Restart Everything**:
   ```bash
   # Stop socket server (Ctrl+C)
   # Stop Laravel (Ctrl+C)
   # Close all browser tabs
   
   # Start fresh
   cd laravel
   node socket-server.js
   
   # New terminal
   php artisan serve
   
   # Open one browser tab to: http://127.0.0.1:8000/crash
   ```

2. **Check the Basics**:
   - Is Node.js installed? `node --version`
   - Are dependencies installed? `ls node_modules/socket.io`
   - Can you access health endpoint? `curl http://localhost:3000/health`

3. **Inspect Network**:
   - Open DevTools → Network → WS filter
   - Look for socket.io connection
   - Check if messages are being sent/received

4. **Look at Console**:
   - Browser console (F12) for frontend errors
   - Terminal for socket server logs
   - Laravel logs at `laravel/storage/logs/laravel.log`

### 💡 Quick Fixes

**Clear Everything and Start Fresh**:
```javascript
// In browser console:
localStorage.clear();
sessionStorage.clear();
location.reload(true);
```

**Force Tab to Become Master**:
```javascript
// In browser console:
localStorage.setItem('aviator_master_tab', Date.now().toString());
location.reload();
```

**Manually Trigger Game Start**:
```javascript
// In browser console (when master tab):
const socket = getAviatorSocket();
socket.startGame(Date.now(), 5.5);
```

---

## Expected Behavior After Fix

✅ **When working correctly**:
1. Open Tab 1 → Connects to socket
2. Open Tab 2 → Connects to socket
3. Tab 1 (master) initiates game start
4. Socket server broadcasts to all tabs
5. Both tabs show EXACT same:
   - Countdown
   - Plane takeoff
   - Multiplier increase (1.00x → 2.00x → 3.00x...)
   - Crash at same multiplier
   - Bets from all users
   - Cash outs from all users

✅ **Real-time synchronization**:
- Bet in Tab 1 → Appears instantly in Tab 2
- Cash out in Tab 2 → Shows in Tab 1 immediately
- No delays, no desync, exact same game state

---

**Last Updated**: After implementing master-slave sync architecture
