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
                        >📧</span
                    >
                    <h1 class="text-2xl font-bold text-primary-500 mt-2">
                        Bet4Gain
                    </h1>
                </a>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Verify your email address
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
                        >A new verification link has been sent to your email
                        address.</span
                    >
                </div>

                <!-- Error message -->
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

                <div class="text-center space-y-4">
                    <div
                        class="w-16 h-16 mx-auto rounded-full bg-primary-500/10 flex items-center justify-center"
                    >
                        <svg
                            class="w-8 h-8 text-primary-500"
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
                    </div>

                    <p
                        class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed"
                    >
                        Thanks for signing up! Before getting started, could you
                        verify your email address by clicking on the link we
                        just emailed to you?
                    </p>

                    <p class="text-slate-500 dark:text-slate-400 text-xs">
                        If you didn't receive the email, click the button below
                        to request another.
                    </p>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <BaseButton
                        variant="primary"
                        size="lg"
                        block
                        :loading="loading"
                        @click="resendVerification"
                    >
                        Resend Verification Email
                    </BaseButton>

                    <BaseButton variant="ghost" size="lg" @click="handleLogout">
                        Log Out
                    </BaseButton>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
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
import { ref } from "vue";
import axios from "axios";
import { BaseButton } from "@/Components/UI";

const loading = ref(false);
const status = ref("");
const globalError = ref("");

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

const resendVerification = async () => {
    loading.value = true;
    globalError.value = "";
    status.value = "";

    try {
        await axios.post(
            "/email/verification-notification",
            {},
            {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
            },
        );
        status.value = "sent";
    } catch (error) {
        if (error.response?.status === 429) {
            globalError.value =
                "Too many requests. Please wait before trying again.";
        } else {
            globalError.value =
                "Could not send verification email. Please try again.";
        }
    } finally {
        loading.value = false;
    }
};

const handleLogout = async () => {
    try {
        await axios.post(
            "/logout",
            {},
            {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
            },
        );
        window.location.href = "/";
    } catch {
        window.location.href = "/";
    }
};
</script>
