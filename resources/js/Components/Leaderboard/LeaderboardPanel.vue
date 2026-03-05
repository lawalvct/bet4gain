<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-4 py-2.5 border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >
                🏆 Leaderboard
            </h3>
            <div class="flex gap-1">
                <button
                    v-for="p in periods"
                    :key="p.value"
                    @click="store.setPeriod(p.value)"
                    :class="[
                        'px-2 py-0.5 text-[10px] font-medium rounded-md transition',
                        store.activePeriod === p.value
                            ? 'bg-primary-500/10 text-primary-500'
                            : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300',
                    ]"
                >
                    {{ p.label }}
                </button>
            </div>
        </div>

        <!-- Leaders List -->
        <div
            class="divide-y divide-surface-light-border dark:divide-surface-dark-border max-h-64 overflow-y-auto scrollbar-thin"
        >
            <!-- Loading -->
            <div v-if="store.loading" class="flex justify-center py-6">
                <svg
                    class="animate-spin w-5 h-5 text-primary-500"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    />
                </svg>
            </div>

            <template v-else>
                <div
                    v-for="(entry, index) in store.entries"
                    :key="entry.rank || index"
                    class="flex items-center gap-3 px-4 py-2"
                >
                    <!-- Rank -->
                    <span
                        :class="[
                            'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0',
                            index === 0
                                ? 'bg-amber-500 text-white'
                                : index === 1
                                  ? 'bg-slate-400 text-white'
                                  : index === 2
                                    ? 'bg-amber-700 text-white'
                                    : 'bg-surface-light dark:bg-surface-dark text-slate-400',
                        ]"
                    >
                        {{ index + 1 }}
                    </span>

                    <!-- Avatar -->
                    <img
                        :src="entry.avatar_url || defaultAvatar(entry.username)"
                        class="w-6 h-6 rounded-full object-cover flex-shrink-0"
                    />

                    <!-- Username -->
                    <span
                        class="text-sm text-slate-700 dark:text-slate-300 truncate flex-1 min-w-0"
                    >
                        {{ entry.username || "Unknown" }}
                    </span>

                    <!-- Profit -->
                    <span
                        :class="[
                            'text-xs font-bold tabular-nums flex-shrink-0',
                            entry.total_profit >= 0
                                ? 'text-game-green'
                                : 'text-game-red',
                        ]"
                    >
                        {{ entry.total_profit >= 0 ? "+" : ""
                        }}{{ formatCoins(entry.total_profit) }}
                    </span>
                </div>

                <div
                    v-if="!store.entries.length"
                    class="px-4 py-8 text-center text-sm text-slate-400"
                >
                    No leaderboard data yet
                </div>
            </template>

            <!-- View All -->
            <div
                class="px-4 py-2 text-center border-t border-surface-light-border dark:border-surface-dark-border"
            >
                <a
                    href="/leaderboard"
                    class="text-xs text-primary-500 hover:text-primary-600 font-medium transition"
                >
                    View Full Leaderboard →
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";
import { formatCoins } from "@/Utils/formatters";

const store = useLeaderboardStore();

const periods = [
    { value: "daily", label: "Day" },
    { value: "weekly", label: "Week" },
    { value: "monthly", label: "Month" },
    { value: "alltime", label: "All" },
];

const defaultAvatar = (username) =>
    `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "?")}&background=random&color=fff&size=32`;

onMounted(() => {
    store.fetchLeaderboard();
});
</script>
