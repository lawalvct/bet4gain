<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl border border-surface-light-border dark:border-surface-dark-border shadow-card p-6"
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                {{ mode === "buy" ? "Buy Coins" : "Sell Coins" }}
            </h3>
            <button
                @click="$emit('close')"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition"
            >
                <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Input Side -->
            <div class="space-y-4">
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        {{ mode === "buy" ? "Amount (₦)" : "Coins to Sell" }}
                    </label>
                    <BaseInput
                        v-model="amount"
                        type="number"
                        :placeholder="
                            mode === 'buy'
                                ? 'Enter NGN amount'
                                : 'Enter coin amount'
                        "
                        :min="10"
                        block
                    >
                        <template #prefix>
                            {{ mode === "buy" ? "₦" : "🪙" }}
                        </template>
                    </BaseInput>
                </div>

                <!-- Quick amounts -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in presets"
                        :key="preset"
                        @click="amount = preset"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium border border-surface-light-border dark:border-surface-dark-border hover:border-primary-400 hover:text-primary-500 transition text-slate-600 dark:text-slate-400"
                    >
                        {{ mode === "buy" ? "₦" : ""
                        }}{{ preset.toLocaleString() }}
                    </button>
                </div>
            </div>

            <!-- Preview Side -->
            <div
                class="p-4 rounded-xl bg-slate-50 dark:bg-surface-dark-alt border border-surface-light-border dark:border-surface-dark-border space-y-3"
            >
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    You'll {{ mode === "buy" ? "receive" : "get" }}
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white">
                    <template v-if="mode === 'buy'">
                        🪙 {{ convertedAmount.toLocaleString() }} coins
                    </template>
                    <template v-else>
                        ₦{{ convertedAmount.toLocaleString() }}
                    </template>
                </div>
                <div class="text-xs text-slate-400">
                    Rate: 1 coin = ₦{{
                        mode === "buy"
                            ? config.coin_rate || 1
                            : config.sell_rate || 1
                    }}
                </div>

                <div
                    class="pt-3 border-t border-surface-light-border dark:border-surface-dark-border text-sm text-slate-500 dark:text-slate-400"
                >
                    <template v-if="mode === 'buy'">
                        Wallet balance: ₦{{
                            Number(walletBalance).toLocaleString()
                        }}
                    </template>
                    <template v-else>
                        Coin balance: {{ Number(coinBalance).toLocaleString() }}
                    </template>
                </div>

                <BaseButton
                    :variant="mode === 'buy' ? 'success' : 'primary'"
                    size="lg"
                    block
                    :loading="loading"
                    :disabled="!isValid"
                    @click="submit"
                >
                    {{ mode === "buy" ? "Buy Coins" : "Sell Coins" }}
                </BaseButton>
            </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-game-red mt-3">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import BaseButton from "@/Components/UI/BaseButton.vue";
import BaseInput from "@/Components/UI/BaseInput.vue";
import { useWalletStore } from "@/Stores/walletStore";

const props = defineProps({
    mode: {
        type: String,
        required: true,
        validator: (v) => ["buy", "sell"].includes(v),
    },
    walletBalance: { type: Number, default: 0 },
    coinBalance: { type: Number, default: 0 },
    config: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["close", "success"]);

const walletStore = useWalletStore();

const amount = ref("");
const loading = ref(false);
const error = ref("");

const presets = [100, 500, 1000, 5000, 10000, 50000];

const convertedAmount = computed(() => {
    const num = Number(amount.value) || 0;
    if (props.mode === "buy") {
        return num * (1 / (props.config.coin_rate || 1));
    }
    return num * (props.config.sell_rate || 1);
});

const isValid = computed(() => {
    const num = Number(amount.value);
    if (num < 10) return false;
    if (props.mode === "buy") {
        return num <= props.walletBalance;
    }
    return num <= props.coinBalance;
});

const submit = async () => {
    if (!isValid.value) return;
    loading.value = true;
    error.value = "";

    try {
        if (props.mode === "buy") {
            await walletStore.purchaseCoins({ amount: Number(amount.value) });
        } else {
            await walletStore.sellCoins({ amount: Number(amount.value) });
        }
        amount.value = "";
        emit("success");
    } catch (e) {
        error.value =
            e.response?.data?.message ||
            "Transaction failed. Please try again.";
    } finally {
        loading.value = false;
    }
};
</script>
