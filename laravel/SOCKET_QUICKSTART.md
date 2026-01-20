# Socket.IO Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Install Dependencies
```bash
cd laravel
npm install
```

### Step 2: Add Environment Variables
Add to `laravel/.env`:
```env
SOCKET_PORT=3000
SOCKET_SERVER_URL=http://localhost:3000
BROADCAST_DRIVER=log
```

### Step 3: Start Socket Server
**Windows:**
```bash
start-socket-server.bat
```

**Linux/Mac:**
```bash
chmod +x start-socket-server.sh
./start-socket-server.sh
```

### Step 4: Update Your Game View
Add these scripts to `laravel/resources/views/crash.blade.php` before `</body>`:

```html
<!-- Socket.IO Client -->
<script src="/socket.io/socket.io.js"></script>

<!-- Socket Client & Integration -->
<script>
    var user_id = {{ Auth::check() ? Auth::id() : 'null' }};
    var username = "{{ Auth::check() ? Auth::user()->username : 'Guest' }}";
    var user_avatar = "{{ Auth::check() && Auth::user()->image ? Auth::user()->image : '/images/avtar/av-1.png' }}";
    var SOCKET_SERVER_URL = "{{ env('SOCKET_SERVER_URL', 'http://localhost:3000') }}";
</script>
<script src="{{ asset('user/socket-client.js') }}"></script>
<script src="{{ asset('user/aviator-socket-integration.js') }}"></script>
```

### Step 5: Test Connection
1. Start Laravel: `php artisan serve`
2. Start Socket server (separate terminal): `node socket-server.js`
3. Open browser to game page
4. Check console: Should see "✓ Connected to game server"
5. Health check: Visit `http://localhost:3000/health`

## 📁 Files Created

### Backend (Node.js)
- ✅ `laravel/socket-server.js` - Socket.IO server
- ✅ `laravel/start-socket-server.bat` - Windows startup script
- ✅ `laravel/start-socket-server.sh` - Linux/Mac startup script

### Laravel Events
- ✅ `laravel/app/Events/ActionEvent.php` - Updated generic event
- ✅ `laravel/app/Events/GameStarted.php` - Game started event
- ✅ `laravel/app/Events/GameCrashed.php` - Game crashed event
- ✅ `laravel/app/Events/BetPlaced.php` - Bet placed event
- ✅ `laravel/app/Events/MultiplierUpdate.php` - Multiplier update event

### Frontend (JavaScript)
- ✅ `laravel/public/user/socket-client.js` - Socket.IO client wrapper
- ✅ `laravel/public/user/aviator-socket-integration.js` - Game integration

### Documentation
- ✅ `laravel/SOCKET_SETUP_GUIDE.md` - Complete setup guide
- ✅ `laravel/.env.socket.example` - Environment variables example
- ✅ `laravel/SOCKET_QUICKSTART.md` - This file

## 🎮 Key Features Implemented

### Real-time Game Events
- ✅ Game start/end synchronization
- ✅ Live multiplier updates (100ms intervals)
- ✅ Crash events broadcast to all players
- ✅ Automatic game reset after crash

### Player Interactions
- ✅ Real-time bet placement
- ✅ Live cash-out notifications
- ✅ Player join/leave tracking
- ✅ Connected players count

### Data Broadcasting
- ✅ All bets visible to all players
- ✅ Cash-out notifications
- ✅ Wallet balance updates
- ✅ Bet history refresh

## 🔧 Socket Events Available

### For Game Logic
```javascript
const socket = getAviatorSocket();

// Listen to game started
socket.on('onGameStarted', (data) => {
    console.log('Game started:', data.gameId);
});

// Listen to multiplier updates
socket.on('onMultiplierUpdate', (data) => {
    console.log('Multiplier:', data.multiplier);
});

// Listen to game crash
socket.on('onGameCrashed', (data) => {
    console.log('Crashed at:', data.crashMultiplier);
});
```

### For Player Actions
```javascript
// Place a bet
placeSocketBet(100, 'normal', 1);

// Cash out
cashOutSocket(betId, currentMultiplier);

// Start game (admin)
startSocketGame(gameId, targetMultiplier);
```

## ⚡ Quick Commands

### Start Everything
```bash
# Terminal 1: Laravel
cd laravel
php artisan serve

# Terminal 2: Socket Server
cd laravel
node socket-server.js
```

### Check Status
```bash
# Socket server health
curl http://localhost:3000/health

# Check connections
curl http://localhost:3000/health | grep connections
```

### Stop Server
```bash
# Press Ctrl+C in socket server terminal
# Or if using PM2:
pm2 stop aviator-socket
```

## 🐛 Common Issues

### Port 3000 already in use
```bash
# Windows: Find and kill process
netstat -ano | findstr :3000
taskkill /PID <PID> /F

# Linux/Mac
lsof -ti:3000 | xargs kill -9
```

### Socket.IO not connecting
1. Check server is running: `curl http://localhost:3000/health`
2. Check browser console for errors
3. Verify SOCKET_SERVER_URL in .env matches server
4. Check firewall settings

### Multiplier not updating
1. Verify socket connection in browser console
2. Check socket-server.js is running
3. Look for errors in server terminal
4. Ensure game is started via socket

## 📊 Monitoring

### Server Logs
The socket server logs all events to console:
- Client connections/disconnections
- Bet placements
- Cash outs
- Game state changes

### Browser Console
Check browser console (F12) for:
- Connection status
- Socket events
- Error messages
- Game state updates

## 🚀 Production Deployment

### Using PM2
```bash
# Install PM2
npm install -g pm2

# Start server
cd laravel
pm2 start socket-server.js --name aviator-socket

# Auto-start on reboot
pm2 save
pm2 startup

# Monitor
pm2 status
pm2 logs aviator-socket
```

### Update for Production
1. Change `.env` SOCKET_SERVER_URL to your domain
2. Configure Nginx/Apache proxy (see SOCKET_SETUP_GUIDE.md)
3. Enable SSL/TLS
4. Add authentication
5. Implement rate limiting

## 📚 Next Steps

1. **Read Full Guide**: Check `SOCKET_SETUP_GUIDE.md` for detailed information
2. **Test Multiplayer**: Open game in multiple browsers
3. **Customize Events**: Add your own socket events as needed
4. **Add Security**: Implement authentication and validation
5. **Scale**: Add Redis adapter for multiple servers

## 💡 Tips

- Keep socket server running in separate terminal
- Monitor both server and browser console for debugging
- Use health endpoint to verify server status
- Test with multiple browser tabs before production
- Consider using PM2 for production deployment

## 📞 Testing Checklist

- [ ] Socket server starts without errors
- [ ] Browser connects to socket server
- [ ] Multiple clients can connect simultaneously
- [ ] Bets appear in real-time for all users
- [ ] Multiplier updates smoothly
- [ ] Game crashes properly
- [ ] Cash-outs work correctly
- [ ] Wallet balance updates
- [ ] Reconnection works after disconnect
- [ ] Health endpoint responds

## 🎯 Socket Server URLs

- **Health Check**: `http://localhost:3000/health`
- **Socket Endpoint**: `http://localhost:3000`
- **Production**: Update to your domain (e.g., `https://yourdomain.com:3000`)

---

**Status**: ✅ Socket.IO setup complete and ready to use!

**Need Help?** Check the full guide in `SOCKET_SETUP_GUIDE.md`
