<template>
    <BaseModal v-model="show" title="Deposit Funds" size="md" @close="close">
        <div class="space-y-5">
            <!-- Gateway Selection -->
            <div v-if="config.gateways?.length > 1">
                <label
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                    >Payment Method</label
                >
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="gw in config.gateways"
                        :key="gw.id"
                        @click="gateway = gw.id"
                        :class="[
                            'p-3 rounded-xl border-2 text-center font-medium transition',
                            gateway === gw.id
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400'
                                : 'border-surface-light-border dark:border-surface-dark-border text-slate-600 dark:text-slate-400 hover:border-slate-300',
                        ]"
                    >
                        {{ gw.name }}
                    </button>
                </div>
            </div>

            <!-- Amount Input -->
            <div>
                <label
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                    >Amount (₦)</label
                >
                <BaseInput
                    v-model="amount"
                    type="number"
                    :placeholder="`Min ₦${config.min_deposit || 500}`"
                    :min="config.min_deposit || 500"
                    :max="config.max_deposit || 1000000"
                    block
                >
                    <template #prefix>₦</template>
                </BaseInput>
                <p class="text-xs text-slate-500 mt-1">
                    Min: ₦{{ (config.min_deposit || 500).toLocaleString() }} ·
                    Max: ₦{{ (config.max_deposit || 1000000).toLocaleString() }}
                </p>
            </div>

            <!-- Quick Amounts -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="preset in presets"
                    :key="preset"
                    @click="amount = preset"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium border border-surface-light-border dark:border-surface-dark-border hover:border-primary-400 hover:text-primary-500 transition text-slate-600 dark:text-slate-400"
                >
                    ₦{{ preset.toLocaleString() }}
                </button>
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
                    Pay ₦{{ Number(amount || 0).toLocaleString() }}
                </BaseButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import BaseModal from "@/Components/UI/BaseModal.vue";
import BaseButton from "@/Components/UI/BaseButton.vue";
import BaseInput from "@/Components/UI/BaseInput.vue";
import { useWalletStore } from "@/Stores/walletStore";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    config: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["update:modelValue", "success"]);

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit("update:modelValue", v),
});

const walletStore = useWalletStore();

const amount = ref("");
const gateway = ref("");
const loading = ref(false);
const error = ref("");

const presets = [500, 1000, 2000, 5000, 10000, 20000, 50000];

const isValid = computed(() => {
    const num = Number(amount.value);
    return (
        num >= (props.config.min_deposit || 500) &&
        num <= (props.config.max_deposit || 1000000)
    );
});

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            error.value = "";
            gateway.value = props.config.default || "paystack";
        }
    },
);

const submit = async () => {
    if (!isValid.value) return;
    loading.value = true;
    error.value = "";

    try {
        const result = await walletStore.deposit({
            amount: Number(amount.value),
            gateway: gateway.value,
        });

        if (result?.authorization_url) {
            // Redirect to payment page
            window.location.href = result.authorization_url;
        } else {
            error.value = "Failed to get payment URL. Please try again.";
        }
    } catch (e) {
        error.value =
            e.response?.data?.message || "Deposit failed. Please try again.";
    } finally {
        loading.value = false;
    }
};

const close = () => {
    show.value = false;
    amount.value = "";
    error.value = "";
};
</script>
