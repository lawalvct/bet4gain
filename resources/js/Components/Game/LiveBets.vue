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
                🎲 Live Bets
                <span class="text-xs text-slate-400 font-normal ml-1"
                    >({{ bets.length }})</span
                >
            </h3>
            <div class="text-xs text-slate-400 tabular-nums">
                Pool:
                <span class="text-primary-500 font-semibold">{{
                    formatCurrency(totalPool)
                }}</span>
            </div>
        </div>

        <!-- Bets List -->
        <div
            class="divide-y divide-surface-light-border dark:divide-surface-dark-border max-h-64 overflow-y-auto scrollbar-thin"
        >
            <div
                v-for="bet in bets"
                :key="bet.id"
                :class="[
                    'flex items-center gap-3 px-4 py-2 transition-colors',
                    bet.cashed_out_at ? 'bg-game-green/5' : '',
                    bet.lost ? 'opacity-50' : '',
                ]"
            >
                <!-- Avatar -->
                <img
                    :src="bet.user?.avatar_url || '/images/default-avatar.png'"
                    :alt="bet.user?.username"
                    class="w-6 h-6 rounded-full object-cover flex-shrink-0"
                />

                <!-- Username -->
                <span
                    class="text-sm text-slate-700 dark:text-slate-300 truncate flex-1 min-w-0"
                >
                    {{ bet.user?.username || "Guest" }}
                </span>

                <!-- Amount -->
                <span class="text-xs text-slate-400 tabular-nums flex-shrink-0">
                    {{ formatCurrency(bet.amount) }}
                </span>

                <!-- Cashout or Multiplier -->
                <span
                    v-if="bet.cashout_multiplier"
                    class="text-xs font-bold text-game-green tabular-nums flex-shrink-0 min-w-[50px] text-right"
                >
                    {{ bet.cashout_multiplier }}x
                </span>
                <span
                    v-else
                    class="text-xs text-slate-400 tabular-nums flex-shrink-0 min-w-[50px] text-right"
                >
                    —
                </span>

                <!-- Profit -->
                <span
                    v-if="bet.cashout_multiplier"
                    class="text-xs font-semibold text-game-green tabular-nums flex-shrink-0 min-w-[60px] text-right"
                >
                    +{{ formatCurrency(bet.profit) }}
                </span>
            </div>

            <div
                v-if="!bets.length"
                class="px-4 py-8 text-center text-sm text-slate-400"
            >
                No bets yet this round
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { formatCurrency } from "@/Utils/formatters";

const props = defineProps({
    bets: { type: Array, default: () => [] },
});

const totalPool = computed(() => {
    return props.bets.reduce((sum, bet) => sum + (bet.amount || 0), 0);
});
</script>
