/**
 * Force Socket Initialization
 * If socket isn't auto-connecting, this forces it
 * Add this script AFTER all other socket scripts in crash.blade.php
 */

(function() {
    console.log('🔧 Force Socket Init Running...');
    
    // Wait for DOM to be fully ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSocket);
    } else {
        initializeSocket();
    }
    
    function initializeSocket() {
        console.log('📡 Initializing socket connection...');
        
        // Check if already initialized
        if (typeof aviatorSocket !== 'undefined' && aviatorSocket !== null) {
            console.log('✅ Socket already initialized');
            if (!aviatorSocket.isSocketConnected()) {
                console.log('⚠️ Socket exists but not connected, attempting connection...');
                aviatorSocket.connect();
            }
            return;
        }
        
        // Initialize if not already done
        if (typeof initAviatorSocket !== 'function') {
            console.error('❌ initAviatorSocket function not found!');
            console.log('Make sure socket-client.js is loaded before this script');
            return;
        }
        
        // Get socket server URL
        const serverUrl = window.SOCKET_SERVER_URL || 'http://localhost:3000';
        console.log('🌐 Connecting to:', serverUrl);
        
        // Initialize socket
        try {
            const socket = initAviatorSocket(serverUrl);
            
            // Wait and check connection
            setTimeout(() => {
                if (socket.isSocketConnected()) {
                    console.log('✅ Socket connected successfully!');
                    console.log('🎮 Game sync is active');
                    
                    // Try to initialize sync if function exists
                    if (typeof initSocketSync === 'function') {
                        console.log('🔄 Initializing socket sync...');
                        initSocketSync();
                    }
                } else {
                    console.error('❌ Socket failed to connect');
                    console.log('Troubleshooting:');
                    console.log('1. Check if socket server is running: node socket-server.js');
                    console.log('2. Check server URL:', serverUrl);
                    console.log('3. Check browser console for errors');
                    console.log('4. Try: curl http://localhost:3000/health');
                }
            }, 2000);
            
        } catch (error) {
            console.error('❌ Error initializing socket:', error);
        }
    }
    
    // Also check if Socket.IO library is loaded
    if (typeof io === 'undefined') {
        console.error('❌ Socket.IO library not loaded!');
        console.log('Make sure this line is in your HTML:');
        console.log('<script src="/socket.io/socket.io.js"></script>');
    }
})();
