/**
 * Chat System for Aviator Game
 */

class AviatorChat {
    constructor() {
        this.messages = [];
        this.rainDataCache = {}; // Cache for rain data
        this.isInitialized = false;
        this.messageLimit = 50;
        this.pollInterval = null;
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        console.log('🗨️ Initializing chat system...');
        
        // Load initial messages
        this.loadMessages();
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Start polling for new messages (every 3 seconds)
        this.startPolling();
        
        this.isInitialized = true;
    }

    setupEventListeners() {
        const chatInput = document.getElementById('chat-input');
        const chatSendBtn = document.getElementById('chat-send-btn');

        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        if (chatSendBtn) {
            chatSendBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        console.log('✅ Chat event listeners set up');
    }

    async loadMessages(forceUpdate = false) {
        try {
            const response = await fetch('/chat/messages?limit=' + this.messageLimit);
            const data = await response.json();

            if (data.success && data.messages) {
                const oldCount = this.messages.length;
                const newMessages = data.messages;
                
                // Store rain data for quick access
                this.rainDataCache = data.rain_data || {};
                console.log('🌧️ Rain data cached:', Object.keys(this.rainDataCache).length, 'rain(s)');
                
                // Update messages
                this.messages = newMessages;
                
                // Force full re-render if requested (e.g., after claiming rain)
                if (forceUpdate || newMessages.length !== oldCount) {
                    this.renderMessages();
                } else if (newMessages.length > oldCount) {
                    // Add only the new messages
                    const newMessagesToAdd = newMessages.slice(oldCount);
                    this.appendNewMessages(newMessagesToAdd);
                }
            }
            
            // Rain cards are now rendered as part of chat messages (via __RAIN_CARD__ marker)
            // No need to inject separately
        } catch (error) {
            console.error('❌ Failed to load chat messages:', error);
        }
    }
    
    async checkAndInjectRainCard() {
        let data = null;
        try {
            const response = await fetch('/rain/active');
            data = await response.json();
            
            if (data.success && data.data) {
                // Check if rain card placeholder or card exists
                const existingCard = $('#rain-chat-card');
                if (existingCard.length > 0) {
                    // Update existing card
                    existingCard.replaceWith(this.createRainCard(data.data, data.data.participant_count));
                }
            }
        } catch (error) {
            // Silent fail
            console.error('Rain card check failed:', error);
        }
        
        return data;
    }
    
    createRainCard(rainData, participantCount, creatorUsername = null, creatorAvatar = null, isCompleted = false) {
        const rain = rainData.rain;
        const participants = rainData.participants || [];
        const slotsAvailable = isCompleted ? 0 : (rainData.slots_available || rain.num_winners);
        
        const div = document.createElement('div');
        div.className = 'chat-message rain-card';
        if (isCompleted) {
            div.classList.add('rain-card-completed');
        }
        div.id = isCompleted ? 'rain-chat-card-' + rain.id : 'rain-chat-card';
        
        // Create slots (claimed + unclaimed)
        let slotsHtml = '';
        
        // Show claimed slots
        participants.forEach((participant, index) => {
            const maskedUsername = this.maskUsername(participant.username);
            const avatar = participant.avatar || '/images/avtar/av-1.png';
            
            slotsHtml += `
                <div class="rain-participant-item claimed">
                    <div class="rain-participant-amount">${parseFloat(participant.amount).toFixed(2)} KSH</div>
                    <div class="rain-participant-user">
                        <img src="${avatar}" class="rain-participant-avatar" alt="${maskedUsername}">
                        <span class="rain-participant-username">${maskedUsername}</span>
                    </div>
                </div>
            `;
        });
        
        // Show unclaimed slots ONLY if rain is still active
        if (!isCompleted) {
            for (let i = 0; i < slotsAvailable; i++) {
                slotsHtml += `
                    <div class="rain-participant-item unclaimed">
                        <div class="rain-participant-amount">${parseFloat(rain.amount_per_user).toFixed(2)} KSH</div>
                        <button class="rain-claim-btn" onclick="window.rainSystem.joinRainFromChat()">
                            Claim
                        </button>
                    </div>
                `;
            }
        }
        
        // Use creator info or default to SUPPORT
        const displayUsername = creatorUsername || 'SUPPORT';
        const displayAvatar = creatorAvatar || '/images/avtar/av-1.png';
        // Don't mask SUPPORT username, show it as-is
        const maskedDisplayUsername = (creatorUsername && creatorUsername !== 'SUPPORT') ? this.maskUsername(creatorUsername) : 'SUPPORT';
        
        div.innerHTML = `
            <div class="rain-card-header">
                <div class="rain-card-logo">
                    ${creatorUsername ? '<img src="' + displayAvatar + '" class="rain-card-logo-img">' : '🌀'}
                </div>
                <div class="rain-card-title">
                    <div class="username">${maskedDisplayUsername}</div>
                    <div class="likes">1 👍</div>
                </div>
            </div>
            
            <div class="rain-participants-list" id="rain-participants-display">
                ${slotsHtml}
            </div>
            
            <div class="rain-card-footer">
                <span class="rain-icon">🌧️</span>
                Freebets Rain | Total ${parseFloat(rain.total_amount).toFixed(2)} KSH
            </div>
        `;
        
        return div;
    }
    
    appendNewMessages(newMessages) {
        const chatContainer = document.getElementById('chat-messages');
        if (!chatContainer) return;
        
        newMessages.forEach(msg => {
            const messageEl = this.createMessageElement(msg);
            chatContainer.appendChild(messageEl);
        });
        
        // Auto-scroll to bottom
        this.scrollToBottom();
    }

    async sendMessage() {
        const chatInput = document.getElementById('chat-input');
        const message = chatInput?.value?.trim();

        if (!message) {
            return;
        }

        // Check if user is logged in
        if (typeof hash_id === 'undefined') {
            toastr.error('Please login to send messages');
            return;
        }

        try {
            const response = await fetch('/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': hash_id,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message })
            });

