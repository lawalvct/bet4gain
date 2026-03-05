import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/Utils/api";

export const useWalletStore = defineStore("wallet", () => {
    // State
    const wallet = ref(null);
    const coinBalance = ref(null);
    const transactions = ref([]);
    const loading = ref(false);

    // Computed
    const balance = computed(() => wallet.value?.balance || 0);
    const coins = computed(() => coinBalance.value?.balance || 0);
    const demoCoins = computed(() => coinBalance.value?.demo_balance || 0);

    // Actions
    const fetchWallet = async () => {
        loading.value = true;
        try {
            const response = await api.get("/api/wallet");
            wallet.value = response.data.wallet;
            coinBalance.value = response.data.coins;
        } catch (error) {
            console.error("Failed to fetch wallet:", error);
        } finally {
            loading.value = false;
        }
    };

    const fetchTransactions = async (page = 1) => {
        try {
            const response = await api.get("/api/wallet/transactions", {
                params: { page },
            });
            transactions.value = response.data.data;
            return response.data;
        } catch (error) {
            console.error("Failed to fetch transactions:", error);
        }
    };

    const updateBalance = (newBalance) => {
        if (wallet.value) {
            wallet.value.balance = newBalance;
        }
    };

    const updateCoinBalance = (newBalance) => {
        if (coinBalance.value) {
            coinBalance.value.balance = newBalance;
        }
    };

    const deposit = async (data) => {
        try {
            const response = await api.post("/api/wallet/deposit", data);
            return response.data;
        } catch (error) {
            throw error;
        }
    };

    const withdraw = async (data) => {
        try {
            const response = await api.post("/api/wallet/withdraw", data);
            return response.data;
        } catch (error) {
            throw error;
        }
    };

    const purchaseCoins = async (data) => {
        try {
            const response = await api.post("/api/wallet/purchase-coins", data);
            if (response.data.coins) {
                coinBalance.value = response.data.coins;
            }
            return response.data;
        } catch (error) {
            throw error;
        }
    };

    return {
        // State
        wallet,
        coinBalance,
        transactions,
        loading,
        // Computed
        balance,
        coins,
        demoCoins,
        // Actions
        fetchWallet,
        fetchTransactions,
        updateBalance,
        updateCoinBalance,
        deposit,
        withdraw,
        purchaseCoins,
    };
});
