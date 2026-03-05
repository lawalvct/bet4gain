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
                    <span class="text-primary-500 font-semibold">History</span>
                    <a
                        href="/provably-fair"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >Fair</a
                    >
                </nav>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-6 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    📜 Game History
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Browse all completed game rounds
                </p>
            </div>

            <!-- Loading -->
            <div v-if="store.loadingHistory" class="flex justify-center py-16">
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

            <template v-else>
                <!-- Rounds Table -->
                <div
                    class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden"
                >
                    <!-- Table Header -->
                    <div
                        class="grid grid-cols-12 gap-2 px-4 py-3 border-b border-surface-light-border dark:border-surface-dark-border text-xs font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        <div class="col-span-2">Round</div>
                        <div class="col-span-2">Crash Point</div>
                        <div class="col-span-2 text-right">Bets</div>
                        <div class="col-span-3 text-right">Total Wagered</div>
                        <div class="col-span-3 text-right">Time</div>
                    </div>

                    <!-- Rows -->
                    <div
                        v-for="round in store.gameHistory"
                        :key="round.id"
                        class="grid grid-cols-12 gap-2 px-4 py-3 items-center border-b border-surface-light-border/50 dark:border-surface-dark-border/50 last:border-0 hover:bg-surface-light dark:hover:bg-surface-dark transition cursor-pointer group"
                        @click="toggleRound(round.id)"
                    >
                        <!-- Round ID -->
                        <div class="col-span-2">
                            <a
                                :href="'/provably-fair?round=' + round.id"
                                class="text-primary-500 hover:text-primary-600 font-medium text-sm transition"
                                @click.stop
                            >
                                #{{ round.id }}
                            </a>
                        </div>

                        <!-- Crash Point -->
                        <div class="col-span-2">
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold tabular-nums',
                                    crashPillClass(round.crash_point),
                                ]"
                            >
                                {{ formatMultiplier(round.crash_point) }}
                            </span>
                        </div>

                        <!-- Bet Count -->
                        <div
                            class="col-span-2 text-right text-sm text-slate-600 dark:text-slate-400 tabular-nums"
                        >
                            {{ round.bet_count }}
                        </div>

                        <!-- Total Wagered -->
                        <div
                            class="col-span-3 text-right text-sm text-slate-600 dark:text-slate-400 tabular-nums"
                        >
                            {{ formatCoins(round.total_wagered) }}
                        </div>

                        <!-- Time -->
                        <div
                            class="col-span-3 text-right text-xs text-slate-400"
                        >
                            {{ timeAgo(round.crashed_at) }}
                        </div>

                        <!-- Expanded: Round Bets -->
                        <div
                            v-if="expandedRound === round.id"
                            class="col-span-12 mt-2 border-t border-surface-light-border dark:border-surface-dark-border pt-3"
                            @click.stop
                        >
                            <div
                                v-if="loadingBets"
                                class="flex justify-center py-4"
                            >
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
                                <!-- Round Info -->
                                <div
                                    class="flex items-center justify-between mb-3"
                                >
                                    <span class="text-xs text-slate-500">
                                        {{ roundBets.length }} bet(s) in this
                                        round
                                    </span>
                                    <a
                                        :href="
                                            '/provably-fair?round=' + round.id
                                        "
                                        class="text-xs text-primary-500 hover:text-primary-600 font-medium transition"
                                    >
                                        🔍 Verify Fairness →
                                    </a>
                                </div>

                                <!-- Bets List -->
                                <div class="space-y-1.5">
                                    <div
                                        v-for="bet in roundBets"
                                        :key="bet.id"
                                        class="flex items-center gap-3 px-3 py-2 rounded-lg bg-surface-light dark:bg-surface-dark text-sm"
                                    >
                                        <img
                                            :src="
                                                bet.avatar ||
                                                defaultAvatar(bet.username)
                                            "
                                            class="w-5 h-5 rounded-full object-cover"
                                        />
                                        <span
                                            class="text-slate-700 dark:text-slate-300 font-medium truncate flex-1 min-w-0"
                                        >
                                            {{ bet.username }}
                                        </span>
                                        <span
                                            class="text-slate-500 tabular-nums"
                                            >{{ formatCoins(bet.amount) }}</span
                                        >
                                        <span class="text-slate-400">→</span>
                                        <span
                                            :class="[
                                                'font-semibold tabular-nums',
                                                bet.status === 'won'
                                                    ? 'text-game-green'
                                                    : 'text-game-red',
                                            ]"
                                        >
                                            {{
                                                bet.status === "won"
                                                    ? formatCoins(bet.payout)
                                                    : "Lost"
                                            }}
                                        </span>
                                        <span
                                            v-if="bet.cashed_out_at"
                                            class="text-xs text-slate-400 tabular-nums"
                                        >
                                            @{{
                                                formatMultiplier(
                                                    bet.cashed_out_at,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-if="!roundBets.length"
                                    class="text-center text-sm text-slate-400 py-4"
                                >
                                    No bets in this round
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Empty -->
                    <div
                        v-if="!store.gameHistory.length"
                        class="px-4 py-16 text-center text-sm text-slate-400"
                    >
                        <span class="text-4xl mb-4 block">📜</span>
                        No game rounds yet
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="store.gameHistoryPagination.last_page > 1"
                    class="flex items-center justify-center gap-2"
                >
                    <button
                        @click="
                            store.fetchGameHistory(
                                store.gameHistoryPagination.current_page - 1,
                            )
                        "
                        :disabled="
                            store.gameHistoryPagination.current_page <= 1
                        "
                        class="px-4 py-2 text-sm rounded-xl border border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:bg-surface-light-card dark:hover:bg-surface-dark-card transition disabled:opacity-30"
                    >
                        ← Previous
                    </button>
                    <span class="text-sm text-slate-500">
                        Page {{ store.gameHistoryPagination.current_page }} of
                        {{ store.gameHistoryPagination.last_page }}
                    </span>
                    <button
                        @click="
                            store.fetchGameHistory(
                                store.gameHistoryPagination.current_page + 1,
                            )
                        "
                        :disabled="
                            store.gameHistoryPagination.current_page >=
                            store.gameHistoryPagination.last_page
                        "
                        class="px-4 py-2 text-sm rounded-xl border border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:bg-surface-light-card dark:hover:bg-surface-dark-card transition disabled:opacity-30"
                    >
                        Next →
                    </button>
                </div>
            </template>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";
import { formatCoins, formatMultiplier, timeAgo } from "@/Utils/formatters";
import api from "@/Utils/api";

const store = useLeaderboardStore();

const expandedRound = ref(null);
const roundBets = ref([]);
const loadingBets = ref(false);

const crashPillClass = (value) => {
    if (value >= 10)
        return "bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400";
    if (value >= 2)
        return "bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400";
    if (value >= 1.5)
        return "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400";
    return "bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400";
};

const defaultAvatar = (username) =>
    `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "?")}&background=random&color=fff&size=32`;

const toggleRound = async (roundId) => {
    if (expandedRound.value === roundId) {
        expandedRound.value = null;
        return;
    }

    expandedRound.value = roundId;
    loadingBets.value = true;
    roundBets.value = [];

    try {
        const response = await api.get(`/api/game/round/${roundId}/bets`);
        roundBets.value = response.data.data || [];
    } catch (error) {
        console.error("Failed to fetch round bets:", error);
    } finally {
        loadingBets.value = false;
    }
};

onMounted(() => {
    store.fetchGameHistory();
});
</script>
