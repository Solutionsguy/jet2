/**
 * Emoji Picker for Chat System
 */

const emojiData = {
    smileys: [
        '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', 
        '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '😋', '😛', '😜', '🤪',
        '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏',
        '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕',
        '🤢', '🤮', '🤧', '🥵', '🥶', '😎', '🤓', '🧐', '😕', '😟', '🙁', '☹️',
        '😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨', '😰', '😥', '😢', '😭',
        '😱', '😖', '😣', '😞', '😓', '😩', '😫', '🥱', '😤', '😡', '😠', '🤬'
    ],
    gestures: [
        '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘',
        '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛',
        '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💪', '🦾', '🦿', '🦵',
        '🦶', '👂', '🦻', '👃', '🧠', '🫀', '🫁', '🦷', '🦴', '👀', '👁️', '👅',
        '👄', '💋', '🩸'
    ],
    objects: [
        '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓',
        '🏸', '🏒', '🏑', '🥍', '🏏', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊',
        '🥋', '🎽', '🛹', '🛼', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️',
        '🤼', '🤸', '🤺', '⛹️', '🤾', '🏌️', '🏇', '🧘', '🏊', '🤽', '🚣', '🧗',
        '🚴', '🚵', '🎪', '🎭', '🎨', '🎬', '🎤', '🎧', '🎼', '🎹', '🥁', '🎷',
        '🎺', '🎸', '🪕', '🎻', '🎲', '♟️', '🎯', '🎳', '🎮', '🎰', '🧩'
    ],
    symbols: [
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕',
        '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️',
        '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌',
        '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️',
        '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️',
        '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌',
        '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️', '🚷', '🚯', '🚳', '🚱',
        '🔞', '📵', '🚭', '❗', '❕', '❓', '❔', '‼️', '⁉️', '🔅', '🔆', '〽️',
        '⚠️', '🚸', '🔱', '⚜️', '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎',
        '🌐', '💠', 'Ⓜ️', '🌀', '💤', '🏧', '🚾', '♿', '🅿️', '🈳', '🈂️', '🛂',
        '🛃', '🛄', '🛅', '🚹', '🚺', '🚼', '⚧️', '🚻', '🚮', '🎦', '📶', '🈁'
    ]
};

let currentCategory = 'smileys';

// Initialize emoji picker
function initEmojiPicker() {
    const emojiBtn = document.getElementById('emoji-picker-btn');
    const emojiPopup = document.getElementById('emoji-picker-popup');
    
    if (!emojiBtn || !emojiPopup) return;
    
    // Toggle emoji picker on button click
    emojiBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const isVisible = emojiPopup.style.display === 'block';
        
        if (isVisible) {
            closeEmojiPicker();
        } else {
            openEmojiPicker();
        }
    });
    
    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!emojiPopup.contains(e.target) && !emojiBtn.contains(e.target)) {
            closeEmojiPicker();
        }
    });
    
    // Category buttons
    const categoryBtns = document.querySelectorAll('.emoji-category-btn');
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Load emojis for this category
            currentCategory = this.dataset.category;
            loadEmojis(currentCategory);
        });
    });
    
    // Load initial emojis
    loadEmojis(currentCategory);
    
    console.log('✅ Emoji picker initialized');
}

function openEmojiPicker() {
    const emojiPopup = document.getElementById('emoji-picker-popup');
    const emojiBtn = document.getElementById('emoji-picker-btn');
    
    if (!emojiPopup || !emojiBtn) return;
    
    emojiPopup.style.display = 'block';
    // Position is handled by CSS (bottom: 100% positions it above the input container)
}

function closeEmojiPicker() {
    const emojiPopup = document.getElementById('emoji-picker-popup');
    if (emojiPopup) {
        emojiPopup.style.display = 'none';
    }
}

function loadEmojis(category) {
    const grid = document.getElementById('emoji-picker-grid');
    if (!grid) return;
    
    const emojis = emojiData[category] || [];
    
    grid.innerHTML = '';
    
    emojis.forEach(emoji => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'emoji-item';
        btn.textContent = emoji;
        btn.title = emoji;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            insertEmoji(emoji);
        });
        grid.appendChild(btn);
    });
}

function insertEmoji(emoji) {
    const chatInput = document.getElementById('chat-input');
    if (!chatInput) return;
    
    // Get current cursor position
    const start = chatInput.selectionStart;
    const end = chatInput.selectionEnd;
    const text = chatInput.value;
    
    // Insert emoji at cursor position
    const newText = text.substring(0, start) + emoji + text.substring(end);
    chatInput.value = newText;
    
    // Move cursor after emoji
    const newPosition = start + emoji.length;
    chatInput.selectionStart = newPosition;
    chatInput.selectionEnd = newPosition;
    
    // Focus back on input
    chatInput.focus();
    
    // DON'T close picker - let user select multiple emojis
    // User can close manually by clicking X or clicking outside
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmojiPicker);
} else {
    initEmojiPicker();
}

// Make closeEmojiPicker global for inline onclick
window.closeEmojiPicker = closeEmojiPicker;
