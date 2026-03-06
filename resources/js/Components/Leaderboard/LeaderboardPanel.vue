<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border flex flex-col"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-3 py-2 lg:py-1.5 border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >
                🏆 Leaderboard
            </h3>
            <div class="flex gap-0.5">
                <button
                    v-for="p in periods"
                    :key="p.value"
                    @click="store.setPeriod(p.value)"
                    :class="[
                        'px-1.5 py-0.5 text-[10px] font-medium rounded-md transition',
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
        <div class="flex-1 max-h-64 lg:max-h-48 overflow-y-auto scrollbar-thin">
            <!-- Loading skeleton -->
            <div v-if="store.loading" class="space-y-0">
                <div
                    v-for="n in 5"
                    :key="n"
                    class="flex items-center gap-2.5 px-3 py-2"
                >
                    <div
                        class="w-6 h-6 rounded-full bg-surface-light dark:bg-surface-dark animate-pulse"
                    ></div>
                    <div
                        class="w-6 h-6 rounded-full bg-surface-light dark:bg-surface-dark animate-pulse"
                    ></div>
                    <div
                        class="flex-1 h-3 rounded bg-surface-light dark:bg-surface-dark animate-pulse"
                    ></div>
                    <div
                        class="w-12 h-3 rounded bg-surface-light dark:bg-surface-dark animate-pulse"
                    ></div>
                </div>
            </div>

            <template v-else>
                <!-- Top 3 highlight -->
                <div
                    v-if="store.entries.length >= 3"
                    class="flex items-end justify-center gap-2 px-3 py-3 border-b border-surface-light-border/50 dark:border-surface-dark-border/50"
                >
                    <!-- 2nd place -->
                    <div class="flex flex-col items-center w-16">
                        <img
                            :src="
                                store.entries[1].avatar_url ||
                                defaultAvatar(store.entries[1].username)
                            "
                            class="w-7 h-7 rounded-full object-cover ring-1 ring-slate-400 mb-1"
                        />
                        <span
                            class="text-[10px] text-slate-400 truncate w-full text-center"
                            >{{ store.entries[1].username }}</span
                        >
                        <div
                            class="w-full h-8 bg-slate-400/20 rounded-t-md flex items-center justify-center mt-1"
                        >
                            <span class="text-[10px] font-bold text-slate-400"
                                >2nd</span
                            >
                        </div>
                    </div>
                    <!-- 1st place -->
                    <div class="flex flex-col items-center w-16 mb-0">
                        <img
                            :src="
                                store.entries[0].avatar_url ||
                                defaultAvatar(store.entries[0].username)
                            "
                            class="w-9 h-9 rounded-full object-cover ring-2 ring-amber-500 mb-1"
                        />
                        <span
                            class="text-[10px] text-amber-500 font-semibold truncate w-full text-center"
                            >{{ store.entries[0].username }}</span
                        >
                        <div
                            class="w-full h-11 bg-amber-500/20 rounded-t-md flex items-center justify-center mt-1"
                        >
                            <span class="text-[10px] font-bold text-amber-500"
                                >🥇</span
                            >
                        </div>
                    </div>
                    <!-- 3rd place -->
                    <div class="flex flex-col items-center w-16">
                        <img
                            :src="
                                store.entries[2].avatar_url ||
                                defaultAvatar(store.entries[2].username)
                            "
                            class="w-7 h-7 rounded-full object-cover ring-1 ring-amber-700 mb-1"
                        />
                        <span
                            class="text-[10px] text-slate-400 truncate w-full text-center"
                            >{{ store.entries[2].username }}</span
                        >
                        <div
                            class="w-full h-6 bg-amber-700/20 rounded-t-md flex items-center justify-center mt-1"
                        >
                            <span class="text-[10px] font-bold text-amber-700"
                                >3rd</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Remaining rows (skip top 3 if podium shown) -->
                <div
                    v-for="(entry, index) in listEntries"
                    :key="entry.rank || index"
                    class="flex items-center gap-2.5 px-3 py-1.5 border-b border-surface-light-border/30 dark:border-surface-dark-border/30 last:border-0 transition hover:bg-surface-light/50 dark:hover:bg-surface-dark/50"
                >
                    <!-- Rank -->
                    <span
                        :class="[
                            'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
                            'bg-surface-light dark:bg-surface-dark text-slate-400',
                        ]"
                    >
                        {{ entry.rank }}
                    </span>

                    <!-- Avatar -->
                    <img
                        :src="entry.avatar_url || defaultAvatar(entry.username)"
                        class="w-6 h-6 rounded-full object-cover shrink-0"
                    />

                    <!-- Username -->
                    <span
                        class="text-xs text-slate-700 dark:text-slate-300 truncate flex-1 min-w-0"
                    >
                        {{ entry.username || "Unknown" }}
                    </span>

                    <!-- Profit -->
                    <span
                        :class="[
                            'text-[11px] font-bold tabular-nums shrink-0',
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
                    class="px-4 py-6 text-center text-xs text-slate-400"
                >
                    No leaderboard data yet
                </div>
            </template>
        </div>

        <!-- View All -->
        <div
            class="px-3 py-1.5 text-center border-t border-surface-light-border dark:border-surface-dark-border"
        >
            <a
                href="/leaderboard"
                class="text-[11px] text-primary-500 hover:text-primary-600 font-medium transition"
            >
                View Full Leaderboard →
            </a>
        </div>
    </div>
</template>

<script setup>
import { onMounted, computed } from "vue";
import { useLeaderboardStore } from "@/Stores/leaderboardStore";
import { formatCoins } from "@/Utils/formatters";

const store = useLeaderboardStore();

const periods = [
    { value: "daily", label: "Day" },
    { value: "weekly", label: "Week" },
    { value: "monthly", label: "Month" },
    { value: "alltime", label: "All" },
];

/** Show rows starting from 4th when podium is visible */
const listEntries = computed(() =>
    store.entries.length >= 3 ? store.entries.slice(3) : store.entries,
);

const defaultAvatar = (username) =>
    `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "?")}&background=random&color=fff&size=32`;

onMounted(() => {
    store.fetchLeaderboard();
});
</script>
