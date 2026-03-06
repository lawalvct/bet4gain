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
                    'flex-1 py-2 lg:py-1.5 text-sm font-medium transition',
                    activeTab === tab
                        ? 'text-primary-500 border-b-2 border-primary-500'
                        : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300',
                ]"
            >
                {{ tab === "manual" ? "🎯 Manual" : "🤖 Auto" }}
            </button>
        </div>

        <div class="p-3 space-y-3 lg:p-2.5 lg:space-y-2">
            <!-- Currency Toggle: COINS / DEMO -->
            <div class="flex items-center gap-2">
                <button
                    v-for="cur in currencies"
                    :key="cur.value"
                    @click="currency = cur.value"
                    :class="[
                        'flex-1 py-1.5 text-xs font-semibold rounded-lg transition border text-center',
                        currency === cur.value
                            ? cur.activeClass
                            : 'bg-surface-light dark:bg-surface-dark text-slate-400 border-transparent hover:border-slate-300 dark:hover:border-slate-600',
                    ]"
                >
                    {{ cur.icon }} {{ cur.label }}
                </button>
            </div>

            <!-- Available Balance Display -->
            <div
                class="flex items-center justify-between px-3 py-1.5 rounded-lg bg-surface-light dark:bg-surface-dark text-xs"
            >
                <span class="text-slate-400">Available</span>
                <span
                    class="font-semibold tabular-nums"
                    :class="{
                        'text-green-500': currency === 'NGN',
                        'text-amber-500': currency === 'COINS',
                        'text-slate-500': currency === 'DEMO',
                    }"
                >
                    {{
                        currency === "NGN"
                            ? `₦ ${formatNum(availableNGN)}`
                            : currency === "COINS"
                              ? `🪙 ${formatNum(availableCoins)}`
                              : `🎮 ${formatNum(availableDemo)}`
                    }}
                </span>
            </div>

            <!-- Bet Slot Tabs (Bet 1 / Bet 2) -->
            <div class="flex gap-2">
                <button
                    v-for="slot in [1, 2]"
                    :key="slot"
                    @click="activeSlot = slot"
                    :class="[
                        'flex-1 py-1 text-xs font-medium rounded-lg transition relative',
                        activeSlot === slot
                            ? 'bg-primary-500/10 text-primary-500 border border-primary-500/30'
                            : 'bg-surface-light dark:bg-surface-dark text-slate-400 border border-transparent',
                    ]"
                >
                    Bet {{ slot }}
                    <span
                        v-if="slotBet(slot)"
                        class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-game-green"
                    ></span>
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
                        :min="0"
                        step="1"
                        :disabled="slotHasBet"
                        class="w-full px-4 py-2.5 lg:py-2 pr-20 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-lg font-semibold tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition disabled:opacity-50"
                        placeholder="0"
                    />
                    <div
                        class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1"
                    >
                        <button
                            @click="
                                betAmount = Math.floor((betAmount || 0) * 2)
                            "
                            :disabled="slotHasBet"
                            class="px-2 py-1 text-xs bg-surface-light-card dark:bg-surface-dark-card border border-surface-light-border dark:border-surface-dark-border rounded-lg text-slate-500 hover:text-primary-500 transition disabled:opacity-40"
                        >
                            2x
                        </button>
                        <button
                            @click="
                                betAmount = Math.max(
                                    1,
                                    Math.floor((betAmount || 0) / 2),
                                )
                            "
                            :disabled="slotHasBet"
                            class="px-2 py-1 text-xs bg-surface-light-card dark:bg-surface-dark-card border border-surface-light-border dark:border-surface-dark-border rounded-lg text-slate-500 hover:text-primary-500 transition disabled:opacity-40"
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
                    :disabled="slotHasBet"
                    class="py-1 text-xs font-medium bg-surface-light dark:bg-surface-dark border border-surface-light-border dark:border-surface-dark-border rounded-lg text-slate-600 dark:text-slate-400 hover:border-primary-500 hover:text-primary-500 transition disabled:opacity-40"
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
                        :disabled="slotHasBet"
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
                    :disabled="slotHasBet"
                    class="w-full px-4 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white font-semibold tabular-nums focus:ring-2 focus:ring-primary-500 focus:border-transparent transition disabled:opacity-50"
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
                        >Stop on profit</label
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
                        >Stop on loss</label
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

            <!-- Insufficient Balance Warning -->
            <div
                v-if="insufficientBalance && !slotHasBet"
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-xs"
            >
                <span>⚠️</span>
                <span
                    >Insufficient
                    {{
                        currency === "NGN"
                            ? "wallet"
                            : currency === "COINS"
                              ? "coin"
                              : "demo"
                    }}
                    balance</span
                >
            </div>

            <!-- Error Message -->
            <div
                v-if="errorMessage"
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-xs"
            >
                <span>❌</span>
                <span>{{ errorMessage }}</span>
                <button @click="errorMessage = ''" class="ml-auto">✕</button>
            </div>

            <!-- Win Banner -->
            <div
                v-if="winBanner"
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-xs font-semibold animate-pulse"
            >
                <span>🎉</span>
                <span>{{ winBanner }}</span>
            </div>

            <!-- Action Button -->
            <button
                @click="handleAction"
                :disabled="actionDisabled"
                :class="[
                    'w-full py-3 lg:py-2.5 rounded-xl font-bold text-lg transition-all duration-200',
                    actionButtonClass,
                ]"
            >
                {{ actionLabel }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { QUICK_BET_AMOUNTS } from "@/Utils/constants";
import { useBetStore } from "@/Stores/betStore";
import { useWalletStore } from "@/Stores/walletStore";
import { useSound } from "@/Composables/useSound";

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
const walletStore = useWalletStore();
const sound = useSound();

// ── State ──────────────────────────────────────────────────────────────────
const activeTab = ref("manual");
const activeSlot = ref(1);
const betAmount = ref(100);
const currency = ref("COINS");
const autoCashoutEnabled = ref(false);
const autoCashoutAt = ref(2.0);
const autoRounds = ref(0);
const stopProfit = ref(0);
const stopLoss = ref(0);
const isAutoBetting = ref(false);
const errorMessage = ref("");
const winBanner = ref("");
let winBannerTimeout = null;

const quickAmounts = QUICK_BET_AMOUNTS;

const currencies = [
    {
        value: "NGN",
        label: "Cash",
        icon: "💵",
        activeClass: "bg-green-500/10 text-green-500 border-green-500/30",
    },
    {
        value: "COINS",
        label: "Coins",
        icon: "🪙",
        activeClass: "bg-amber-500/10 text-amber-500 border-amber-500/30",
    },
    {
        value: "DEMO",
        label: "Demo",
        icon: "🎮",
        activeClass: "bg-slate-500/10 text-slate-400 border-slate-500/30",
    },
];

// ── Wallet balances ────────────────────────────────────────────────────────
const availableNGN = computed(() => walletStore.balance || 0);
const availableCoins = computed(() => walletStore.coins || 0);
const availableDemo = computed(() => walletStore.demoCoins || 0);

const currentBalance = computed(() => {
    if (currency.value === "NGN") return availableNGN.value;
    if (currency.value === "COINS") return availableCoins.value;
    return availableDemo.value;
});

const insufficientBalance = computed(
    () => betAmount.value > 0 && betAmount.value > currentBalance.value,
);

const formatNum = (n) =>
    Number(n || 0).toLocaleString("en-NG", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

// ── Slot helpers ───────────────────────────────────────────────────────────
const slotBet = (slot) => betStore.bets[slot];

/** True when slot has a bet object (regardless of status) */
const slotHasBet = computed(() => betStore.bets[activeSlot.value] !== null);

/** True when slot has a live (actionable) bet – not already won/lost/cancelled */
const slotHasActiveBet = computed(() => {
    const bet = betStore.bets[activeSlot.value];
    if (!bet) return false;
    // Already resolved — e.g. auto-cashed-out or lost on crash
    if (
        bet.cashed_out_at ||
        bet.status === "won" ||
        bet.status === "lost" ||
        bet.status === "cancelled"
    )
        return false;
    return true;
});

/** The actual bet amount from store for cashout labels */
const activeBetAmount = computed(() => {
    const bet = betStore.bets[activeSlot.value];
    return bet ? parseFloat(bet.amount) : 0;
});

// ── Action button logic ────────────────────────────────────────────────────
const actionDisabled = computed(() => {
    if (betStore.placing || betStore.cashingOut) return true;

    if (props.status === "betting" || props.status === "waiting") {
        if (slotHasActiveBet.value) return false; // allow cancel
        if (slotHasBet.value) return true; // resolved bet, can't act
        if (!betAmount.value || betAmount.value <= 0) return true;
        if (insufficientBalance.value) return true;
        return !props.canBet;
    }

    if (props.status === "running") {
        return !slotHasActiveBet.value; // only enable cashout for active bets
    }

    return true;
});

const actionLabel = computed(() => {
    if (isAutoBetting.value && activeTab.value === "auto") {
        return `🛑 Stop Auto (${betStore.autoBetRoundsPlayed} rounds)`;
    }
    if (betStore.placing) return "Placing...";
    if (betStore.cashingOut) return "Cashing out...";

    // Bet already resolved (auto-cashed-out or lost) — show result
    if (
        slotHasBet.value &&
        !slotHasActiveBet.value &&
        props.status === "running"
    ) {
        const bet = betStore.bets[activeSlot.value];
        if (bet?.cashed_out_at || bet?.status === "won") {
            return `✅ Won ${formatNum(bet.payout)}`;
        }
        return "⏳ Waiting...";
    }

    if (slotHasActiveBet.value && props.status === "running") {
        const potential = (
            activeBetAmount.value * props.currentMultiplier
        ).toFixed(2);
        return `💰 Cashout ${potential}`;
    }

    if (
        slotHasActiveBet.value &&
        (props.status === "betting" || props.status === "waiting")
    ) {
        return "❌ Cancel Bet";
    }

    if (props.status === "betting" || props.status === "waiting") {
        if (activeTab.value === "auto") {
            return `🤖 Start Auto Bet`;
        }
        const icon =
            currency.value === "NGN"
                ? "₦"
                : currency.value === "COINS"
                  ? "🪙"
                  : "🎮";
        return `${icon} Bet ${formatNum(betAmount.value || 0)}`;
    }

    if (props.status === "crashed") return "⏳ Next Round...";
    return "Waiting...";
});

const actionButtonClass = computed(() => {
    if (slotHasActiveBet.value && props.status === "running") {
        return "bg-game-green hover:brightness-110 text-white shadow-lg shadow-game-green/30 animate-pulse";
    }
    if (slotHasActiveBet.value) {
        return "bg-red-500 hover:bg-red-600 text-white";
    }
    if (isAutoBetting.value) {
        return "bg-red-500 hover:bg-red-600 text-white";
    }
    if (actionDisabled.value) {
        return "bg-slate-300 dark:bg-slate-700 text-slate-500 cursor-not-allowed";
    }
    return "bg-primary-500 hover:bg-primary-600 text-white shadow-lg shadow-primary-500/30";
});

// ── Action handler ─────────────────────────────────────────────────────────
const handleAction = async () => {
    errorMessage.value = "";

    // Stop auto-bet
    if (isAutoBetting.value && activeTab.value === "auto") {
        isAutoBetting.value = false;
        emit("stop-auto");
        return;
    }

    // Cashout during running
    if (slotHasActiveBet.value && props.status === "running") {
        try {
            const result = await betStore.cashout({ slot: activeSlot.value });
            walletStore.fetchWallet();
            if (result?.data) {
                const d = result.data;
                const profit =
                    d.profit ?? d.payout - (activeBetAmount.value || 0);
                showWin(
                    `Won ${formatNum(d.payout)}! (${d.cashed_out_at}x, +${formatNum(profit)})`,
                );
            }
            sound.cashout();
        } catch (err) {
            errorMessage.value =
                err?.response?.data?.message || "Cashout failed";
        }
        return;
    }

    // Cancel bet during waiting/betting
    if (slotHasActiveBet.value) {
        try {
            await betStore.cancelBet({ slot: activeSlot.value });
            walletStore.fetchWallet();
        } catch (err) {
            errorMessage.value =
                err?.response?.data?.message || "Cancel failed";
        }
        return;
    }

    // Start auto-bet
    if (activeTab.value === "auto") {
        if (!autoCashoutAt.value || autoCashoutAt.value < 1.01) {
            errorMessage.value = "Auto cashout must be at least 1.01x";
            return;
        }
        isAutoBetting.value = true;
        emit("start-auto", {
            amount: betAmount.value,
            cashoutAt: autoCashoutAt.value,
            rounds: autoRounds.value,
            stopProfit: stopProfit.value,
            stopLoss: stopLoss.value,
            slot: activeSlot.value,
            currency: currency.value,
        });
        // Place the first auto-bet immediately if round accepts bets
        if (props.canBet) {
            await placeBetNow();
        }
        return;
    }

    // Manual bet
    await placeBetNow();
};

/** Place a bet right now with current settings */
const placeBetNow = async () => {
    errorMessage.value = "";

    if (!betAmount.value || betAmount.value <= 0) {
        errorMessage.value = "Enter a bet amount";
        return;
    }
    if (insufficientBalance.value) {
        errorMessage.value = `Not enough ${
            currency.value === "NGN"
                ? "wallet balance"
                : currency.value === "COINS"
                  ? "coins"
                  : "demo coins"
        }`;
        return;
    }

    try {
        await betStore.placeBet({
            amount: betAmount.value,
            autoCashout:
                autoCashoutEnabled.value || activeTab.value === "auto"
                    ? autoCashoutAt.value
                    : null,
            slot: activeSlot.value,
            currency: currency.value,
        });
        // Refresh balance after deduction
        walletStore.fetchWallet();
        sound.betPlaced();
    } catch (err) {
        errorMessage.value =
            err?.response?.data?.message ||
            err?.response?.data?.errors?.amount?.[0] ||
            "Failed to place bet";
    }
};

// ── Win banner helper ──────────────────────────────────────────────────────
const showWin = (msg) => {
    winBanner.value = msg;
    if (winBannerTimeout) clearTimeout(winBannerTimeout);
    winBannerTimeout = setTimeout(() => {
        winBanner.value = "";
    }, 5000);
};

// ── Auto-bet: place bet on each new betting round ──────────────────────────
watch(
    () => props.status,
    (newStatus, oldStatus) => {
        // When round crashes, check if we should show a loss
        if (newStatus === "crashed" && oldStatus === "running") {
            [1, 2].forEach((slot) => {
                const bet = betStore.bets[slot];
                if (bet && !bet.cashed_out_at && !bet.payout) {
                    const label =
                        bet.currency === "NGN"
                            ? "naira"
                            : bet.currency === "DEMO"
                              ? "demo coins"
                              : "coins";
                    errorMessage.value = `Bet ${slot}: Lost ${formatNum(bet.amount)} ${label}`;
                }
            });
            walletStore.fetchWallet();
        }

        // Auto-bet: when next round opens for betting, auto-place
        if (
            isAutoBetting.value &&
            (newStatus === "betting" || newStatus === "waiting") &&
            (oldStatus === "crashed" || oldStatus === "running")
        ) {
            if (!betStore.autoBetActive) {
                isAutoBetting.value = false;
                return;
            }
            setTimeout(async () => {
                if (!isAutoBetting.value) return;
                await placeBetNow();
            }, 500);
        }
    },
);

// Track auto-bet profit on crash
watch(
    () => props.status,
    (newStatus) => {
        if (newStatus === "crashed" && isAutoBetting.value) {
            const bet = betStore.bets[activeSlot.value];
            if (bet) {
                const profit = bet.payout
                    ? parseFloat(bet.payout) - parseFloat(bet.amount)
                    : -parseFloat(bet.amount);
                betStore.incrementAutoRound(profit);
            }
        }
    },
);
</script>
