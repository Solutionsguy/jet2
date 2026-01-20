/**
 * Socket Debug Helper
 * Paste this into browser console to diagnose issues
 */

console.log('🔍 Starting Socket Debug...\n');

// Check 1: Is Socket.IO library loaded?
console.log('1️⃣ Checking Socket.IO library...');
if (typeof io !== 'undefined') {
    console.log('✅ Socket.IO library loaded');
} else {
    console.error('❌ Socket.IO library NOT loaded - Check if /socket.io/socket.io.js is accessible');
}

// Check 2: Is aviatorSocket instance created?
console.log('\n2️⃣ Checking aviatorSocket instance...');
if (typeof aviatorSocket !== 'undefined' && aviatorSocket !== null) {
    console.log('✅ aviatorSocket instance exists');
    console.log('   Connected:', aviatorSocket.isConnected);
    console.log('   Server URL:', aviatorSocket.serverUrl);
    console.log('   Game State:', aviatorSocket.gameState);
} else {
    console.error('❌ aviatorSocket NOT initialized');
    console.log('   Trying to initialize now...');
    if (typeof initAviatorSocket === 'function') {
        const socket = initAviatorSocket(SOCKET_SERVER_URL || 'http://localhost:3000');
        console.log('   Initialized:', socket);
    } else {
        console.error('   initAviatorSocket function not found!');
    }
}

// Check 3: Are required variables defined?
console.log('\n3️⃣ Checking required variables...');
const requiredVars = {
    'SOCKET_SERVER_URL': typeof SOCKET_SERVER_URL !== 'undefined' ? SOCKET_SERVER_URL : '❌ NOT DEFINED',
    'user_id': typeof user_id !== 'undefined' ? user_id : '❌ NOT DEFINED',
    'username': typeof username !== 'undefined' ? username : '❌ NOT DEFINED',
    'hash_id': typeof hash_id !== 'undefined' ? '✅ Defined' : '❌ NOT DEFINED'
};
console.table(requiredVars);

// Check 4: Are socket functions available?
console.log('\n4️⃣ Checking socket functions...');
const functions = {
    'getAviatorSocket': typeof getAviatorSocket === 'function' ? '✅' : '❌',
    'initAviatorSocket': typeof initAviatorSocket === 'function' ? '✅' : '❌',
    'initSocketSync': typeof initSocketSync === 'function' ? '✅' : '❌',
    'placeSocketBet': typeof placeSocketBet === 'function' ? '✅' : '❌',
    'cashOutSocket': typeof cashOutSocket === 'function' ? '✅' : '❌'
};
console.table(functions);

// Check 5: Test socket connection
console.log('\n5️⃣ Testing socket connection...');
if (typeof getAviatorSocket === 'function') {
    const socket = getAviatorSocket();
    if (socket) {
        setTimeout(() => {
            if (socket.isSocketConnected()) {
                console.log('✅ Socket is CONNECTED');
                socket.getPlayersCount();
            } else {
                console.error('❌ Socket is DISCONNECTED');
                console.log('   Attempting to reconnect...');
                if (socket.socket) {
                    socket.socket.connect();
                }
            }
        }, 2000);
    }
} else {
    console.error('❌ Cannot test - getAviatorSocket not available');
}

// Check 6: Check server health
console.log('\n6️⃣ Checking server health...');
fetch('http://localhost:3000/health')
    .then(res => res.json())
    .then(data => {
        console.log('✅ Server Health:', data);
        if (data.connections === 0) {
            console.warn('⚠️ Server has 0 connections - Your browser is not connected!');
        }
    })
    .catch(err => {
        console.error('❌ Cannot reach server:', err.message);
        console.log('   Make sure socket server is running: node socket-server.js');
    });

// Check 7: Check if master/slave is set up
console.log('\n7️⃣ Checking master/slave setup...');
setTimeout(() => {
    if (typeof isSocketMaster !== 'undefined') {
        console.log('   This tab is:', isSocketMaster ? '👑 MASTER' : '👥 SLAVE');
        console.log('   Master Tab:', localStorage.getItem('aviator_master_tab'));
    } else {
        console.warn('⚠️ isSocketMaster not defined - game-socket-sync.js may not be loaded');
    }
}, 1000);

console.log('\n📋 Summary:');
console.log('═══════════════════════════════════════════════════════════');
console.log('If you see errors above, that\'s the problem!');
console.log('Common fixes:');
console.log('1. Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)');
console.log('2. Clear cache and reload');
console.log('3. Check browser console for red errors');
console.log('4. Make sure socket server is running: node socket-server.js');
console.log('═══════════════════════════════════════════════════════════\n');
