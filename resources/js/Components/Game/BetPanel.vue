<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border"
    >
        <!-- Tab: Manual / Auto -->
        <div
            class="flex border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <button
                v-for="tab in ['manual', 'auto']"
                :key="tab"
                @click="activeTab = tab"
                :class="[
                    'flex-1 py-2.5 text-sm font-medium transition',
                    activeTab === tab
                        ? 'text-primary-500 border-b-2 border-primary-500'
                        : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300',
                ]"
            >
                {{ tab === "manual" ? "🎯 Manual" : "🤖 Auto" }}
            </button>
        </div>

        <div class="p-4 space-y-4">
            <!-- Bet Slot Tabs (Bet 1 / Bet 2) -->
            <div class="flex gap-2">
                <button
                    v-for="slot in [1, 2]"
                    :key="slot"
                    @click="activeSlot = slot"
                    :class="[
                        'flex-1 py-1.5 text-xs font-medium rounded-lg transition',
                        activeSlot === slot
                            ? 'bg-primary-500/10 text-primary-500 border border-primary-500/30'
                            : 'bg-surface-light dark:bg-surface-dark text-slate-400 border border-transparent',
                    ]"
                >
                    Bet {{ slot }}
                </button>
            </div>

            <!-- Amount Input -->
            <div>
                <label class="block text-xs text-slate-400 mb-1"
                    >Bet Amount</label
                >
                <div class="relative">
                    <input
                        v-model.number="betAmount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full px-4 py-3 pr-20 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-lg font-semibold tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="0.00"
                    />
                    <div
                        class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1"
                    >
                        <button
                            @click="betAmount = (betAmount || 0) * 2"
                            class="px-2 py-1 text-xs bg-surface-light-card dark:bg-surface-dark-card border border-surface-light-border dark:border-surface-dark-border rounded-lg text-slate-500 hover:text-primary-500 transition"
                        >
                            2x
                        </button>
                        <button
                            @click="betAmount = (betAmount || 0) / 2"
                            class="px-2 py-1 text-xs bg-surface-light-card dark:bg-surface-dark-card border border-surface-light-border dark:border-surface-dark-border rounded-lg text-slate-500 hover:text-primary-500 transition"
                        >
                            ½
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Amounts -->
            <div class="grid grid-cols-4 gap-1.5">
                <button
                    v-for="amount in quickAmounts"
                    :key="amount"
                    @click="betAmount = amount"
                    class="py-1.5 text-xs font-medium bg-surface-light dark:bg-surface-dark border border-surface-light-border dark:border-surface-dark-border rounded-lg text-slate-600 dark:text-slate-400 hover:border-primary-500 hover:text-primary-500 transition"
                >
                    {{ amount }}
                </button>
            </div>

            <!-- Auto Cashout (Manual mode) -->
            <div v-if="activeTab === 'manual'">
                <label class="flex items-center gap-2 mb-1">
                    <input
                        v-model="autoCashoutEnabled"
                        type="checkbox"
                        class="rounded border-slate-300 dark:border-slate-600 text-primary-500 focus:ring-primary-500"
                    />
                    <span class="text-xs text-slate-400">Auto Cashout at</span>
                </label>
                <input
                    v-if="autoCashoutEnabled"
                    v-model.number="autoCashoutAt"
                    type="number"
                    min="1.01"
                    step="0.01"
                    class="w-full px-4 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white font-semibold tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                    placeholder="2.00"
                />
            </div>

            <!-- Auto Bet Settings -->
            <div v-if="activeTab === 'auto'" class="space-y-3">
                <div>
                    <label class="block text-xs text-slate-400 mb-1"
                        >Auto Cashout at</label
                    >
                    <input
                        v-model.number="autoCashoutAt"
                        type="number"
                        min="1.01"
                        step="0.01"
                        class="w-full px-4 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white font-semibold tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="2.00"
                    />
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"
                        >Number of Rounds (0 = infinite)</label
                    >
                    <input
                        v-model.number="autoRounds"
                        type="number"
                        min="0"
                        class="w-full px-4 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="0"
                    />
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"
                        >Stop on profit (₦)</label
                    >
                    <input
                        v-model.number="stopProfit"
                        type="number"
                        min="0"
                        class="w-full px-4 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="0"
                    />
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"
                        >Stop on loss (₦)</label
                    >
                    <input
                        v-model.number="stopLoss"
                        type="number"
                        min="0"
                        class="w-full px-4 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="0"
                    />
                </div>
            </div>

            <!-- Action Button -->
            <button
                @click="handleAction"
                :disabled="actionDisabled"
                :class="[
                    'w-full py-4 rounded-xl font-bold text-lg transition-all duration-200',
                    actionButtonClass,
                ]"
            >
                {{ actionLabel }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { QUICK_BET_AMOUNTS } from "@/Utils/constants";
import { useBetStore } from "@/Stores/betStore";

const props = defineProps({
    status: { type: String, default: "waiting" },
    currentMultiplier: { type: Number, default: 1.0 },
    canBet: { type: Boolean, default: true },
});

const emit = defineEmits([
    "place-bet",
    "cashout",
    "cancel-bet",
    "start-auto",
    "stop-auto",
]);

const betStore = useBetStore();

const activeTab = ref("manual");
const activeSlot = ref(1);
const betAmount = ref(100);
const autoCashoutEnabled = ref(false);
const autoCashoutAt = ref(2.0);
const autoRounds = ref(0);
const stopProfit = ref(0);
const stopLoss = ref(0);
const isAutoBetting = ref(false);

const quickAmounts = QUICK_BET_AMOUNTS;

/** Does the current active slot have a bet placed? */
const slotHasBet = computed(() => betStore.bets[activeSlot.value] !== null);

const actionDisabled = computed(() => {
    if (betStore.placing || betStore.cashingOut) return true;
    if (props.status === "betting" || props.status === "waiting") {
        if (slotHasBet.value) return false; // allow cancel
        return !betAmount.value || betAmount.value <= 0 || !props.canBet;
    }
    if (props.status === "running") {
        return !slotHasBet.value;
    }
    return true;
});

const actionLabel = computed(() => {
    if (isAutoBetting.value) return "Stop Auto Bet";
    if (betStore.placing) return "Placing...";
    if (betStore.cashingOut) return "Cashing out...";
    if (slotHasBet.value && props.status === "running") {
        const potential = (betAmount.value * props.currentMultiplier).toFixed(
            2,
        );
        return `Cashout ${potential}`;
    }
    if (
        slotHasBet.value &&
        (props.status === "betting" || props.status === "waiting")
    ) {
        return "Cancel Bet";
    }
    if (props.status === "betting" || props.status === "waiting") {
        return activeTab.value === "auto"
            ? "Start Auto Bet"
            : `Bet ${betAmount.value || 0}`;
    }
    return "Waiting...";
});

const actionButtonClass = computed(() => {
    if (slotHasBet.value && props.status === "running") {
        return "bg-game-green hover:brightness-110 text-white shadow-lg shadow-game-green/30 animate-pulse";
    }
    if (slotHasBet.value) {
        return "bg-red-500 hover:bg-red-600 text-white";
    }
    if (actionDisabled.value) {
        return "bg-slate-300 dark:bg-slate-700 text-slate-500 cursor-not-allowed";
    }
    return "bg-primary-500 hover:bg-primary-600 text-white shadow-lg shadow-primary-500/30";
});

const handleAction = () => {
    if (isAutoBetting.value) {
        isAutoBetting.value = false;
        emit("stop-auto");
        return;
    }
    if (slotHasBet.value && props.status === "running") {
        emit("cashout", { slot: activeSlot.value });
        return;
    }
    if (slotHasBet.value) {
        emit("cancel-bet", { slot: activeSlot.value });
        return;
    }
    if (activeTab.value === "auto") {
        isAutoBetting.value = true;
        emit("start-auto", {
            amount: betAmount.value,
            cashoutAt: autoCashoutAt.value,
            rounds: autoRounds.value,
            stopProfit: stopProfit.value,
            stopLoss: stopLoss.value,
            slot: activeSlot.value,
        });
        return;
    }
    emit("place-bet", {
        amount: betAmount.value,
        autoCashout: autoCashoutEnabled.value ? autoCashoutAt.value : null,
        slot: activeSlot.value,
    });
};
</script>
