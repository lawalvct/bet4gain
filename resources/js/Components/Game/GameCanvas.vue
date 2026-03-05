<template>
    <div
        ref="wrapperRef"
        class="relative w-full aspect-[16/9] overflow-hidden rounded-2xl border border-surface-light-border dark:border-surface-dark-border"
        :class="{ 'canvas-shake': isShaking }"
    >
        <!-- Background canvas (starfield) -->
        <canvas
            ref="bgCanvasRef"
            class="absolute inset-0 w-full h-full"
        ></canvas>
        <!-- Main game canvas -->
        <canvas ref="canvasRef" class="absolute inset-0 w-full h-full"></canvas>

        <!-- Red flash overlay -->
        <Transition name="crash-flash">
            <div
                v-if="showCrashOverlay"
                class="absolute inset-0 bg-game-red/30 pointer-events-none rounded-2xl"
            ></div>
        </Transition>

        <!-- Multiplier Overlay -->
        <div
            class="absolute inset-0 flex items-center justify-center pointer-events-none select-none z-10"
        >
            <div v-if="status === 'waiting'" class="text-center">
                <p class="text-slate-400 text-sm mb-1 tracking-wide uppercase">
                    Next round in
                </p>
                <p
                    class="text-5xl font-black text-white tabular-nums drop-shadow-lg"
                >
                    {{ countdown }}s
                </p>
            </div>
            <div v-else-if="status === 'betting'" class="text-center">
                <p
                    class="text-amber-400 text-sm mb-1 animate-pulse tracking-wide uppercase"
                >
                    Place your bets!
                </p>
                <p
                    class="text-5xl font-black text-white tabular-nums drop-shadow-lg"
                >
                    {{ bettingCountdown }}s
                </p>
            </div>
            <div v-else-if="status === 'running'" class="text-center">
                <p
                    :class="multiplierColorClass"
                    class="text-6xl sm:text-7xl font-black tabular-nums drop-shadow-[0_0_24px_currentColor] transition-colors duration-300"
                >
                    {{ displayMultiplier }}x
                </p>
            </div>
            <div
                v-else-if="status === 'crashed'"
                class="text-center animate-bounce-once"
            >
                <p class="text-sm text-slate-400 mb-1 tracking-wide uppercase">
                    Crashed at
                </p>
                <p
                    class="text-6xl sm:text-7xl font-black text-game-red tabular-nums drop-shadow-[0_0_24px_#ef4444]"
                >
                    {{ crashPoint }}x
                </p>
            </div>
        </div>

        <!-- Round info footer -->
        <div
            class="absolute bottom-2 left-3 right-3 flex items-center justify-between text-[10px] text-slate-500 font-mono z-10 pointer-events-none"
        >
            <span>#{{ roundId }}</span>
            <span v-if="hash" class="truncate max-w-[220px] opacity-60">{{
                hash
            }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";

//  Props
const props = defineProps({
    status: { type: String, default: "waiting" },
    currentMultiplier: { type: Number, default: 1.0 },
    crashPoint: { type: [Number, null], default: null },
    countdown: { type: Number, default: 0 },
    bettingCountdown: { type: Number, default: 0 },
    roundId: { type: [Number, String], default: "" },
    hash: { type: String, default: "" },
});

//  Canvas refs
const wrapperRef = ref(null);
const bgCanvasRef = ref(null);
const canvasRef = ref(null);

//  Internal state
let bgCtx = null;
let ctx = null;
let W = 0; // logical CSS width
let H = 0; // logical CSS height

let bgRaf = null;
let gameRaf = null;

let roundStartTime = 0;
const renderMultiplier = ref(1.0);

const showCrashOverlay = ref(false);
const isShaking = ref(false);

//  Computed
const multiplierColorClass = computed(() => {
    const m = renderMultiplier.value;
    if (m >= 10) return "text-game-purple";
    if (m >= 5) return "text-game-blue";
    if (m >= 2) return "text-game-green";
    return "text-white";
});

const displayMultiplier = computed(() => renderMultiplier.value.toFixed(2));

//  Physics / math (mirrors backend)
const multiplierAtMs = (ms) => Math.max(1.0, Math.exp((0.06 * ms) / 1000));
const msToCrash = (cp) => (Math.log(Math.max(cp, 1.01)) / 0.06) * 1000;

//  Starfield
const STAR_COUNT = 120;
const stars = [];

const initStars = () => {
    stars.length = 0;
    for (let i = 0; i < STAR_COUNT; i++) {
        stars.push({
            x: Math.random() * W,
            y: Math.random() * H,
            r: Math.random() * 1.4 + 0.3,
            spd: Math.random() * 0.15 + 0.05,
            alpha: Math.random() * 0.7 + 0.2,
        });
    }
};

const drawBg = () => {
    if (!bgCtx || W === 0) return;
    bgCtx.clearRect(0, 0, W, H);

    const grad = bgCtx.createLinearGradient(0, 0, 0, H);
    grad.addColorStop(0, "#0b0f1f");
    grad.addColorStop(1, "#111827");
    bgCtx.fillStyle = grad;
    bgCtx.fillRect(0, 0, W, H);

    const scroll =
        props.status === "running"
            ? ((performance.now() - roundStartTime) * 0.03) % H
            : 0;

    for (const s of stars) {
        const sy = (((s.y + scroll * s.spd) % H) + H) % H;
        bgCtx.beginPath();
        bgCtx.arc(s.x, sy, s.r, 0, Math.PI * 2);
        bgCtx.fillStyle = `rgba(255,255,255,${s.alpha})`;
        bgCtx.fill();
    }
};

//  Colour helpers
const mToColor = (m, alpha = 1) => {
    if (m >= 10) return `rgba(168,85,247,${alpha})`;
    if (m >= 5) return `rgba(59,130,246,${alpha})`;
    if (m >= 2) return `rgba(34,197,94,${alpha})`;
    return `rgba(255,255,255,${alpha})`;
};

//  Curve drawing
const PAD_X = 48;
const PAD_Y = 36;

const timeToX = (ms, maxMs) =>
    PAD_X + (maxMs > 0 ? ms / maxMs : 0) * (W - PAD_X * 2);

const multiplierToY = (m) => {
    const logM = Math.log(Math.max(m, 1));
    const logMax = Math.log(20);
    const frac = Math.min(logM / logMax, 1);
    return H - PAD_Y - frac * (H - PAD_Y * 2);
};

const drawCurve = (currentMs, maxMs) => {
    if (!ctx || W === 0) return;
    ctx.clearRect(0, 0, W, H);

    // Multiplier labels
    ctx.font = "10px monospace";
    [2, 3, 5, 10].forEach((m) => {
        const y = multiplierToY(m);
        ctx.strokeStyle = "rgba(255,255,255,0.06)";
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(PAD_X / 2, y);
        ctx.lineTo(W - PAD_X / 2, y);
        ctx.stroke();
        ctx.fillStyle = "rgba(255,255,255,0.2)";
        ctx.fillText(`${m}x`, 2, y + 4);
    });

    if (currentMs <= 0) return;

    const steps = 120;
    const stepMs = currentMs / steps;
    const tipM = multiplierAtMs(currentMs);
    const tipX = timeToX(currentMs, maxMs);
    const tipY = multiplierToY(tipM);

    // Build path
    const buildPath = () => {
        ctx.beginPath();
        for (let i = 0; i <= steps; i++) {
            const ms = i * stepMs;
            const x = timeToX(ms, maxMs);
            const y = multiplierToY(multiplierAtMs(ms));
            i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        }
    };

    // Glow
    buildPath();
    ctx.strokeStyle = mToColor(tipM, 0.2);
    ctx.lineWidth = 14;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
    ctx.stroke();

    // Line
    buildPath();
    ctx.strokeStyle = mToColor(tipM, 0.9);
    ctx.lineWidth = 3;
    ctx.stroke();

    // Fill
    ctx.lineTo(tipX, H - PAD_Y);
    ctx.lineTo(timeToX(0, maxMs), H - PAD_Y);
    ctx.closePath();
    const fill = ctx.createLinearGradient(0, tipY, 0, H);
    fill.addColorStop(0, mToColor(tipM, 0.18));
    fill.addColorStop(1, mToColor(tipM, 0.0));
    ctx.fillStyle = fill;
    ctx.fill();

    drawRocket(tipX, tipY, tipM);
};

const drawRocket = (x, y, m) => {
    ctx.save();
    ctx.translate(x, y);
    const s = 1 + Math.min(m / 20, 0.9);

    // Halo
    ctx.beginPath();
    ctx.arc(0, 0, 14 * s, 0, Math.PI * 2);
    ctx.fillStyle = mToColor(m, 0.12);
    ctx.fill();

    // Body
    ctx.shadowColor = mToColor(m, 0.9);
    ctx.shadowBlur = 14;
    ctx.beginPath();
    ctx.arc(0, 0, 7 * s, 0, Math.PI * 2);
    ctx.fillStyle = mToColor(m, 1);
    ctx.fill();
    ctx.shadowBlur = 0;

    // Exhaust
    ctx.beginPath();
    ctx.moveTo(-4 * s, 5 * s);
    ctx.lineTo(0, 18 * s);
    ctx.lineTo(4 * s, 5 * s);
    const ex = ctx.createLinearGradient(0, 5 * s, 0, 18 * s);
    ex.addColorStop(0, mToColor(m, 0.85));
    ex.addColorStop(1, "rgba(255,140,0,0)");
    ctx.fillStyle = ex;
    ctx.fill();

    ctx.restore();
};

const drawCrashed = () => {
    if (!ctx) return;
    const cp = props.crashPoint ?? 1;
    const maxMs = msToCrash(cp) || 5000;
    drawCurve(maxMs, maxMs);

    const tipX = timeToX(maxMs, maxMs);
    const tipY = multiplierToY(cp);
    ctx.save();
    ctx.translate(tipX, tipY);
    ctx.strokeStyle = "#ef4444";
    ctx.lineWidth = 3;
    ctx.lineCap = "round";
    const sz = 10;
    ctx.beginPath();
    ctx.moveTo(-sz, -sz);
    ctx.lineTo(sz, sz);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(sz, -sz);
    ctx.lineTo(-sz, sz);
    ctx.stroke();
    ctx.restore();
};

//  RAF loops
const startBgLoop = () => {
    const tick = () => {
        drawBg();
        bgRaf = requestAnimationFrame(tick);
    };
    bgRaf = requestAnimationFrame(tick);
};

const startGameLoop = () => {
    const tick = () => {
        if (props.status === "running") {
            const elapsedMs = performance.now() - roundStartTime;
            const serverM = props.currentMultiplier;
            renderMultiplier.value += (serverM - renderMultiplier.value) * 0.3;

            const windowMs = Math.max(elapsedMs * 1.25, 10000);
            drawCurve(elapsedMs, windowMs);
        }
        gameRaf = requestAnimationFrame(tick);
    };
    gameRaf = requestAnimationFrame(tick);
};

//  Canvas resize
const resize = () => {
    const dpr = window.devicePixelRatio || 1;
    for (const el of [bgCanvasRef.value, canvasRef.value]) {
        if (!el) continue;
        const rect = el.getBoundingClientRect();
        el.width = rect.width * dpr;
        el.height = rect.height * dpr;
        const c = el.getContext("2d");
        c.scale(dpr, dpr);
    }
    const rect = bgCanvasRef.value?.getBoundingClientRect();
    W = rect?.width ?? 400;
    H = rect?.height ?? 225;
    bgCtx = bgCanvasRef.value?.getContext("2d") ?? null;
    ctx = canvasRef.value?.getContext("2d") ?? null;
    initStars();
};

//  Watch status
watch(
    () => props.status,
    (val) => {
        if (val === "running") {
            roundStartTime = performance.now();
            renderMultiplier.value = 1.0;
            showCrashOverlay.value = false;
        }
        if (val === "crashed") {
            renderMultiplier.value = props.crashPoint ?? 1;
            showCrashOverlay.value = true;
            isShaking.value = true;
            drawCrashed();
            setTimeout(() => {
                showCrashOverlay.value = false;
            }, 900);
            setTimeout(() => {
                isShaking.value = false;
            }, 600);
        }
    },
);

//  Lifecycle
onMounted(() => {
    resize();
    window.addEventListener("resize", resize);
    startBgLoop();
    startGameLoop();
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", resize);
    cancelAnimationFrame(bgRaf);
    cancelAnimationFrame(gameRaf);
});
</script>

<style scoped>
.canvas-shake {
    animation: shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}
@keyframes shake {
    0%,
    100% {
        transform: translate(0, 0) rotate(0deg);
    }
    15% {
        transform: translate(-4px, -2px) rotate(-1deg);
    }
    30% {
        transform: translate(4px, 2px) rotate(1deg);
    }
    45% {
        transform: translate(-3px, 3px) rotate(-0.5deg);
    }
    60% {
        transform: translate(3px, -3px) rotate(0.5deg);
    }
    75% {
        transform: translate(-2px, 1px) rotate(-1deg);
    }
    90% {
        transform: translate(2px, -1px) rotate(1deg);
    }
}
.crash-flash-enter-active {
    transition: opacity 0.1s ease;
}
.crash-flash-leave-active {
    transition: opacity 0.8s ease;
}
.crash-flash-enter-from,
.crash-flash-leave-to {
    opacity: 0;
}
@keyframes bounceOnce {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    60% {
        transform: scale(1.18);
    }
    80% {
        transform: scale(0.95);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-bounce-once {
    animation: bounceOnce 0.5s ease forwards;
}
</style>
