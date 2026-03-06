<template>
    <BaseModal v-model="show" title="Withdraw Funds" size="md" @close="close">
        <div class="space-y-5">
            <!-- Current Balance -->
            <div
                class="p-3 rounded-xl bg-slate-50 dark:bg-surface-dark-alt border border-surface-light-border dark:border-surface-dark-border"
            >
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    Available Balance
                </div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">
                    ₦{{ Number(walletBalance || 0).toLocaleString() }}
                </div>
            </div>

            <!-- Amount -->
            <div>
                <label
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >Amount (₦)</label
                >
                <BaseInput
                    v-model="form.amount"
                    type="number"
                    :placeholder="`Min ₦${config.min_withdrawal || 1000}`"
                    :min="config.min_withdrawal || 1000"
                    :max="config.max_withdrawal || 500000"
                    block
                >
                    <template #prefix>₦</template>
                </BaseInput>
                <p class="text-xs text-slate-500 mt-1">
                    Fee: {{ config.withdrawal_fee || 1 }}% · You'll receive: ₦{{
                        netAmount.toLocaleString()
                    }}
                </p>
            </div>

            <!-- Bank Details -->
            <div>
                <label
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >Bank</label
                >
                <select
                    v-model="form.bank_code"
                    class="w-full rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-primary-500/30 focus:border-transparent transition"
                    @change="onBankChange"
                >
                    <option value="">Select a bank</option>
                    <option
                        v-for="bank in banks"
                        :key="bank.code"
                        :value="bank.code"
                    >
                        {{ bank.name }}
                    </option>
                </select>
            </div>

            <div>
                <label
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >Account Number</label
                >
                <BaseInput
                    v-model="form.account_number"
                    type="text"
                    placeholder="0123456789"
                    maxlength="10"
                    block
                    @blur="resolveAccount"
                />
            </div>

            <!-- Resolved Account Name -->
            <div
                v-if="resolvedName"
                class="p-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700"
            >
                <div class="text-xs text-green-600 dark:text-green-400">
                    Account Name
                </div>
                <div class="font-semibold text-green-800 dark:text-green-300">
                    {{ resolvedName }}
                </div>
            </div>

            <div
                v-if="resolving"
                class="text-sm text-slate-400 flex items-center gap-2"
            >
                <svg
                    class="animate-spin w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    />
                </svg>
                Verifying account...
            </div>

            <!-- Error -->
            <p v-if="error" class="text-sm text-game-red">{{ error }}</p>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <BaseButton variant="ghost" @click="close">Cancel</BaseButton>
                <BaseButton
                    variant="primary"
                    :loading="loading"
                    :disabled="!isValid"
                    @click="submit"
                >
                    Withdraw ₦{{ Number(form.amount || 0).toLocaleString() }}
                </BaseButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import BaseModal from "@/Components/UI/BaseModal.vue";
import BaseButton from "@/Components/UI/BaseButton.vue";
import BaseInput from "@/Components/UI/BaseInput.vue";
import { useWalletStore } from "@/Stores/walletStore";
import api from "@/Utils/api";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    config: { type: Object, default: () => ({}) },
    walletBalance: { type: Number, default: 0 },
});

const emit = defineEmits(["update:modelValue", "success"]);

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

const walletStore = useWalletStore();

const form = ref({
    amount: "",
    bank_code: "",
    account_number: "",
    account_name: "",
});

const banks = ref([]);
const resolvedName = ref("");
const resolving = ref(false);
const loading = ref(false);
const error = ref("");

const netAmount = computed(() => {
    const amt = Number(form.value.amount) || 0;
    const fee = (amt * (props.config.withdrawal_fee || 1)) / 100;
    return Math.max(0, amt - fee);
});

const isValid = computed(() => {
    const amt = Number(form.value.amount);
    return (
        amt >= (props.config.min_withdrawal || 1000) &&
        amt <= (props.config.max_withdrawal || 500000) &&
        amt <= props.walletBalance &&
        form.value.bank_code &&
        form.value.account_number?.length === 10 &&
        form.value.account_name
    );
});

const fetchBanks = async () => {
    try {
        const response = await api.get("/wallet/banks");
        banks.value = response.data.data || [];
    } catch (e) {
        console.error("Failed to load banks:", e);
    }
};

const onBankChange = () => {
    resolvedName.value = "";
    form.value.account_name = "";
    if (form.value.account_number?.length === 10) {
        resolveAccount();
    }
};

const resolveAccount = async () => {
    if (form.value.account_number?.length !== 10 || !form.value.bank_code) {
        return;
    }

    resolving.value = true;
    resolvedName.value = "";
    form.value.account_name = "";

    try {
        const response = await api.post("/wallet/resolve-account", {
            account_number: form.value.account_number,
            bank_code: form.value.bank_code,
        });
        const name = response.data.data?.account_name || "";
        resolvedName.value = name;
        form.value.account_name = name;
    } catch (e) {
        error.value = "Could not verify account. Please check details.";
    } finally {
        resolving.value = false;
    }
};

const submit = async () => {
    if (!isValid.value) return;
    loading.value = true;
    error.value = "";

    try {
        await walletStore.withdraw({
            amount: Number(form.value.amount),
            gateway: props.config.default || "paystack",
            account_number: form.value.account_number,
            bank_code: form.value.bank_code,
            account_name: form.value.account_name,
        });

        emit("success");
        close();
    } catch (e) {
        error.value =
            e.response?.data?.message || "Withdrawal failed. Please try again.";
    } finally {
        loading.value = false;
    }
};

const close = () => {
    show.value = false;
    form.value = {
        amount: "",
        bank_code: "",
        account_number: "",
        account_name: "",
    };
    resolvedName.value = "";
    error.value = "";
};

watch(
    () => props.modelValue,
    (v) => {
        if (v && banks.value.length === 0) {
            fetchBanks();
        }
    },
);
</script>
