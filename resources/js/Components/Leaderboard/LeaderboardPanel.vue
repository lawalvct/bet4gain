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
                    @click="activePeriod = p.value"
                    :class="[
                        'px-2 py-0.5 text-[10px] font-medium rounded-md transition',
                        activePeriod === p.value
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
            <div
                v-for="(entry, index) in leaders"
                :key="entry.id || index"
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
                    :src="
                        entry.user?.avatar_url || '/images/default-avatar.png'
                    "
                    class="w-6 h-6 rounded-full object-cover flex-shrink-0"
                />

                <!-- Username -->
                <span
                    class="text-sm text-slate-700 dark:text-slate-300 truncate flex-1 min-w-0"
                >
                    {{ entry.user?.username || "Unknown" }}
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
                    }}{{ formatCurrency(entry.total_profit) }}
                </span>
            </div>

            <div
                v-if="!leaders.length"
                class="px-4 py-8 text-center text-sm text-slate-400"
            >
                No leaderboard data yet
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { formatCurrency } from "@/Utils/formatters";

defineProps({
    leaders: { type: Array, default: () => [] },
});

const emit = defineEmits(["period-change"]);

const periods = [
    { value: "daily", label: "Day" },
    { value: "weekly", label: "Week" },
    { value: "monthly", label: "Month" },
    { value: "all_time", label: "All" },
];

const activePeriod = ref("daily");
</script>
