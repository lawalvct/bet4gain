<template>
    <div
        class="relative w-full aspect-[16/9] bg-gradient-to-b from-slate-900 to-slate-800 rounded-2xl overflow-hidden border border-surface-light-border dark:border-surface-dark-border"
    >
        <!-- Canvas -->
        <canvas ref="canvasRef" class="absolute inset-0 w-full h-full"></canvas>

        <!-- Multiplier Overlay -->
        <div
            class="absolute inset-0 flex items-center justify-center pointer-events-none"
        >
            <div v-if="status === 'waiting'" class="text-center">
                <p class="text-slate-400 text-sm mb-1">Next round in</p>
                <p class="text-4xl font-bold text-white tabular-nums">
                    {{ countdown }}s
                </p>
            </div>

            <div v-else-if="status === 'betting'" class="text-center">
                <p class="text-amber-400 text-sm mb-1 animate-pulse">
                    Place your bets!
                </p>
                <p class="text-4xl font-bold text-white tabular-nums">
                    {{ bettingCountdown }}s
                </p>
            </div>

            <div v-else-if="status === 'running'" class="text-center">
                <p
                    :class="multiplierClass"
                    class="text-6xl sm:text-7xl font-black tabular-nums multiplier-glow transition-colors duration-200"
                >
                    {{ currentMultiplier }}x
                </p>
            </div>

            <div v-else-if="status === 'crashed'" class="text-center">
                <p class="text-sm text-slate-400 mb-1">Crashed at</p>
                <p
                    class="text-6xl sm:text-7xl font-black text-game-red tabular-nums"
                >
                    {{ crashPoint }}x
                </p>
            </div>
        </div>

        <!-- Round Hash -->
        <div
            class="absolute bottom-2 left-2 right-2 flex items-center justify-between text-[10px] text-slate-500 font-mono"
        >
            <span>Round #{{ roundId }}</span>
            <span v-if="hash" class="truncate max-w-[200px]">{{ hash }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";

const props = defineProps({
    status: { type: String, default: "waiting" },
    currentMultiplier: { type: Number, default: 1.0 },
    crashPoint: { type: Number, default: null },
    countdown: { type: Number, default: 0 },
    bettingCountdown: { type: Number, default: 0 },
    roundId: { type: [Number, String], default: "" },
    hash: { type: String, default: "" },
});

const canvasRef = ref(null);

const multiplierClass = computed(() => {
    const m = props.currentMultiplier;
    if (m >= 10) return "text-game-purple";
    if (m >= 5) return "text-game-blue";
    if (m >= 2) return "text-game-green";
    return "text-white";
});

// Canvas drawing will be implemented in Phase 3
let animationFrame = null;
let ctx = null;

const initCanvas = () => {
    if (!canvasRef.value) return;
    const canvas = canvasRef.value;
    ctx = canvas.getContext("2d");

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * window.devicePixelRatio;
        canvas.height = rect.height * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    };

    resize();
    window.addEventListener("resize", resize);
};

onMounted(() => {
    initCanvas();
});

onBeforeUnmount(() => {
    if (animationFrame) cancelAnimationFrame(animationFrame);
});
</script>
