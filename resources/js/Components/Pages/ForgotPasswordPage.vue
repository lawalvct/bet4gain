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
                        >🔑</span
                    >
                    <h1 class="text-2xl font-bold text-primary-500 mt-2">
                        Bet4Gain
                    </h1>
                </a>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Reset your password
                </p>
            </div>

            <!-- Card -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 sm:p-8 border border-surface-light-border dark:border-surface-dark-border"
            >
                <!-- Success message -->
                <div
                    v-if="status === 'sent'"
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
                        >We've emailed your password reset link! Check your
                        inbox.</span
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

                <p class="text-slate-600 dark:text-slate-300 text-sm mb-5">
                    Enter your email address and we'll send you a link to reset
                    your password.
                </p>

                <form @submit.prevent="handleSubmit" class="space-y-5">
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

                    <BaseButton
                        type="submit"
                        variant="primary"
                        size="lg"
                        block
                        :loading="loading"
                    >
                        Send Reset Link
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
                <a
                    href="/"
                    class="inline-block text-sm text-slate-400 hover:text-primary-500 transition"
                    >← Back to Game</a
                >
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import axios from "axios";
import { BaseInput, BaseButton } from "@/Components/UI";

const loading = ref(false);
const status = ref("");
const globalError = ref("");
const errors = reactive({});

const form = reactive({
    email: "",
});

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

const handleSubmit = async () => {
    loading.value = true;
    globalError.value = "";
    status.value = "";
    Object.keys(errors).forEach((k) => delete errors[k]);

    try {
        await axios.post("/forgot-password", form, {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        });
        status.value = "sent";
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
