/**
 * Audio Handler - Manages audio playback with proper user interaction handling
 * Prevents "NotAllowedError: play() failed because the user didn't interact with the document first"
 */

class AudioHandler {
    constructor() {
        this.userInteracted = false;
        this.audioElements = {};
        this.pendingPlay = [];
        this.init();
    }

    init() {
        // Listen for first user interaction
        const interactionEvents = ['click', 'touchstart', 'keydown'];
        
        const handleFirstInteraction = () => {
            this.userInteracted = true;
            
            // Play any pending audio
            this.playPendingAudio();
            
            // Remove listeners after first interaction
            interactionEvents.forEach(event => {
                document.removeEventListener(event, handleFirstInteraction);
            });
        };

        // Add listeners for all interaction types
        interactionEvents.forEach(event => {
            document.addEventListener(event, handleFirstInteraction, { once: true });
        });
    }

    /**
     * Register an audio element
     * @param {string} id - The ID of the audio element
     * @param {HTMLAudioElement} element - The audio element
     */
    register(id, element) {
        this.audioElements[id] = element;
    }

    /**
     * Safely play audio with user interaction check
     * @param {string|HTMLAudioElement} audio - Audio element ID or element itself
     * @returns {Promise}
     */
    async safePlay(audio) {
        const audioElement = typeof audio === 'string' ? 
            (this.audioElements[audio] || document.getElementById(audio)) : 
            audio;

        if (!audioElement) {
            console.warn('Audio element not found:', audio);
            return Promise.reject('Audio element not found');
        }

        // If user hasn't interacted yet, queue the audio
        if (!this.userInteracted) {
            console.log('Waiting for user interaction to play audio...');
            this.pendingPlay.push(audioElement);
            return Promise.resolve();
        }

        try {
            await audioElement.play();
            return Promise.resolve();
        } catch (error) {
            if (error.name === 'NotAllowedError') {
                console.log('Audio play blocked, waiting for user interaction');
                this.pendingPlay.push(audioElement);
            } else {
                console.error('Error playing audio:', error);
            }
            return Promise.reject(error);
        }
    }

    /**
     * Play all pending audio
     */
    async playPendingAudio() {
        const pending = [...this.pendingPlay];
        this.pendingPlay = [];

        for (const audio of pending) {
            try {
                await audio.play();
            } catch (error) {
                console.error('Error playing pending audio:', error);
            }
        }
    }

    /**
     * Pause audio safely
     * @param {string|HTMLAudioElement} audio - Audio element ID or element itself
     */
    safePause(audio) {
        const audioElement = typeof audio === 'string' ? 
            (this.audioElements[audio] || document.getElementById(audio)) : 
            audio;

        if (audioElement) {
            audioElement.pause();
        }
    }
}

// Create global instance
window.audioHandler = new AudioHandler();

// Helper function for backward compatibility
window.safePlayAudio = (audio) => window.audioHandler.safePlay(audio);
