<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl border border-surface-light-border dark:border-surface-dark-border shadow-card p-6"
    >
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                🎁 Send Coins
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

        <!-- Success State -->
        <div v-if="successData" class="text-center py-6 space-y-4">
            <div class="text-5xl">✅</div>
            <h4 class="text-xl font-bold text-slate-900 dark:text-white">
                Transfer Successful!
            </h4>
            <p class="text-slate-500 dark:text-slate-400">
                You sent
                <span class="font-bold text-amber-500"
                    >🪙
                    {{ Number(successData.net_amount).toLocaleString() }}</span
                >
                coins to
                <span class="font-semibold text-primary-500">{{
                    successData.recipient
                }}</span>
            </p>
            <p v-if="successData.fee > 0" class="text-xs text-slate-400">
                Fee: {{ Number(successData.fee).toLocaleString() }} coins
            </p>
            <div class="flex gap-3 justify-center pt-4">
                <BaseButton variant="outline" @click="resetForm">
                    Send More
                </BaseButton>
                <BaseButton variant="primary" @click="$emit('close')">
                    Done
                </BaseButton>
            </div>
        </div>

        <!-- Transfer Form -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Input Side -->
            <div class="space-y-4">
                <!-- Recipient Username -->
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        Recipient Username
                    </label>
                    <div class="relative">
                        <BaseInput
                            v-model="username"
                            type="text"
                            placeholder="Enter username"
                            block
                            @blur="resolveUser"
                            @keydown.enter.prevent="resolveUser"
                        >
                            <template #prefix>@</template>
                        </BaseInput>
                        <div
                            v-if="resolving"
                            class="absolute right-3 top-1/2 -translate-y-1/2"
                        >
                            <BaseSpinner size="sm" />
                        </div>
                    </div>

                    <!-- Resolved user badge -->
                    <div
                        v-if="resolvedUser"
                        class="mt-2 p-2 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 flex items-center gap-2"
                    >
                        <span class="text-green-500">✓</span>
                        <span
                            class="text-sm font-medium text-green-700 dark:text-green-300"
                        >
                            {{ resolvedUser.username }}
                        </span>
                    </div>
                    <p v-if="resolveError" class="text-sm text-game-red mt-1">
                        {{ resolveError }}
                    </p>
                </div>

                <!-- Amount -->
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        Amount (Coins)
                    </label>
                    <BaseInput
                        v-model="amount"
                        type="number"
                        placeholder="Enter amount"
                        :min="transferConfig.min_transfer"
                        :max="transferConfig.max_transfer"
                        block
                    >
                        <template #prefix>🪙</template>
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
                        {{ preset.toLocaleString() }}
                    </button>
                </div>

                <!-- Transfer type -->
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        Type
                    </label>
                    <div class="flex gap-2">
                        <button
                            @click="transferType = 'transfer'"
                            :class="[
                                'flex-1 py-2 rounded-lg text-sm font-medium border transition',
                                transferType === 'transfer'
                                    ? 'bg-primary-500 text-white border-primary-500'
                                    : 'border-surface-light-border dark:border-surface-dark-border text-slate-500 dark:text-slate-400 hover:border-primary-400',
                            ]"
                        >
                            💸 Transfer
                        </button>
                        <button
                            @click="transferType = 'gift'"
                            :class="[
                                'flex-1 py-2 rounded-lg text-sm font-medium border transition',
                                transferType === 'gift'
                                    ? 'bg-amber-500 text-white border-amber-500'
                                    : 'border-surface-light-border dark:border-surface-dark-border text-slate-500 dark:text-slate-400 hover:border-amber-400',
                            ]"
                        >
                            🎁 Gift
                        </button>
                    </div>
                </div>

                <!-- Note -->
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >
                        Note (optional)
                    </label>
                    <BaseInput
                        v-model="note"
                        type="text"
                        placeholder="Add a message..."
                        :maxlength="200"
                        block
                    />
                </div>
            </div>

            <!-- Preview Side -->
            <div
                class="p-4 rounded-xl bg-slate-50 dark:bg-surface-dark-alt border border-surface-light-border dark:border-surface-dark-border space-y-3"
            >
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    Transfer Preview
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400"
                            >Amount</span
                        >
                        <span
                            class="font-medium text-slate-900 dark:text-white"
                        >
                            🪙 {{ Number(amount || 0).toLocaleString() }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400"
                            >Fee ({{ transferConfig.fee_percent }}%)</span
                        >
                        <span class="font-medium text-game-red">
                            -{{ feeAmount.toLocaleString() }}
                        </span>
                    </div>
                    <div
                        class="border-t border-surface-light-border dark:border-surface-dark-border pt-2 flex justify-between"
                    >
                        <span
                            class="font-semibold text-slate-700 dark:text-slate-300"
                            >Recipient gets</span
                        >
                        <span class="font-bold text-lg text-game-green">
                            🪙 {{ netAmount.toLocaleString() }}
                        </span>
                    </div>
                </div>

                <div
                    class="mt-3 text-xs text-slate-400 dark:text-slate-500 space-y-1"
                >
                    <div>
                        Your balance: 🪙
                        {{ Number(coinBalance).toLocaleString() }}
                    </div>
                    <div>
                        Daily remaining: 🪙
                        {{
                            Number(
                                transferConfig.daily_remaining,
                            ).toLocaleString()
                        }}
                    </div>
                    <div>
                        Min:
                        {{
                            Number(transferConfig.min_transfer).toLocaleString()
                        }}
                        · Max:
                        {{
                            Number(transferConfig.max_transfer).toLocaleString()
                        }}
                    </div>
                </div>

                <!-- Confirmation step -->
                <div v-if="showConfirm" class="mt-4 space-y-3">
                    <div
                        class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 text-sm text-amber-700 dark:text-amber-300"
                    >
                        Confirm sending
                        <strong
                            >🪙 {{ Number(amount).toLocaleString() }}</strong
                        >
                        to <strong>@{{ resolvedUser?.username }}</strong
                        >?
                    </div>
                    <div class="flex gap-2">
                        <BaseButton
                            variant="outline"
                            block
                            @click="showConfirm = false"
                        >
                            Cancel
                        </BaseButton>
                        <BaseButton
                            variant="success"
                            block
                            :loading="loading"
                            @click="confirmTransfer"
                        >
                            Confirm Send
                        </BaseButton>
                    </div>
                </div>

                <BaseButton
                    v-else
                    variant="success"
                    size="lg"
                    block
                    :disabled="!isValid"
                    @click="showConfirm = true"
                >
                    <template #icon>
                        <span>🚀</span>
                    </template>
                    Send Coins
                </BaseButton>
            </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-game-red mt-3">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import BaseButton from "@/Components/UI/BaseButton.vue";
import BaseInput from "@/Components/UI/BaseInput.vue";
import BaseSpinner from "@/Components/UI/BaseSpinner.vue";
import { useWalletStore } from "@/Stores/walletStore";
import api from "@/Utils/api";

const props = defineProps({
    coinBalance: { type: Number, default: 0 },
});

const emit = defineEmits(["close", "success"]);

const walletStore = useWalletStore();

const username = ref("");
const amount = ref("");
const transferType = ref("transfer");
const note = ref("");
const loading = ref(false);
const resolving = ref(false);
const error = ref("");
const resolveError = ref("");
const resolvedUser = ref(null);
const showConfirm = ref(false);
const successData = ref(null);

const transferConfig = ref({
    fee_percent: 2,
    min_transfer: 100,
    max_transfer: 100000,
    daily_limit: 500000,
    daily_sent: 0,
    daily_remaining: 500000,
});

const presets = [100, 500, 1000, 5000, 10000, 50000];

const feeAmount = computed(() => {
    const num = Number(amount.value) || 0;
    return (
        Math.round(num * (transferConfig.value.fee_percent / 100) * 100) / 100
    );
});

const netAmount = computed(() => {
    const num = Number(amount.value) || 0;
    return Math.max(0, num - feeAmount.value);
});

const isValid = computed(() => {
    const num = Number(amount.value);
    if (!resolvedUser.value) return false;
    if (num < transferConfig.value.min_transfer) return false;
    if (num > transferConfig.value.max_transfer) return false;
    if (num > props.coinBalance) return false;
    if (num > transferConfig.value.daily_remaining) return false;
    return true;
});

const resolveUser = async () => {
    const name = username.value.trim();
    if (!name || name.length < 2) {
        resolvedUser.value = null;
        resolveError.value = "";
        return;
    }

    resolving.value = true;
    resolveError.value = "";
    resolvedUser.value = null;

    try {
        const response = await api.post("/wallet/resolve-user", {
            username: name,
        });
        if (response.data.found) {
            resolvedUser.value = response.data.user;
        }
    } catch (e) {
        resolveError.value = e.response?.data?.message || "User not found.";
    } finally {
        resolving.value = false;
    }
};

const confirmTransfer = async () => {
    if (!isValid.value) return;
    loading.value = true;
    error.value = "";

    try {
        const response = await api.post("/wallet/transfer-coins", {
            username: resolvedUser.value.username,
            amount: Number(amount.value),
            type: transferType.value,
            note: note.value || undefined,
        });

        // Update wallet store with new balances
        if (response.data.coins) {
            walletStore.updateCoinBalance(response.data.coins.balance);
        }
        if (response.data.wallet) {
            walletStore.updateBalance(response.data.wallet.balance);
        }

        successData.value = response.data.transfer;
        showConfirm.value = false;

        // Refresh wallet data
        walletStore.fetchWallet();
        walletStore.fetchTransactions();

        emit("success");
    } catch (e) {
        error.value =
            e.response?.data?.message || "Transfer failed. Please try again.";
        showConfirm.value = false;
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    username.value = "";
    amount.value = "";
    note.value = "";
    transferType.value = "transfer";
    resolvedUser.value = null;
    resolveError.value = "";
    error.value = "";
    showConfirm.value = false;
    successData.value = null;
    fetchTransferConfig();
};

const fetchTransferConfig = async () => {
    try {
        const response = await api.get("/wallet/transfer-config");
        transferConfig.value = response.data;
    } catch (e) {
        console.error("Failed to load transfer config:", e);
    }
};

onMounted(() => {
    fetchTransferConfig();
});
</script>
