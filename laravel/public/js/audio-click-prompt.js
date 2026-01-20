/**
 * Audio Click Prompt - Shows a user-friendly prompt to enable audio
 */

(function() {
    // Only show if audio handler exists
    if (!window.audioHandler) return;

    // Check if user has already interacted
    let promptShown = false;

    function showAudioPrompt() {
        if (promptShown || window.audioHandler.userInteracted) return;
        promptShown = true;

        // Create a subtle overlay
        const overlay = document.createElement('div');
        overlay.id = 'audio-enable-prompt';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            animation: fadeIn 0.3s ease-in;
        `;

        const promptBox = document.createElement('div');
        promptBox.style.cssText = `
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        `;

        promptBox.innerHTML = `
            <div style="font-size: 48px; margin-bottom: 15px;">🔊</div>
            <h3 style="margin: 0 0 10px 0; color: #333;">Enable Sound</h3>
            <p style="margin: 0 0 20px 0; color: #666;">Click anywhere to enable game audio</p>
            <button id="enable-audio-btn" style="
                background: #4CAF50;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                transition: background 0.3s;
            ">Enable Audio</button>
        `;

        overlay.appendChild(promptBox);
        document.body.appendChild(overlay);

        // Add hover effect
        const btn = promptBox.querySelector('#enable-audio-btn');
        btn.addEventListener('mouseenter', () => {
            btn.style.background = '#45a049';
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.background = '#4CAF50';
        });

        // Remove overlay on any click
        overlay.addEventListener('click', () => {
            overlay.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                overlay.remove();
            }, 300);
        });
    }

    // Add fade animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // Show prompt after a short delay if user hasn't interacted
    setTimeout(() => {
        if (!window.audioHandler.userInteracted) {
            showAudioPrompt();
        }
    }, 2000);
})();
