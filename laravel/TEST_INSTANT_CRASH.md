# Testing True Instant Crash at 1.00x

## Test Setup
1. **Restart Socket Server**
   ```bash
   node laravel/socket-server.js
   ```

2. **Open Browser Console** (F12)
   - You'll see detailed debug logs

3. **Place a Real Bet**
   - The bet must deduct from your wallet (not a fake bet)
   - This triggers AGGRESSIVE mode

## Expected Behavior

### ✅ Server Logs Should Show:
```
✅ REAL BET placed: Guest - 100.00 (Total real bets: 1, Total bets: xxx)
🎯 AGGRESSIVE MODE: 1 real bets - Crash point: 1.00x
💥 Game xxx crashed at 1.00x
```

### ✅ Browser Console Should Show:
```
💥 [SERVER] Game crashed at 1.00x
🔍 [DEBUG] Current UI multiplier before update: 1.XXx
🔍 [DEBUG] UI multiplier after force update: 1.00x
🔍 [DEBUG] UI multiplier after crash_plane(): 1.00x
🔍 [DEBUG] UI multiplier after gameover(): 1.00x
```

### ✅ UI Should Display:
- Main multiplier: **1.00x** (not 1.2x or any other value)
- History bar: Shows **1.00x** in the crash history

## What Each Component Does

1. **Server calculates 1.00x** → `trueInstantCrashChance: 1.0`
2. **Server forces crash at 1.00x** → Even if game is at 1.19x
3. **Client blocks late updates** → `ignoreMultiplierUpdates = true`
4. **Client sets UI to 1.00x** → `incrementor()` and `updateMultiplierDisplay()`

## If UI Still Shows Wrong Value

Check the debug logs to see which function is changing it:
- If it changes after `crash_plane()` → Problem in `aviatorold.js crash_plane()`
- If it changes after `gameover()` → Problem in `aviatorold.js gameover()`
- If all logs show 1.00x but UI shows 1.2x → CSS/DOM issue

## Configuration

Current setting (100% instant crash):
```javascript
// laravel/socket-server.js line 97
trueInstantCrashChance: 1.0,  // 100% = always crash at 1.00x
```

To adjust percentage:
```javascript
trueInstantCrashChance: 0.5,  // 50% instant crash at 1.00x
instantCrashChance: 0.3,      // 30% crash at 1.01-1.05x
lowCrashChance: 0.2,          // 20% crash at 1.05-1.50x
```
