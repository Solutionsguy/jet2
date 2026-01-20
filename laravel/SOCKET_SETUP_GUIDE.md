# Aviator Game - Socket.IO Setup Guide

## Overview
This guide will help you set up real-time Socket.IO communication for your Aviator game. The socket system enables live multiplayer features, real-time bet updates, and synchronized gameplay.

## Architecture

### Components Created:
1. **Socket Server** (`socket-server.js`) - Node.js Socket.IO server
2. **Laravel Events** - Broadcasting events for game actions
3. **Socket Client** (`socket-client.js`) - Frontend Socket.IO client
4. **Integration Layer** (`aviator-socket-integration.js`) - Bridges socket with existing game logic

## Prerequisites

- Node.js (v14 or higher) - [Download](https://nodejs.org/)
- npm (comes with Node.js)
- Laravel application running
- Socket.IO dependencies already in package.json

## Installation Steps

### 1. Install Dependencies

```bash
cd laravel
npm install
```

This will install:
- express (v4.18.2)
- socket.io (v4.5.4)
- request (v2.88.2)

### 2. Configure Environment Variables

Add to your `laravel/.env` file:

```env
# Socket.IO Configuration
SOCKET_PORT=3000
SOCKET_SERVER_URL=http://localhost:3000

# Broadcasting
BROADCAST_DRIVER=log
```

For production, update:
```env
SOCKET_SERVER_URL=http://your-domain.com:3000
```

### 3. Start the Socket Server

#### Windows:
```bash
cd laravel
start-socket-server.bat
```

#### Linux/Mac:
```bash
cd laravel
chmod +x start-socket-server.sh
./start-socket-server.sh
```

#### Manual Start:
```bash
cd laravel
node socket-server.js
```

You should see:
```
Socket.IO server running on port 3000
Health check available at http://localhost:3000/health
```

### 4. Update Your HTML Template

Add Socket.IO client library and your socket scripts to `laravel/resources/views/crash.blade.php`:

```html
<!-- Before closing </body> tag -->

<!-- Socket.IO Client Library -->
<script src="/socket.io/socket.io.js"></script>

<!-- Your Socket Client -->
<script src="{{ asset('user/socket-client.js') }}"></script>

<!-- Socket Integration -->
<script>
    // Define user variables for socket
    var user_id = {{ Auth::check() ? Auth::id() : 'null' }};
    var username = "{{ Auth::check() ? Auth::user()->username : 'Guest' }}";
    var user_avatar = "{{ Auth::check() && Auth::user()->image ? Auth::user()->image : '/images/avtar/av-1.png' }}";
    var SOCKET_SERVER_URL = "{{ env('SOCKET_SERVER_URL', 'http://localhost:3000') }}";
</script>
<script src="{{ asset('user/aviator-socket-integration.js') }}"></script>

<!-- Your existing game scripts -->
<script src="{{ asset('user/aviatorbyapp.js') }}"></script>
```

### 5. Test the Connection

1. Start the Laravel server:
   ```bash
   php artisan serve
   ```

2. Start the Socket.IO server (in a separate terminal):
   ```bash
   cd laravel
   node socket-server.js
   ```

3. Open your browser and navigate to the game page

4. Check the browser console (F12) - you should see:
   ```
   Connected to Socket.IO server
   ✓ Connected to game server
   ```

5. Check the server health:
   ```bash
   curl http://localhost:3000/health
   ```

   Response:
   ```json
   {
     "status": "ok",
     "connections": 1,
     "gameStatus": "waiting",
     "currentMultiplier": 1.00
   }
   ```

## Socket Events Reference

### Client → Server Events

| Event | Data | Description |
|-------|------|-------------|
| `playerJoin` | `{userId, username}` | Player joins the game |
| `placeBet` | `{userId, amount, betId, username, avatar}` | Place a new bet |
| `cashOut` | `{betId, userId, username, multiplier}` | Cash out a bet |
| `startGame` | `{gameId, targetMultiplier}` | Start new game (server/admin) |
| `getPlayersCount` | - | Request current players count |

### Server → Client Events

| Event | Data | Description |
|-------|------|-------------|
| `gameState` | `{gameId, status, multiplier, players}` | Current game state |
| `gameStarted` | `{gameId, timestamp}` | Game has started |
| `multiplierUpdate` | `{multiplier}` | Real-time multiplier update |
| `gameCrashed` | `{gameId, crashMultiplier, results}` | Game crashed |
| `gameReset` | `{status}` | Game reset for next round |
| `betPlaced` | `{userId, username, amount, betId, avatar}` | New bet placed |
| `playerCashedOut` | `{userId, username, betId, multiplier, winAmount}` | Player cashed out |
| `betError` | `{message}` | Bet error |
| `cashOutError` | `{message}` | Cash out error |

## Integration with Laravel

### Update Game Controller

Modify `laravel/app/Http/Controllers/Gamesetting.php`:

```php
use App\Events\GameStarted;
use App\Events\GameCrashed;
use App\Events\BetPlaced;

// In new_game_generated method:
public function new_game_generated(Request $r) {
    $new = Setting::where('category', 'game_status')->update(['value' => '0']);
    $r->session()->put('gamegenerate','1');
    $gameId = currentid();
    
    // Broadcast game started event
    broadcast(new GameStarted($gameId, rand(8,11)))->toOthers();
    
    return response()->json(array("id" => $gameId));
}

// In betNow method:
public function betNow(Request $r) {
    // ... existing code ...
    
    if ($result->save()) {
        // Broadcast bet placed
        broadcast(new BetPlaced(
            user('id'),
            user('username'),
            $r->all_bets[$i]['bet_amount'],
            $result->id,
            user('image')
        ))->toOthers();
        
        // ... rest of code ...
    }
}
```

## Production Deployment

### Using PM2 (Recommended)

1. Install PM2:
   ```bash
   npm install -g pm2
   ```

2. Start server with PM2:
   ```bash
   cd laravel
   pm2 start socket-server.js --name aviator-socket
   pm2 save
   pm2 startup
   ```

3. Monitor:
   ```bash
   pm2 status
   pm2 logs aviator-socket
   ```

### Nginx Configuration

Add to your Nginx config:

```nginx
# Socket.IO proxy
location /socket.io/ {
    proxy_pass http://localhost:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### Apache Configuration

Enable required modules:
```bash
a2enmod proxy
a2enmod proxy_http
a2enmod proxy_wstunnel
```

Add to VirtualHost:
```apache
<Location /socket.io>
    ProxyPass http://localhost:3000/socket.io
    ProxyPassReverse http://localhost:3000/socket.io
</Location>

ProxyPass /socket.io ws://localhost:3000/socket.io
ProxyPassReverse /socket.io ws://localhost:3000/socket.io
```

## Troubleshooting

### Socket server won't start
- Check if port 3000 is already in use: `netstat -ano | findstr :3000`
- Kill process or change port in `.env` and `socket-server.js`

### Client can't connect
- Verify server is running: `curl http://localhost:3000/health`
- Check CORS settings in `socket-server.js`
- Verify firewall allows port 3000
- Check browser console for errors

### Connection keeps dropping
- Check server logs for errors
- Increase timeout settings in socket client
- Verify network stability
- Check if server has enough resources

### Bets not syncing
- Check browser console for socket events
- Verify Laravel broadcasting is configured
- Check server logs for event emissions
- Ensure database is being updated

## Testing

### Manual Testing
1. Open game in multiple browser tabs
2. Place bets in different tabs
3. Verify all tabs show the same multiplier
4. Cash out in one tab
5. Verify other tabs see the cash out

### Load Testing
Use Socket.IO load testing tool:
```bash
npm install -g artillery
artillery quick --count 100 --num 10 http://localhost:3000
```

## Security Considerations

1. **Authentication**: Add JWT or session validation
2. **Rate Limiting**: Implement rate limiting for bet placement
3. **Input Validation**: Validate all incoming data
4. **CORS**: Restrict CORS to your domain in production
5. **SSL/TLS**: Use HTTPS in production

## Performance Optimization

1. **Redis Adapter**: For multiple server instances
   ```bash
   npm install @socket.io/redis-adapter redis
   ```

2. **Clustering**: Use Node.js cluster module
3. **Load Balancing**: Use Nginx or HAProxy
4. **Monitoring**: Add monitoring with tools like New Relic or Datadog

## Support

For issues or questions:
- Check server logs: `pm2 logs aviator-socket`
- Check browser console for errors
- Verify all dependencies are installed
- Ensure Node.js version is compatible

## Next Steps

1. Add authentication/authorization
2. Implement Redis for scaling
3. Add comprehensive logging
4. Set up monitoring and alerts
5. Implement backup and recovery procedures
