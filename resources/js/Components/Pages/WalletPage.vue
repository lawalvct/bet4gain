<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark">
        <!-- Header -->
        <header
            class="bg-surface-light-card dark:bg-surface-dark-card border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <div class="max-w-5xl mx-auto px-4 py-4 flex items-center gap-3">
                <a
                    href="/"
                    class="text-slate-400 hover:text-primary-500 transition"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    Wallet
                </h1>
            </div>
        </header>

        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
            <!-- Payment Result Banner -->
            <div
                v-if="paymentResult"
                :class="[
                    'p-4 rounded-xl border flex items-center gap-3',
                    paymentResult === 'success'
                        ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700 text-green-800 dark:text-green-300'
                        : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 text-red-800 dark:text-red-300',
                ]"
            >
                <span class="text-xl">{{
                    paymentResult === "success" ? "✅" : "❌"
                }}</span>
                <span class="font-medium">{{
                    paymentResult === "success"
                        ? "Payment completed successfully! Your wallet has been credited."
                        : "Payment failed or was cancelled. Please try again."
                }}</span>
                <button
                    @click="paymentResult = null"
                    class="ml-auto text-slate-400 hover:text-slate-600"
                >
                    ✕
                </button>
            </div>

            <!-- Balance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- NGN Wallet -->
                <div
                    class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl p-6 text-white shadow-lg"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-white/80"
                            >NGN Wallet</span
                        >
                        <span class="text-2xl">💰</span>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        ₦{{ formatNumber(walletBalance) }}
                    </div>
                    <div class="text-white/70 text-sm">Nigerian Naira</div>
                </div>

                <!-- Coin Balance -->
                <div
                    class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-white/80"
                            >Game Coins</span
                        >
                        <span class="text-2xl">🪙</span>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        {{ formatNumber(coinBalance) }}
                    </div>
                    <div class="text-white/70 text-sm">
                        Available for betting
                    </div>
                </div>

                <!-- Demo Balance -->
                <div
                    class="bg-gradient-to-br from-slate-500 to-slate-700 rounded-2xl p-6 text-white shadow-lg"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-white/80"
                            >Demo Coins</span
                        >
                        <span class="text-2xl">🎮</span>
                    </div>
                    <div class="text-3xl font-bold mb-1">
                        {{ formatNumber(demoBalance) }}
                    </div>
                    <div class="text-white/70 text-sm">Practice mode</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <BaseButton
                    variant="primary"
                    size="lg"
                    block
                    @click="showDeposit = true"
                >
                    <template #icon>
                        <span>💳</span>
                    </template>
                    Deposit
                </BaseButton>
                <BaseButton
                    variant="outline"
                    size="lg"
                    block
                    @click="showWithdraw = true"
                >
                    <template #icon>
                        <span>🏦</span>
                    </template>
                    Withdraw
                </BaseButton>
                <BaseButton
                    variant="success"
                    size="lg"
                    block
                    @click="showBuyCoins = true"
                >
                    <template #icon>
                        <span>🪙</span>
                    </template>
                    Buy Coins
                </BaseButton>
                <BaseButton
                    variant="secondary"
                    size="lg"
                    block
                    @click="showSellCoins = true"
                >
                    <template #icon>
                        <span>💵</span>
                    </template>
                    Sell Coins
                </BaseButton>
                <BaseButton
                    variant="warning"
                    size="lg"
                    block
                    @click="showTransfer = true"
                >
                    <template #icon>
                        <span>🎁</span>
                    </template>
                    Send Coins
                </BaseButton>
            </div>

            <!-- Coin Exchange Panel -->
            <CoinExchange
                v-if="showBuyCoins || showSellCoins"
                :mode="showBuyCoins ? 'buy' : 'sell'"
                :wallet-balance="walletBalance"
                :coin-balance="coinBalance"
                :config="gatewayConfig"
                @close="
                    showBuyCoins = false;
                    showSellCoins = false;
                "
                @success="onCoinExchangeSuccess"
            />

            <!-- Coin Transfer Panel -->
            <CoinTransfer
                v-if="showTransfer"
                :coin-balance="coinBalance"
                @close="showTransfer = false"
                @success="onTransferSuccess"
            />

            <!-- Transaction History -->
            <TransactionList />

            <!-- Deposit Modal -->
            <DepositModal
                v-model="showDeposit"
                :config="gatewayConfig"
                @success="onDepositSuccess"
            />

            <!-- Withdraw Modal -->
            <WithdrawModal
                v-model="showWithdraw"
                :config="gatewayConfig"
                :wallet-balance="walletBalance"
                @success="onWithdrawSuccess"
            />
        </div>

        <!-- Toast Notifications -->
        <ToastContainer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useWalletStore } from "@/Stores/walletStore";
import BaseButton from "@/Components/UI/BaseButton.vue";
import DepositModal from "@/Components/Wallet/DepositModal.vue";
import WithdrawModal from "@/Components/Wallet/WithdrawModal.vue";
import CoinExchange from "@/Components/Wallet/CoinExchange.vue";
import CoinTransfer from "@/Components/Wallet/CoinTransfer.vue";
import TransactionList from "@/Components/Wallet/TransactionList.vue";
import { ToastContainer } from "@/Components/UI";
import { useNotifications } from "@/Composables/useNotifications";
import api from "@/Utils/api";

const walletStore = useWalletStore();

// Private channel notifications (coin transfers, etc.)
const currentUser = window.__BET4GAIN__?.user;
if (currentUser?.id) {
    useNotifications(currentUser.id);
}

const showDeposit = ref(false);
const showWithdraw = ref(false);
const showBuyCoins = ref(false);
const showSellCoins = ref(false);
const showTransfer = ref(false);
const paymentResult = ref(null);
const gatewayConfig = ref({
    gateways: [],
    default: "paystack",
    currency: "NGN",
    coin_rate: 1,
    sell_rate: 1,
    min_deposit: 500,
    max_deposit: 1000000,
    min_withdrawal: 1000,
    max_withdrawal: 500000,
    withdrawal_fee: 1,
});

const walletBalance = computed(() => walletStore.balance);
const coinBalance = computed(() => walletStore.coins);
const demoBalance = computed(() => walletStore.demoCoins);

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString("en-NG", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};

const fetchGatewayConfig = async () => {
    try {
        const response = await api.get("/wallet/gateways");
        gatewayConfig.value = response.data;
    } catch (e) {
        console.error("Failed to load gateway config:", e);
    }
};

const onDepositSuccess = () => {
    walletStore.fetchWallet();
};

const onWithdrawSuccess = () => {
    walletStore.fetchWallet();
    walletStore.fetchTransactions();
};

const onCoinExchangeSuccess = () => {
    walletStore.fetchWallet();
    walletStore.fetchTransactions();
    showBuyCoins.value = false;
    showSellCoins.value = false;
};

const onTransferSuccess = () => {
    walletStore.fetchWallet();
    walletStore.fetchTransactions();
};

onMounted(() => {
    walletStore.fetchWallet();
    walletStore.fetchTransactions();
    fetchGatewayConfig();

    // Check URL for payment result
    const params = new URLSearchParams(window.location.search);
    const payment = params.get("payment");
    if (payment) {
        paymentResult.value = payment;
        // Clean URL
        window.history.replaceState({}, "", window.location.pathname);
        // Refresh wallet data
        walletStore.fetchWallet();
    }
});
</script>
