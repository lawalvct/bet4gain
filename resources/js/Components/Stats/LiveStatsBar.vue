<template>
    <div
        class="flex items-center justify-between gap-3 px-4 py-2 bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border text-xs"
    >
        <div class="flex items-center gap-1.5">
            <span class="text-slate-400">💰 Today:</span>
            <span
                class="font-semibold text-slate-700 dark:text-slate-300 tabular-nums"
            >
                {{ formatCompact(stats.total_wagered_today) }}
            </span>
        </div>

        <div
            class="w-px h-3 bg-surface-light-border dark:bg-surface-dark-border"
        />

        <div class="flex items-center gap-1.5">
            <span class="text-slate-400">🏆 Big Win:</span>
            <span class="font-semibold text-game-green tabular-nums">
                {{ formatCompact(stats.biggest_win_today) }}
            </span>
        </div>

        <div
            class="w-px h-3 bg-surface-light-border dark:bg-surface-dark-border hidden sm:block"
        />

        <div class="items-center gap-1.5 hidden sm:flex">
            <span class="text-slate-400">👥 Players:</span>
            <span
                class="font-semibold text-slate-700 dark:text-slate-300 tabular-nums"
            >
                {{ stats.unique_players }}
            </span>
        </div>

        <div
            class="w-px h-3 bg-surface-light-border dark:bg-surface-dark-border hidden sm:block"
        />

        <div class="items-center gap-1.5 hidden sm:flex">
            <span class="text-slate-400">🎯 Bets:</span>
            <span
                class="font-semibold text-slate-700 dark:text-slate-300 tabular-nums"
            >
                {{ stats.total_bets_today.toLocaleString() }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from "vue";
import { storeToRefs } from "pinia";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";

const store = useLeaderboardStore();
const { liveStats: stats } = storeToRefs(store);

let refreshInterval = null;

const formatCompact = (value) => {
    if (value >= 1000000) return (value / 1000000).toFixed(1) + "M";
    if (value >= 1000) return (value / 1000).toFixed(1) + "K";
    return Number(value).toFixed(0);
};

onMounted(() => {
    store.fetchLiveStats();
    // Refresh every 30 seconds
    refreshInterval = setInterval(() => store.fetchLiveStats(), 30000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>
