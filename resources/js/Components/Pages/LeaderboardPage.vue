<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark">
        <!-- Header -->
        <header
            class="sticky top-0 z-50 bg-surface-light-card/80 dark:bg-surface-dark-card/80 backdrop-blur-xl border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <div
                class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between"
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
                <nav class="flex items-center gap-4 text-sm">
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
        <main class="max-w-6xl mx-auto px-4 py-6 space-y-6">
            <!-- Page Header + Period Tabs -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
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

            <!-- Top 3 Podium Cards -->
            <div
                v-if="!store.loading && topThree.length"
                class="grid grid-cols-1 sm:grid-cols-3 gap-3"
            >
                <div
                    v-for="(entry, i) in topThreeSorted"
                    :key="entry.rank"
                    :class="[
                        'relative rounded-xl border p-4 text-center transition hover:shadow-lg',
                        i === 1
                            ? 'bg-linear-to-b from-amber-500/10 to-amber-500/5 border-amber-500/30 sm:order-first sm:-mt-2'
                            : i === 0
                              ? 'bg-surface-light-card dark:bg-surface-dark-card border-slate-300 dark:border-slate-600'
                              : 'bg-surface-light-card dark:bg-surface-dark-card border-surface-light-border dark:border-surface-dark-border',
                    ]"
                >
                    <!-- Medal -->
                    <div class="flex justify-center mb-2">
                        <span
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold',
                                entry.rank === 1
                                    ? 'bg-amber-500 text-white'
                                    : entry.rank === 2
                                      ? 'bg-slate-400 text-white'
                                      : 'bg-amber-700 text-white',
                            ]"
                        >
                            {{ entry.rank }}
                        </span>
                    </div>
                    <!-- Avatar -->
                    <img
                        :src="entry.avatar_url || defaultAvatar(entry.username)"
                        class="w-14 h-14 rounded-full object-cover mx-auto mb-2 ring-2 ring-offset-2 ring-offset-surface-light dark:ring-offset-surface-dark"
                        :class="
                            entry.rank === 1
                                ? 'ring-amber-500'
                                : entry.rank === 2
                                  ? 'ring-slate-400'
                                  : 'ring-amber-700'
                        "
                    />
                    <div
                        class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate"
                    >
                        {{ entry.username }}
                    </div>
                    <div
                        :class="[
                            'text-lg font-bold tabular-nums mt-1',
                            entry.total_profit >= 0
                                ? 'text-game-green'
                                : 'text-game-red',
                        ]"
                    >
                        {{ entry.total_profit >= 0 ? "+" : ""
                        }}{{ formatCoins(entry.total_profit) }}
                    </div>
                    <div
                        class="flex justify-center gap-3 mt-2 text-xs text-slate-400"
                    >
                        <span>{{ entry.total_games }} games</span>
                        <span>{{ entry.win_rate }}% win</span>
                    </div>
                </div>
            </div>

            <!-- Section Tabs: Rankings / My Stats / My Bets -->
            <div
                class="flex gap-1 bg-surface-light dark:bg-surface-dark rounded-xl p-1"
            >
                <button
                    v-for="t in sectionTabs"
                    :key="t.value"
                    @click="activeSection = t.value"
                    :class="[
                        'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition text-center',
                        activeSection === t.value
                            ? 'bg-surface-light-card dark:bg-surface-dark-card text-slate-900 dark:text-white shadow-sm'
                            : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300',
                    ]"
                >
                    {{ t.icon }} {{ t.label }}
                </button>
            </div>

            <!-- ════════════════ SECTION: Rankings Table ════════════════ -->
            <div v-if="activeSection === 'rankings'">
                <!-- Loading -->
                <div v-if="store.loading" class="flex justify-center py-16">
                    <Spinner />
                </div>

                <!-- Table -->
                <div
                    v-else-if="store.entries.length"
                    class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden"
                >
                    <!-- Header -->
                    <div
                        class="grid grid-cols-12 gap-2 px-4 py-3 border-b border-surface-light-border dark:border-surface-dark-border text-xs font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        <div class="col-span-1 text-center">#</div>
                        <div class="col-span-3 sm:col-span-4">Player</div>
                        <div class="col-span-2 text-right">Wagered</div>
                        <div class="col-span-2 text-right">Profit</div>
                        <div class="col-span-2 sm:col-span-1 text-right">
                            Win%
                        </div>
                        <div class="col-span-2 text-right hidden sm:block">
                            Best
                        </div>
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
                                        : entry.rank === 2
                                          ? 'bg-slate-400 text-white'
                                          : 'bg-amber-700 text-white',
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
                        <div
                            class="col-span-3 sm:col-span-4 flex items-center gap-2.5 min-w-0"
                        >
                            <img
                                :src="
                                    entry.avatar_url ||
                                    defaultAvatar(entry.username)
                                "
                                class="w-8 h-8 rounded-full object-cover shrink-0"
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
                            class="col-span-2 sm:col-span-1 text-right text-sm text-slate-500 tabular-nums"
                        >
                            {{ entry.win_rate }}%
                        </div>

                        <!-- Best Multiplier -->
                        <div class="col-span-2 text-right hidden sm:block">
                            <span
                                class="text-sm font-semibold text-purple-500 tabular-nums"
                            >
                                {{ formatMultiplier(entry.best_multiplier) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty -->
                <EmptyState
                    v-else
                    icon="🏆"
                    title="No data yet"
                    message="Play some rounds to see the leaderboard!"
                />
            </div>

            <!-- ════════════════ SECTION: My Stats ════════════════ -->
            <div v-if="activeSection === 'mystats'">
                <div
                    v-if="store.loadingStats"
                    class="flex justify-center py-16"
                >
                    <Spinner />
                </div>

                <div v-else-if="store.personalStats" class="space-y-4">
                    <!-- Stats Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <StatCard
                            label="Total Wagered"
                            :value="
                                formatCoins(store.personalStats.total_wagered)
                            "
                            icon="🪙"
                        />
                        <StatCard
                            label="Total Profit"
                            :value="
                                (store.personalStats.total_profit >= 0
                                    ? '+'
                                    : '') +
                                formatCoins(store.personalStats.total_profit)
                            "
                            icon="📈"
                            :color="
                                store.personalStats.total_profit >= 0
                                    ? 'text-game-green'
                                    : 'text-game-red'
                            "
                        />
                        <StatCard
                            label="Win Rate"
                            :value="store.personalStats.win_rate + '%'"
                            icon="🎯"
                        />
                        <StatCard
                            label="Games Played"
                            :value="String(store.personalStats.total_games)"
                            icon="🎮"
                        />
                        <StatCard
                            label="Biggest Win"
                            :value="
                                formatCoins(store.personalStats.biggest_win)
                            "
                            icon="💰"
                            color="text-amber-500"
                        />
                        <StatCard
                            label="Best Multiplier"
                            :value="
                                formatMultiplier(
                                    store.personalStats.best_multiplier,
                                )
                            "
                            icon="🚀"
                            color="text-purple-500"
                        />
                        <StatCard
                            label="Favorite Bet"
                            :value="
                                formatCoins(store.personalStats.favorite_bet)
                            "
                            icon="⭐"
                        />
                        <StatCard
                            label="Rank"
                            :value="'#' + store.personalStats.rank"
                            icon="🏅"
                            color="text-amber-500"
                        />
                    </div>

                    <!-- 30-Day Profit Chart (simple bar chart) -->
                    <div
                        v-if="store.personalStats.daily_stats?.length"
                        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-4"
                    >
                        <h3
                            class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3"
                        >
                            📊 30-Day Profit Trend
                        </h3>
                        <div
                            class="flex items-end gap-1 h-32 overflow-x-auto scrollbar-thin"
                        >
                            <div
                                v-for="day in store.personalStats.daily_stats"
                                :key="day.date"
                                class="flex-1 min-w-2 flex flex-col items-center justify-end group relative"
                            >
                                <div
                                    class="w-full rounded-t transition-all"
                                    :class="
                                        day.profit >= 0
                                            ? 'bg-game-green/60 group-hover:bg-game-green'
                                            : 'bg-game-red/60 group-hover:bg-game-red'
                                    "
                                    :style="{
                                        height:
                                            Math.max(
                                                4,
                                                Math.min(
                                                    100,
                                                    (Math.abs(day.profit) /
                                                        maxDailyProfit) *
                                                        100,
                                                ),
                                            ) + '%',
                                    }"
                                ></div>
                                <!-- Tooltip -->
                                <div
                                    class="absolute bottom-full mb-2 px-2 py-1 bg-slate-800 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10"
                                >
                                    {{ day.date }}:
                                    {{ day.profit >= 0 ? "+" : ""
                                    }}{{ formatCoins(day.profit) }} ({{
                                        day.wins
                                    }}/{{ day.games }} wins)
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex justify-between text-[10px] text-slate-400 mt-1"
                        >
                            <span>{{
                                store.personalStats.daily_stats[0]?.date
                            }}</span>
                            <span>{{
                                store.personalStats.daily_stats[
                                    store.personalStats.daily_stats.length - 1
                                ]?.date
                            }}</span>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    icon="📊"
                    title="No stats available"
                    message="Log in and play some rounds to see your stats!"
                />
            </div>

            <!-- ════════════════ SECTION: My Bets ════════════════ -->
            <div v-if="activeSection === 'mybets'">
                <div v-if="store.loadingBets" class="flex justify-center py-16">
                    <Spinner />
                </div>

                <div v-else-if="store.myBets.length">
                    <div
                        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden"
                    >
                        <!-- Header -->
                        <div
                            class="grid grid-cols-12 gap-2 px-4 py-3 border-b border-surface-light-border dark:border-surface-dark-border text-xs font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            <div class="col-span-2 sm:col-span-1">Round</div>
                            <div class="col-span-2">Crash</div>
                            <div class="col-span-2 text-right">Bet</div>
                            <div class="col-span-2 text-right">Cashout</div>
                            <div class="col-span-2 text-right">Profit</div>
                            <div class="col-span-2 sm:col-span-3 text-right">
                                Time
                            </div>
                        </div>

                        <!-- Rows -->
                        <div
                            v-for="bet in store.myBets"
                            :key="bet.id"
                            class="grid grid-cols-12 gap-2 px-4 py-2.5 items-center border-b border-surface-light-border/50 dark:border-surface-dark-border/50 last:border-0 text-sm"
                        >
                            <div
                                class="col-span-2 sm:col-span-1 text-slate-500 text-xs tabular-nums"
                            >
                                #{{ bet.round_id }}
                            </div>
                            <div class="col-span-2">
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded text-xs font-bold tabular-nums',
                                        getCrashPointColor(bet.crash_point),
                                    ]"
                                >
                                    {{
                                        bet.crash_point
                                            ? parseFloat(
                                                  bet.crash_point,
                                              ).toFixed(2) + "x"
                                            : "—"
                                    }}
                                </span>
                            </div>
                            <div
                                class="col-span-2 text-right text-slate-700 dark:text-slate-300 tabular-nums"
                            >
                                {{ formatCoins(bet.amount) }}
                                <span
                                    v-if="bet.currency === 'DEMO'"
                                    class="text-[10px] text-slate-400 ml-0.5"
                                    >D</span
                                >
                            </div>
                            <div class="col-span-2 text-right tabular-nums">
                                <span
                                    v-if="bet.cashed_out_at"
                                    class="text-game-green font-semibold"
                                >
                                    {{
                                        parseFloat(bet.cashed_out_at).toFixed(
                                            2,
                                        )
                                    }}x
                                </span>
                                <span v-else class="text-game-red">—</span>
                            </div>
                            <div
                                :class="[
                                    'col-span-2 text-right font-semibold tabular-nums',
                                    bet.profit != null && bet.profit >= 0
                                        ? 'text-game-green'
                                        : 'text-game-red',
                                ]"
                            >
                                {{
                                    bet.profit != null
                                        ? (bet.profit >= 0 ? "+" : "") +
                                          formatCoins(bet.profit)
                                        : "-" + formatCoins(bet.amount)
                                }}
                            </div>
                            <div
                                class="col-span-2 sm:col-span-3 text-right text-xs text-slate-400"
                            >
                                {{ timeAgo(bet.created_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="store.myBetsPagination.last_page > 1"
                        class="flex items-center justify-center gap-2 mt-4"
                    >
                        <button
                            @click="
                                store.fetchMyBets(
                                    store.myBetsPagination.current_page - 1,
                                )
                            "
                            :disabled="store.myBetsPagination.current_page <= 1"
                            class="px-3 py-1.5 text-sm rounded-lg bg-surface-light dark:bg-surface-dark border border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:border-primary-500 transition disabled:opacity-40"
                        >
                            ← Prev
                        </button>
                        <span class="text-sm text-slate-500 tabular-nums">
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
                            class="px-3 py-1.5 text-sm rounded-lg bg-surface-light dark:bg-surface-dark border border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:border-primary-500 transition disabled:opacity-40"
                        >
                            Next →
                        </button>
                    </div>
                </div>

                <EmptyState
                    v-else
                    icon="🎰"
                    title="No bets yet"
                    message="Place your first bet to see your history here!"
                />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";
import {
    formatCoins,
    formatMultiplier,
    timeAgo,
    getCrashPointColor,
} from "@/Utils/formatters";

const store = useLeaderboardStore();

// ── Constants ──
const periods = [
    { value: "daily", label: "Today" },
    { value: "weekly", label: "Week" },
    { value: "monthly", label: "Month" },
    { value: "alltime", label: "All Time" },
];

const sectionTabs = [
    { value: "rankings", label: "Rankings", icon: "🏆" },
    { value: "mystats", label: "My Stats", icon: "📊" },
    { value: "mybets", label: "My Bets", icon: "🎰" },
];

const activeSection = ref("rankings");

// ── Computed ──
const topThree = computed(() => store.entries.slice(0, 3));

/** Reorder for podium: [2nd, 1st, 3rd] so gold is center */
const topThreeSorted = computed(() => {
    const t = topThree.value;
    if (t.length < 3) return t;
    return [t[1], t[0], t[2]];
});

const maxDailyProfit = computed(() => {
    const stats = store.personalStats?.daily_stats || [];
    if (!stats.length) return 1;
    return Math.max(...stats.map((d) => Math.abs(d.profit)), 1);
});

// ── Helpers ──
const defaultAvatar = (username) =>
    `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "?")}&background=random&color=fff&size=64`;

// ── Sub-components (inline) ──
const Spinner = {
    template: `
    <svg class="animate-spin w-8 h-8 text-primary-500" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>`,
};

const EmptyState = {
    props: ["icon", "title", "message"],
    template: `
    <div class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-16 text-center">
      <span class="text-4xl mb-4 block">{{ icon }}</span>
      <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">{{ title }}</h3>
      <p class="text-sm text-slate-400">{{ message }}</p>
    </div>`,
};

const StatCard = {
    props: ["label", "value", "icon", "color"],
    template: `
    <div class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-3">
      <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
        <span>{{ icon }}</span>
        <span>{{ label }}</span>
      </div>
      <div class="text-lg font-bold tabular-nums" :class="color || 'text-slate-800 dark:text-white'">{{ value }}</div>
    </div>`,
};

// ── Lifecycle ──
onMounted(() => {
    store.fetchLeaderboard();
});

// Load section data on tab switch
watch(activeSection, (section) => {
    if (section === "mystats" && !store.personalStats) {
        store.fetchPersonalStats();
    }
    if (section === "mybets" && !store.myBets.length) {
        store.fetchMyBets();
    }
});
</script>