            const data = await response.json();

            if (data.success) {
                // Clear input
                chatInput.value = '';
                
                // Don't add to local array - let the next poll fetch it
                // This prevents duplicate messages
                
                // Reload messages immediately to show the new message
                this.loadMessages();
            } else {
                toastr.error(data.message || 'Failed to send message');
            }
        } catch (error) {
            console.error('❌ Failed to send message:', error);
            toastr.error('Failed to send message');
        }
    }

    renderMessages() {
        const chatContainer = document.getElementById('chat-messages');
        if (!chatContainer) return;

        // Clear container
        chatContainer.innerHTML = '';

        // Render messages
        this.messages.forEach(msg => {
            const messageEl = this.createMessageElement(msg);
            chatContainer.appendChild(messageEl);
        });

        // Auto-scroll to bottom
        this.scrollToBottom();
    }

    createMessageElement(msg) {
        // Check if this is a rain card message
        if (msg.message && msg.message.startsWith('__RAIN_CARD__')) {
            const rainId = msg.message.replace('__RAIN_CARD__', '');
            return this.createRainCardPlaceholder(rainId, msg.username, msg.avatar);
        }
        
        const div = document.createElement('div');
        div.className = 'chat-message';
        if (msg.is_admin) {
            div.classList.add('admin-message');
        }

        const avatar = msg.avatar || '/images/avtar/av-1.png';
        const username = this.maskUsername(msg.username);
        const time = this.formatTime(msg.created_at);

        div.innerHTML = `
            <div class="chat-message-header">
                <img src="${avatar}" alt="${username}" class="chat-avatar">
                <span class="chat-username ${msg.is_admin ? 'admin-username' : ''}">${username}</span>
                <span class="chat-time">${time}</span>
            </div>
            <div class="chat-message-body">
                ${this.escapeHtml(msg.message)}
            </div>
        `;

        return div;
    }
    
    createRainCardPlaceholder(rainId, creatorUsername = null, creatorAvatar = null) {
        // Check if rain data is already in cache
        if (this.rainDataCache && this.rainDataCache[rainId]) {
            const rainData = this.rainDataCache[rainId];
            const isCompleted = rainData.rain.status === 'completed' || rainData.slots_available === 0;
            return this.createRainCard(rainData, rainData.participant_count, creatorUsername, creatorAvatar, isCompleted);
        }
        
        // Not in cache - create placeholder and load
        const div = document.createElement('div');
        div.className = 'rain-card-placeholder';
        div.setAttribute('data-rain-id', rainId);
        div.setAttribute('data-creator-username', creatorUsername || '');
        div.setAttribute('data-creator-avatar', creatorAvatar || '');
        div.id = 'rain-chat-card';
        div.innerHTML = '<div style="text-align: center; padding: 20px; color: #d4af37;">Loading rain...</div>';
        
        // Load rain data and replace placeholder
        this.loadRainCardData(rainId, div, creatorUsername, creatorAvatar);
        
        return div;
    }
    
    async loadRainCardData(rainId, placeholder, creatorUsername = null, creatorAvatar = null) {
        try {
            // Check if rain data is already in cache (from chat messages response)
            if (this.rainDataCache && this.rainDataCache[rainId]) {
                const rainData = this.rainDataCache[rainId];
                const isCompleted = rainData.rain.status === 'completed' || rainData.slots_available === 0;
                const rainCard = this.createRainCard(rainData, rainData.participant_count, creatorUsername, creatorAvatar, isCompleted);
                placeholder.replaceWith(rainCard);
                return;
            }
            
            // Not in cache - fetch from API
            let response = await fetch('/rain/active');
            let data = await response.json();
            
            if (data.success && data.data && data.data.rain.id == rainId) {
                // Active rain - show with claim buttons
                const rainCard = this.createRainCard(data.data, data.data.participant_count, creatorUsername, creatorAvatar);
                placeholder.replaceWith(rainCard);
            } else {
                // Rain is completed - fetch completed rain data
                response = await fetch('/rain/' + rainId);
                data = await response.json();
                
                if (data.success && data.data) {
                    // Completed rain - show all winners, no claim buttons
                    const rainCard = this.createRainCard(data.data, data.data.participant_count, creatorUsername, creatorAvatar, true);
                    placeholder.replaceWith(rainCard);
                } else {
                    // Fallback - show minimal completed state
                    placeholder.innerHTML = '<div style="text-align: center; padding: 10px; color: #666;">Rain completed</div>';
                }
            }
        } catch (error) {
            console.error('Failed to load rain card:', error);
            // Keep the placeholder visible even on error
            placeholder.innerHTML = '<div style="text-align: center; padding: 10px; color: #999;">Unable to load rain</div>';
        }
    }

    maskUsername(username) {
        // Mask username like "2**7"
        if (!username || username.length < 3) {
            return username || 'U**r';
        }
        return username[0] + '**' + username[username.length - 1];
    }
    
    maskUsernameGlobal(username) {
        return this.maskUsername(username);
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        // Less than 1 minute
        if (diff < 60000) {
            return 'Just now';
        }

        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes}m ago`;
        }

        // Less than 24 hours
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours}h ago`;
        }

        // Show time
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    scrollToBottom() {
        const chatContainer = document.getElementById('chat-messages');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    startPolling() {
        // Poll for new messages every 5 seconds (reduced frequency)
        this.pollInterval = setInterval(() => {
            this.loadMessages();
        }, 5000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    destroy() {
        this.stopPolling();
        this.isInitialized = false;
    }
}

// Toggle chat popup
function toggleChat() {
    const chatSidebar = document.getElementById('chat-sidebar-popup');
    const toggleBtn = document.getElementById('chat-toggle-btn');
    const unreadBadge = document.getElementById('chat-unread-badge');
    
    chatSidebar.classList.toggle('open');
    
    // Clear unread count when opening
    if (chatSidebar.classList.contains('open')) {
        unreadBadge.style.display = 'none';
        unreadBadge.textContent = '0';
        toggleBtn.querySelector('.material-symbols-outlined').textContent = 'close';
        
        // Add click-outside listener when chat opens
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100); // Small delay to prevent immediate close
    } else {
        toggleBtn.querySelector('.material-symbols-outlined').textContent = 'chat';
        
        // Remove click-outside listener when chat closes
        document.removeEventListener('click', handleClickOutside);
    }
}

// Handle clicks outside chat to close it
function handleClickOutside(event) {
    const chatSidebar = document.getElementById('chat-sidebar-popup');
    const toggleBtn = document.getElementById('chat-toggle-btn');
    
    // Check if chat is open
    if (!chatSidebar || !chatSidebar.classList.contains('open')) {
        document.removeEventListener('click', handleClickOutside);
        return;
    }
    
    // Check if click is outside chat and toggle button
    if (!chatSidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
        // Close the chat
        chatSidebar.classList.remove('open');
        toggleBtn.querySelector('.material-symbols-outlined').textContent = 'chat';
        
        // Remove this listener
        document.removeEventListener('click', handleClickOutside);
    }
}

// Initialize chat when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('chat-container')) {
        window.aviatorChat = new AviatorChat();
        
        // Expose maskUsername globally for rain system
        window.maskUsername = function(username) {
            return window.aviatorChat.maskUsername(username);
        };
    }
});

