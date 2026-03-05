<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark">
        <!-- Header -->
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
                    <a
                        href="/leaderboard"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >Leaderboard</a
                    >
                    <span class="text-primary-500 font-semibold">My Stats</span>
                </nav>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-6 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    📊 My Statistics
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Your personal performance breakdown
                </p>
            </div>

            <!-- Loading -->
            <div v-if="store.loadingStats" class="flex justify-center py-16">
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

            <template v-else-if="stats">
                <!-- Stats Overview Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <StatCard
                        label="Total Wagered"
                        :value="formatCoins(stats.total_wagered)"
                        icon="💰"
                    />
                    <StatCard
                        label="Total Won"
                        :value="formatCoins(stats.total_won)"
                        icon="🎉"
                        color="green"
                    />
                    <StatCard
                        label="Net Profit"
                        :value="
                            (stats.total_profit >= 0 ? '+' : '') +
                            formatCoins(stats.total_profit)
                        "
                        icon="📈"
                        :color="stats.total_profit >= 0 ? 'green' : 'red'"
                    />
                    <StatCard
                        label="Leaderboard Rank"
                        :value="'#' + stats.rank"
                        icon="🏆"
                        color="amber"
                    />
                </div>

                <!-- Secondary Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <StatCard
                        label="Games Played"
                        :value="stats.total_games.toLocaleString()"
                        icon="🎮"
                        size="sm"
                    />
                    <StatCard
                        label="Wins"
                        :value="stats.win_count.toLocaleString()"
                        icon="✅"
                        size="sm"
                        color="green"
                    />
                    <StatCard
                        label="Win Rate"
                        :value="stats.win_rate + '%'"
                        icon="📊"
                        size="sm"
                    />
                    <StatCard
                        label="Best Multiplier"
                        :value="formatMultiplier(stats.best_multiplier)"
                        icon="🚀"
                        size="sm"
                        color="purple"
                    />
                    <StatCard
                        label="Favorite Bet"
                        :value="formatCoins(stats.favorite_bet)"
                        icon="⭐"
                        size="sm"
                    />
                </div>

                <!-- Daily Profit Chart -->
                <div
                    class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-4"
                >
                    <h3
                        class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4"
                    >
                        📈 Last 30 Days — Daily Profit
                    </h3>

                    <div
                        v-if="stats.daily_stats.length"
                        class="flex items-end gap-1 h-32"
                    >
                        <div
                            v-for="day in stats.daily_stats"
                            :key="day.date"
                            class="flex-1 flex flex-col items-center justify-end gap-1 group relative"
                        >
                            <!-- Bar -->
                            <div
                                :class="[
                                    'w-full rounded-t-sm transition min-h-[2px]',
                                    day.profit >= 0
                                        ? 'bg-game-green'
                                        : 'bg-game-red',
                                ]"
                                :style="{
                                    height: barHeight(day.profit) + 'px',
                                }"
                            />
                            <!-- Tooltip -->
                            <div
                                class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-surface-dark-card dark:bg-surface-light-card text-white dark:text-slate-900 text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10"
                            >
                                {{ formatDateShort(day.date) }}:
                                {{ day.profit >= 0 ? "+" : ""
                                }}{{ formatCoins(day.profit) }} ({{
                                    day.games
                                }}
                                games)
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-sm text-slate-400">
                        No recent activity to chart
                    </div>
                </div>

                <!-- Bet History -->
                <div
                    class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-surface-light-border dark:border-surface-dark-border"
                    >
                        <h3
                            class="text-sm font-semibold text-slate-700 dark:text-slate-300"
                        >
                            🎯 Bet History
                        </h3>
                        <span class="text-xs text-slate-400"
                            >{{ store.myBetsPagination.total }} total bets</span
                        >
                    </div>

                    <!-- Loading Bets -->
                    <div
                        v-if="store.loadingBets"
                        class="flex justify-center py-8"
                    >
                        <svg
                            class="animate-spin w-6 h-6 text-primary-500"
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
                        <!-- Table Header -->
                        <div
                            class="grid grid-cols-12 gap-2 px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-surface-light dark:bg-surface-dark"
                        >
                            <div class="col-span-2">Round</div>
                            <div class="col-span-2">Crash</div>
                            <div class="col-span-2 text-right">Bet</div>
                            <div class="col-span-2 text-right">Cash Out</div>
                            <div class="col-span-2 text-right">Profit</div>
                            <div class="col-span-2 text-right">Status</div>
                        </div>

                        <!-- Rows -->
                        <div
                            v-for="bet in store.myBets"
                            :key="bet.id"
                            class="grid grid-cols-12 gap-2 px-4 py-2.5 items-center border-b border-surface-light-border/50 dark:border-surface-dark-border/50 last:border-0 text-sm"
                        >
                            <div class="col-span-2">
                                <a
                                    :href="
                                        '/provably-fair?round=' + bet.round_id
                                    "
                                    class="text-primary-500 hover:text-primary-600 font-medium transition"
                                >
                                    #{{ bet.round_id }}
                                </a>
                            </div>
                            <div class="col-span-2">
                                <span
                                    :class="getCrashPointColor(bet.crash_point)"
                                    class="font-semibold tabular-nums"
                                >
                                    {{
                                        bet.crash_point
                                            ? formatMultiplier(bet.crash_point)
                                            : "—"
                                    }}
                                </span>
                            </div>
                            <div
                                class="col-span-2 text-right tabular-nums text-slate-600 dark:text-slate-400"
                            >
                                {{ formatCoins(bet.amount) }}
                                <span
                                    v-if="bet.currency === 'DEMO'"
                                    class="text-[10px] text-slate-400 ml-0.5"
                                    >D</span
                                >
                            </div>
                            <div
                                class="col-span-2 text-right tabular-nums text-slate-600 dark:text-slate-400"
                            >
                                {{
                                    bet.cashed_out_at
                                        ? formatMultiplier(bet.cashed_out_at)
                                        : "—"
                                }}
                            </div>
                            <div
                                :class="[
                                    'col-span-2 text-right font-semibold tabular-nums',
                                    bet.profit !== null && bet.profit >= 0
                                        ? 'text-game-green'
                                        : 'text-game-red',
                                ]"
                            >
                                {{
                                    bet.profit !== null
                                        ? (bet.profit >= 0 ? "+" : "") +
                                          formatCoins(bet.profit)
                                        : "—"
                                }}
                            </div>
                            <div class="col-span-2 text-right">
                                <span
                                    :class="[
                                        'text-xs px-2 py-0.5 rounded-full font-medium',
                                        bet.status === 'won'
                                            ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                                            : '',
                                        bet.status === 'lost'
                                            ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
                                            : '',
                                        bet.status === 'active'
                                            ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                                            : '',
                                        bet.status === 'pending'
                                            ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400'
                                            : '',
                                        bet.status === 'cancelled'
                                            ? 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                                            : '',
                                    ]"
                                >
                                    {{
                                        bet.status.charAt(0).toUpperCase() +
                                        bet.status.slice(1)
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Empty -->
                        <div
                            v-if="!store.myBets.length"
                            class="px-4 py-8 text-center text-sm text-slate-400"
                        >
                            No bets placed yet
                        </div>
                    </template>

                    <!-- Pagination -->
                    <div
                        v-if="store.myBetsPagination.last_page > 1"
                        class="flex items-center justify-center gap-2 px-4 py-3 border-t border-surface-light-border dark:border-surface-dark-border"
                    >
                        <button
                            @click="
                                store.fetchMyBets(
                                    store.myBetsPagination.current_page - 1,
                                )
                            "
                            :disabled="store.myBetsPagination.current_page <= 1"
                            class="px-3 py-1.5 text-sm rounded-lg border border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:bg-surface-light dark:hover:bg-surface-dark transition disabled:opacity-30"
                        >
                            ← Prev
                        </button>
                        <span class="text-sm text-slate-500">
                            {{ store.myBetsPagination.current_page }} /
                            {{ store.myBetsPagination.last_page }}
                        </span>
                        <button
                            @click="
                                store.fetchMyBets(
                                    store.myBetsPagination.current_page + 1,
                                )
                            "
                            :disabled="
                                store.myBetsPagination.current_page >=
                                store.myBetsPagination.last_page
                            "
                            class="px-3 py-1.5 text-sm rounded-lg border border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:bg-surface-light dark:hover:bg-surface-dark transition disabled:opacity-30"
                        >
                            Next →
                        </button>
                    </div>
                </div>
            </template>

            <!-- Not logged in -->
            <div
                v-else
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-16 text-center"
            >
                <span class="text-4xl mb-4 block">🔒</span>
                <h3
                    class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2"
                >
                    Login Required
                </h3>
                <p class="text-sm text-slate-400 mb-4">
                    Sign in to view your personal statistics
                </p>
                <a
                    href="/login"
                    class="inline-block px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-xl transition"
                >
                    Sign In
                </a>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";
import {
    formatCoins,
    formatMultiplier,
    getCrashPointColor,
} from "@/Utils/formatters";
import StatCard from "@/Components/Stats/StatCard.vue";

const store = useLeaderboardStore();

const stats = computed(() => store.personalStats);

const barHeight = (profit) => {
    if (!stats.value?.daily_stats?.length) return 2;
    const maxAbs = Math.max(
        ...stats.value.daily_stats.map((d) => Math.abs(d.profit)),
        1,
    );
    return Math.max(2, (Math.abs(profit) / maxAbs) * 100);
};

const formatDateShort = (date) => {
    const d = new Date(date);
    return d.toLocaleDateString("en", { month: "short", day: "numeric" });
};

onMounted(async () => {
    await store.fetchPersonalStats();
    store.fetchMyBets();
});
</script>
