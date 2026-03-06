<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border"
    >
        <!-- Header -->
        <div
            class="flex items-center px-3 py-2 lg:py-1.5 border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >
                History
            </h3>
        </div>

        <!-- Crash Points -->
        <div
            class="px-3 py-1.5 lg:py-1 flex flex-wrap gap-1.5 max-h-20 lg:max-h-12 overflow-y-auto scrollbar-thin"
        >
            <span
                v-for="(point, index) in visibleHistory"
                :key="index"
                :class="[
                    'px-2 py-0.5 rounded-full text-xs font-bold tabular-nums cursor-pointer hover:scale-110 transition-transform',
                    crashPillClass(point),
                ]"
                :title="`Round crashed at ${point}x`"
            >
                {{ point }}x
            </span>
            <span
                v-if="!visibleHistory.length"
                class="text-xs text-slate-400 py-1"
                >No history yet</span
            >
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    history: { type: Array, default: () => [] },
});

const visibleHistory = computed(() => props.history.slice(0, 8));

const crashPillClass = (point) => {
    if (point >= 10) return "crash-pill-purple";
    if (point >= 5) return "crash-pill-blue";
    if (point >= 2) return "crash-pill-green";
    return "crash-pill-red";
};
</script>
