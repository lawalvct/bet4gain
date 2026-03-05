<template>
    <div
        class="min-h-screen flex items-center justify-center bg-surface-light dark:bg-surface-dark px-4 py-8"
    >
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="/" class="inline-block group">
                    <span
                        class="text-4xl block transition-transform group-hover:scale-110"
                        >🔒</span
                    >
                    <h1 class="text-2xl font-bold text-primary-500 mt-2">
                        Bet4Gain
                    </h1>
                </a>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Set your new password
                </p>
            </div>

            <!-- Card -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 sm:p-8 border border-surface-light-border dark:border-surface-dark-border"
            >
                <!-- Success message -->
                <div
                    v-if="status === 'reset'"
                    class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-500 text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span
                        >Password reset successfully! Redirecting to
                        login...</span
                    >
                </div>

                <!-- Global error -->
                <div
                    v-if="globalError"
                    class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ globalError }}</span>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-5">
                    <!-- Email -->
                    <BaseInput
                        v-model="form.email"
                        type="email"
                        label="Email Address"
                        placeholder="Enter your email"
                        :error="errors.email?.[0] || errors.email"
                        required
                        autocomplete="email"
                        size="lg"
                    >
                        <template #prefix>
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
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <!-- Password -->
                    <BaseInput
                        v-model="form.password"
                        type="password"
                        label="New Password"
                        placeholder="Enter new password"
                        :error="errors.password?.[0] || errors.password"
                        hint="At least 8 characters"
                        required
                        autocomplete="new-password"
                        size="lg"
                    >
                        <template #prefix>
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
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <!-- Password Confirmation -->
                    <BaseInput
                        v-model="form.password_confirmation"
                        type="password"
                        label="Confirm Password"
                        placeholder="Confirm new password"
                        :error="
                            passwordMismatch ? 'Passwords do not match' : ''
                        "
                        required
                        autocomplete="new-password"
                        size="lg"
                    >
                        <template #prefix>
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
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <BaseButton
                        type="submit"
                        variant="primary"
                        size="lg"
                        block
                        :loading="loading"
                    >
                        Reset Password
                    </BaseButton>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 space-y-3">
                <p class="text-slate-500 dark:text-slate-400">
                    Remember your password?
                    <a
                        href="/login"
                        class="text-primary-500 hover:text-primary-400 font-medium transition"
                        >Sign In</a
                    >
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from "vue";
import axios from "axios";
import { BaseInput, BaseButton } from "@/Components/UI";

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: "" },
});

const loading = ref(false);
const status = ref("");
const globalError = ref("");
const errors = reactive({});

const form = reactive({
    email: props.email,
    password: "",
    password_confirmation: "",
});

const passwordMismatch = computed(() => {
    return (
        form.password_confirmation.length > 0 &&
        form.password !== form.password_confirmation
    );
});

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

const handleSubmit = async () => {
    if (passwordMismatch.value) return;

    loading.value = true;
    globalError.value = "";
    status.value = "";
    Object.keys(errors).forEach((k) => delete errors[k]);

    try {
        await axios.post(
            "/reset-password",
            {
                token: props.token,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            },
            {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
            },
        );
        status.value = "reset";
        setTimeout(() => {
            window.location.href = "/login";
        }, 2000);
    } catch (error) {
        if (error.response?.status === 422) {
            const data = error.response.data;
            if (data.errors) {
                Object.assign(errors, data.errors);
            }
            if (data.message && !data.errors) {
                globalError.value = data.message;
            }
        } else if (error.response?.status === 429) {
            globalError.value =
                "Too many requests. Please wait before trying again.";
        } else {
            globalError.value =
                "An unexpected error occurred. Please try again.";
        }
    } finally {
        loading.value = false;
    }
};
</script>
