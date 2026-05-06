/**
 * Professional Web Audio API Handler (Safe Bridge Version)
 * Prevents mobile notification bar hijacking.
 */
if (!window.AudioHandler) {
    var AudioHandler = (function() {
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const buffers = {};
        let backgroundSource = null;
        let backgroundGain = null;

        async function loadSound(name, url) {
            try {
                const response = await fetch(url);
                const arrayBuffer = await response.arrayBuffer();
                buffers[name] = await context.decodeAudioData(arrayBuffer);
            } catch (e) {
                console.warn(`🔈 Silent fallback for: ${name}`);
            }
        }

        function playSound(name) {
            if (!buffers[name] || context.state === 'suspended') return;
            const source = context.createBufferSource();
            source.buffer = buffers[name];
            source.connect(context.destination);
            source.start(0);
        }

        function playBackground(name) {
            if (!buffers[name]) return;
            if (backgroundSource) stopBackground();
            backgroundSource = context.createBufferSource();
            backgroundSource.buffer = buffers[name];
            backgroundSource.loop = true;
            backgroundGain = context.createGain();
            backgroundGain.gain.value = 0.4;
            backgroundSource.connect(backgroundGain);
            backgroundGain.connect(context.destination);
            backgroundSource.start(0);
        }

        function stopBackground() {
            if (backgroundSource) {
                try { backgroundSource.stop(); } catch(e) {}
                backgroundSource = null;
            }
        }

        function unlock() {
            if (context.state === 'suspended') context.resume();
        }

        // Pre-load
        const b = '/';
        loadSound('crash', b + 'plane-crash.mp3');
        loadSound('background', b + 'background.mp3');
        loadSound('start', b + 'game-start.mp3');
        loadSound('cashout', b + 'cashout.mp3');
        loadSound('cashout2', b + 'cashout_2.mp3');

        return { playSound, playBackground, stopBackground, unlock };
    })();

    ['click', 'touchstart'].forEach(v => document.addEventListener(v, () => AudioHandler.unlock(), {once:true}));
}
