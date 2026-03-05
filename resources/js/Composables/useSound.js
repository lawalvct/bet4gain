import { ref } from "vue";
import { SOUNDS } from "@/Utils/constants";

const soundEnabled = ref(true);
const volume = ref(0.5);
const audioCache = new Map();
let initialized = false;

/**
 * Sound system composable for game audio.
 * Manages sound effects with caching, volume control,
 * and localStorage persistence.
 */
export function useSound() {
    if (!initialized) {
        const saved = localStorage.getItem("bet4gain_sound");
        if (saved !== null) soundEnabled.value = saved === "1";

        const savedVol = localStorage.getItem("bet4gain_volume");
        if (savedVol !== null) volume.value = parseFloat(savedVol);

        initialized = true;
    }

    /**
     * Pre-load all sound files into cache for instant playback.
     */
    function preload() {
        Object.values(SOUNDS).forEach((path) => {
            if (!audioCache.has(path)) {
                const audio = new Audio(path);
                audio.preload = "auto";
                audio.volume = volume.value;
                audioCache.set(path, audio);
            }
        });
    }

    /**
     * Play a sound effect by its constant path.
     * Clones the audio node so overlapping plays work.
     */
    function play(soundPath) {
        if (!soundEnabled.value) return;

        try {
            let source = audioCache.get(soundPath);
            if (!source) {
                source = new Audio(soundPath);
                source.preload = "auto";
                audioCache.set(soundPath, source);
            }

            // Clone so we can play overlapping instances
            const clone = source.cloneNode();
            clone.volume = volume.value;
            clone.play().catch(() => {
                // Browser may block autoplay — silently ignore
            });
        } catch {
            // Sound file missing or other error — non-critical
        }
    }

    // Convenience methods matching SOUNDS constants
    function betPlaced() {
        play(SOUNDS.BET_PLACED);
    }
    function cashout() {
        play(SOUNDS.CASHOUT);
    }
    function crash() {
        play(SOUNDS.CRASH);
    }
    function tick() {
        play(SOUNDS.TICK);
    }
    function chat() {
        play(SOUNDS.CHAT);
    }
    function win() {
        play(SOUNDS.WIN);
    }

    /**
     * Toggle sound on/off.
     */
    function toggle() {
        soundEnabled.value = !soundEnabled.value;
        localStorage.setItem("bet4gain_sound", soundEnabled.value ? "1" : "0");
    }

    /**
     * Set volume (0.0 – 1.0).
     */
    function setVolume(val) {
        volume.value = Math.max(0, Math.min(1, val));
        localStorage.setItem("bet4gain_volume", volume.value.toString());

        // Update cached audio nodes
        audioCache.forEach((audio) => {
            audio.volume = volume.value;
        });
    }

    return {
        soundEnabled,
        volume,
        preload,
        play,
        toggle,
        setVolume,
        betPlaced,
        cashout,
        crash,
        tick,
        chat,
        win,
    };
}
