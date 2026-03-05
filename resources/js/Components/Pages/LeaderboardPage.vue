<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark">
        <!-- Simple Header -->
        <header
            class="sticky top-0 z-50 bg-surface-light-card/80 dark:bg-surface-dark-card/80 backdrop-blur-xl border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <div
                class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between"
            >
                <a href="/" class="flex items-center gap-2 group">
                    <span
                        class="text-2xl transition-transform group-hover:scale-110"
                        >🎮</span
                    >
                    <span class="text-lg font-bold text-primary-500"
                        >Bet4Gain</span
                    >
                </a>
                <nav class="flex items-center gap-3 text-sm">
                    <a
                        href="/"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >Game</a
                    >
                    <span class="text-primary-500 font-semibold"
                        >Leaderboard</span
                    >
                    <a
                        href="/history"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >History</a
                    >
                    <a
                        href="/provably-fair"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >Fair</a
                    >
                </nav>
            </div>
        </header>

        <!-- Content -->
        <main class="max-w-5xl mx-auto px-4 py-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-white"
                    >
                        🏆 Leaderboard
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Top players ranked by profit
                    </p>
                </div>

                <!-- Period Tabs -->
                <div
                    class="flex gap-1 bg-surface-light dark:bg-surface-dark rounded-xl p-1"
                >
                    <button
                        v-for="p in periods"
                        :key="p.value"
                        @click="store.setPeriod(p.value)"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition',
                            store.activePeriod === p.value
                                ? 'bg-primary-500 text-white shadow-sm'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300',
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="store.loading" class="flex justify-center py-16">
                <svg
                    class="animate-spin w-8 h-8 text-primary-500"
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

            <!-- Leaderboard Table -->
            <div
                v-else-if="store.entries.length"
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden"
            >
                <!-- Table Header -->
                <div
                    class="grid grid-cols-12 gap-2 px-4 py-3 border-b border-surface-light-border dark:border-surface-dark-border text-xs font-semibold text-slate-500 uppercase tracking-wider"
                >
                    <div class="col-span-1 text-center">#</div>
                    <div class="col-span-4">Player</div>
                    <div class="col-span-2 text-right">Wagered</div>
                    <div class="col-span-2 text-right">Profit</div>
                    <div class="col-span-1 text-right hidden sm:block">
                        Win%
                    </div>
                    <div class="col-span-2 text-right">Best Multi</div>
                </div>

                <!-- Rows -->
                <div
                    v-for="entry in store.entries"
                    :key="entry.rank"
                    :class="[
                        'grid grid-cols-12 gap-2 px-4 py-3 items-center border-b border-surface-light-border/50 dark:border-surface-dark-border/50 last:border-0 transition hover:bg-surface-light dark:hover:bg-surface-dark',
                        entry.rank <= 3
                            ? 'bg-amber-50/30 dark:bg-amber-900/5'
                            : '',
                    ]"
                >
                    <!-- Rank -->
                    <div class="col-span-1 flex justify-center">
                        <span
                            v-if="entry.rank <= 3"
                            :class="[
                                'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
                                entry.rank === 1
                                    ? 'bg-amber-500 text-white'
                                    : '',
                                entry.rank === 2
                                    ? 'bg-slate-400 text-white'
                                    : '',
                                entry.rank === 3
                                    ? 'bg-amber-700 text-white'
                                    : '',
                            ]"
                        >
                            {{ entry.rank }}
                        </span>
                        <span
                            v-else
                            class="text-sm text-slate-400 font-medium"
                            >{{ entry.rank }}</span
                        >
                    </div>

                    <!-- Player -->
                    <div class="col-span-4 flex items-center gap-2.5 min-w-0">
                        <img
                            :src="
                                entry.avatar_url ||
                                defaultAvatar(entry.username)
                            "
                            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                        />
                        <span
                            class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate"
                        >
                            {{ entry.username }}
                        </span>
                    </div>

                    <!-- Wagered -->
                    <div
                        class="col-span-2 text-right text-sm text-slate-600 dark:text-slate-400 tabular-nums"
                    >
                        {{ formatCoins(entry.total_wagered) }}
                    </div>

                    <!-- Profit -->
                    <div
                        :class="[
                            'col-span-2 text-right text-sm font-bold tabular-nums',
                            entry.total_profit >= 0
                                ? 'text-game-green'
                                : 'text-game-red',
                        ]"
                    >
                        {{ entry.total_profit >= 0 ? "+" : ""
                        }}{{ formatCoins(entry.total_profit) }}
                    </div>

                    <!-- Win Rate -->
                    <div
                        class="col-span-1 text-right text-sm text-slate-500 tabular-nums hidden sm:block"
                    >
                        {{ entry.win_rate }}%
                    </div>

                    <!-- Best Multiplier -->
                    <div class="col-span-2 text-right">
                        <span
                            class="text-sm font-semibold text-purple-500 tabular-nums"
                        >
                            {{ formatMultiplier(entry.best_multiplier) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-16 text-center"
            >
                <span class="text-4xl mb-4 block">🏆</span>
                <h3
                    class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2"
                >
                    No data yet
                </h3>
                <p class="text-sm text-slate-400">
                    Play some rounds to see the leaderboard!
                </p>
            </div>
        </main>
    </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";
import { formatCoins, formatMultiplier } from "@/Utils/formatters";

const store = useLeaderboardStore();

const periods = [
    { value: "daily", label: "Today" },
    { value: "weekly", label: "Week" },
    { value: "monthly", label: "Month" },
    { value: "alltime", label: "All Time" },
];

const defaultAvatar = (username) =>
    `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "?")}&background=random&color=fff&size=64`;

onMounted(() => {
    store.fetchLeaderboard();
});
</script>
