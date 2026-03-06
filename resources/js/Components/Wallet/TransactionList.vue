<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl border border-surface-light-border dark:border-surface-dark-border shadow-card"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-6 py-4 border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2"
            >
                <span>📋</span> Transaction History
            </h3>

            <div class="flex items-center gap-2">
                <!-- Type Filter -->
                <select
                    v-model="filter.type"
                    @change="fetchPage(1)"
                    class="text-xs rounded-lg border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-700 dark:text-slate-300 px-2 py-1.5"
                >
                    <option value="">All Types</option>
                    <option value="deposit">Deposits</option>
                    <option value="withdrawal">Withdrawals</option>
                    <option value="purchase_coins">Coin Purchases</option>
                    <option value="sell_coins">Coin Sales</option>
                    <option value="bet">Bets</option>
                    <option value="win">Wins</option>
                    <option value="refund">Refunds</option>
                    <option value="bonus">Bonuses</option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="filter.status"
                    @change="fetchPage(1)"
                    class="text-xs rounded-lg border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-700 dark:text-slate-300 px-2 py-1.5"
                >
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="reversed">Reversed</option>
                </select>
            </div>
        </div>

        <!-- Transactions -->
        <div
            class="divide-y divide-surface-light-border dark:divide-surface-dark-border"
        >
            <template v-if="loading && transactions.length === 0">
                <div
                    v-for="i in 5"
                    :key="i"
                    class="px-6 py-4 flex items-center gap-4 animate-pulse"
                >
                    <div
                        class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700"
                    ></div>
                    <div class="flex-1 space-y-2">
                        <div
                            class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/3"
                        ></div>
                        <div
                            class="h-2 bg-slate-200 dark:bg-slate-700 rounded w-1/4"
                        ></div>
                    </div>
                    <div
                        class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-16"
                    ></div>
                </div>
            </template>

            <div
                v-else-if="transactions.length === 0"
                class="px-6 py-12 text-center text-slate-400"
            >
                <span class="text-3xl block mb-2">📭</span>
                No transactions yet
            </div>

            <div
                v-for="tx in transactions"
                :key="tx.id"
                class="px-6 py-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-surface-dark-alt transition"
            >
                <!-- Type Icon -->
                <div
                    :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center text-lg',
                        typeStyles[tx.type]?.bg ||
                            'bg-slate-100 dark:bg-slate-800',
                    ]"
                >
                    {{ typeStyles[tx.type]?.icon || "💱" }}
                </div>

                <!-- Details -->
                <div class="flex-1 min-w-0">
                    <div
                        class="text-sm font-medium text-slate-900 dark:text-white truncate"
                    >
                        {{ typeLabels[tx.type] || tx.type }}
                    </div>
                    <div class="text-xs text-slate-400 flex items-center gap-2">
                        <span>{{ formatDate(tx.created_at) }}</span>
                        <span v-if="tx.reference" class="truncate"
                            >·
                            {{ tx.reference }}
                        </span>
                    </div>
                </div>

                <!-- Amount -->
                <div class="text-right">
                    <div
                        :class="[
                            'text-sm font-semibold',
                            isCredit(tx.type)
                                ? 'text-game-green'
                                : 'text-game-red',
                        ]"
                    >
                        {{ isCredit(tx.type) ? "+" : "-" }}₦{{
                            Number(tx.amount).toLocaleString()
                        }}
                    </div>
                    <span
                        :class="[
                            'text-xs px-1.5 py-0.5 rounded-full font-medium',
                            statusClasses[tx.status] ||
                                'bg-slate-100 text-slate-600',
                        ]"
                    >
                        {{ tx.status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="meta.last_page > 1"
            class="flex items-center justify-between px-6 py-3 border-t border-surface-light-border dark:border-surface-dark-border"
        >
            <button
                :disabled="meta.current_page <= 1"
                @click="fetchPage(meta.current_page - 1)"
                class="text-sm text-primary-500 hover:text-primary-600 disabled:text-slate-300 disabled:cursor-not-allowed transition"
            >
                ← Previous
            </button>
            <span class="text-xs text-slate-400">
                Page {{ meta.current_page }} of {{ meta.last_page }}
            </span>
            <button
                :disabled="meta.current_page >= meta.last_page"
                @click="fetchPage(meta.current_page + 1)"
                class="text-sm text-primary-500 hover:text-primary-600 disabled:text-slate-300 disabled:cursor-not-allowed transition"
            >
                Next →
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import api from "@/Utils/api";

const transactions = ref([]);
const loading = ref(false);
const filter = reactive({ type: "", status: "" });
const meta = reactive({
    current_page: 1,
    last_page: 1,
});

const typeLabels = {
    deposit: "Deposit",
    withdrawal: "Withdrawal",
    purchase_coins: "Coin Purchase",
    sell_coins: "Coin Sale",
    bet: "Bet Placed",
    win: "Bet Win",
    refund: "Refund",
    bonus: "Bonus",
};

const typeStyles = {
    deposit: { icon: "💳", bg: "bg-green-100 dark:bg-green-900/30" },
    withdrawal: { icon: "🏦", bg: "bg-blue-100 dark:bg-blue-900/30" },
    purchase_coins: { icon: "🪙", bg: "bg-amber-100 dark:bg-amber-900/30" },
    sell_coins: { icon: "💵", bg: "bg-purple-100 dark:bg-purple-900/30" },
    bet: { icon: "🎰", bg: "bg-red-100 dark:bg-red-900/30" },
    win: { icon: "🏆", bg: "bg-green-100 dark:bg-green-900/30" },
    refund: { icon: "↩️", bg: "bg-orange-100 dark:bg-orange-900/30" },
    bonus: { icon: "🎁", bg: "bg-pink-100 dark:bg-pink-900/30" },
};

const statusClasses = {
    completed:
        "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400",
    pending:
        "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400",
    failed: "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400",
    reversed:
        "bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400",
};

const creditTypes = ["deposit", "win", "refund", "bonus", "sell_coins"];

const isCredit = (type) => creditTypes.includes(type);

const formatDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleDateString("en-NG", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const fetchPage = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page };
        if (filter.type) params.type = filter.type;
        if (filter.status) params.status = filter.status;

        const response = await api.get("/wallet/transactions", { params });
        transactions.value = response.data.data || [];
        meta.current_page = response.data.current_page || 1;
        meta.last_page = response.data.last_page || 1;
    } catch (e) {
        console.error("Failed to load transactions:", e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => fetchPage(1));
</script>
