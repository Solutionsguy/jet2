@if(session()->has('userlogin'))
<!--====== Chat Toggle Button ======-->
<button class="chat-toggle-btn" id="chat-toggle-btn" onclick="toggleChat()">
    <span class="material-symbols-outlined">chat</span>
    <span class="chat-unread-badge" id="chat-unread-badge" style="display: none;">0</span>
</button>

<!--====== Chat Sidebar Start ======-->
<div class="chat-sidebar" id="chat-sidebar-popup">
    <div id="chat-container">
        <div class="chat-header">
            <h3>
                <span class="chat-online-indicator"></span>
                CHAT
            </h3>
            <div class="chat-header-actions">
                <button class="chat-rules-btn" onclick="$('#chat-rules-modal').modal('show')" title="Chat Rules">
                    <span class="material-symbols-outlined">info</span>
                </button>
                <button class="rain-admin-btn" onclick="window.rainSystem.showCreateRainModal()" title="Create Rain">
                    🌧️
                </button>
            </div>
        </div>
        <div id="chat-messages"></div>
        <div class="chat-input-container">
            <button type="button" id="emoji-picker-btn" class="emoji-picker-btn" title="Add emoji">
                <span class="emoji-icon">😀</span>
            </button>
            <textarea id="chat-input" placeholder="Type a message..." rows="1" maxlength="500"></textarea>
            <button type="button" id="chat-send-btn">Send</button>
        </div>
        
        <!-- Emoji Picker Popup -->
        <div id="emoji-picker-popup" class="emoji-picker-popup" style="display: none;">
            <div class="emoji-picker-header">
                <span class="emoji-picker-title">Pick an emoji</span>
                <button type="button" class="emoji-picker-close" onclick="closeEmojiPicker()">✕</button>
            </div>
            <div class="emoji-picker-categories">
                <button type="button" class="emoji-category-btn active" data-category="smileys">😀</button>
                <button type="button" class="emoji-category-btn" data-category="gestures">👍</button>
                <button type="button" class="emoji-category-btn" data-category="objects">⚽</button>
                <button type="button" class="emoji-category-btn" data-category="symbols">❤️</button>
            </div>
            <div class="emoji-picker-grid" id="emoji-picker-grid">
                <!-- Emojis will be dynamically loaded here -->
            </div>
        </div>
    </div>
</div>

<!--====== Chat Rules Modal ======-->
<div class="modal fade" id="chat-rules-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title secondary-font">CHAT RULES</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled">
                    <li class="mb-2">?? <strong>Be Respectful:</strong> No insults, hate speech, or harassment.</li>
                    <li class="mb-2">?? <strong>No Spam:</strong> Avoid repeating the same message or using excessive caps.</li>
                    <li class="mb-2">?? <strong>No Promotion:</strong> Do not share external links, referral codes, or advertise other services.</li>
                    <li class="mb-2">?? <strong>Language:</strong> Please use English or the primary local language.</li>
                    <li class="mb-2">?? <strong>Safety:</strong> Never share your password or personal contact details in chat.</li>
                    <li class="mb-2">?? <strong>Rain:</strong> Begging for Rain/Tips is discouraged and may result in a chat ban.</li>
                </ul>
                <div class="alert alert-warning py-2 small mt-3">
                    <i class="mdi mdi-alert"></i> Violating these rules may result in temporary or permanent chat restrictions.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.chat-rules-btn {
    background: transparent;
    border: none;
    color: #9ea2ae;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 0;
    transition: color 0.2s;
}
.chat-rules-btn:hover {
    color: #fff;
}
.chat-rules-btn .material-symbols-outlined {
    font-size: 20px;
}
</style>
<!--====== Chat Sidebar End ======-->
@endif
