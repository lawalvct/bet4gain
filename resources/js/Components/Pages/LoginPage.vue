<template>
    <div
        class="min-h-screen flex items-center justify-center bg-surface-light dark:bg-surface-dark px-4 py-8"
    >
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="/" class="inline-block group">
                    <img
                        v-if="siteLogoUrl"
                        :src="siteLogoUrl"
                        :alt="siteName"
                        class="h-14 w-auto max-w-55 object-contain mx-auto transition-transform group-hover:scale-105"
                    />
                    <span
                        v-else
                        class="text-4xl block transition-transform group-hover:scale-110"
                        >🎮</span
                    >
                    <h1 class="text-2xl font-bold text-primary-500 mt-2">
                        {{ siteName }}
                    </h1>
                </a>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Sign in to your account
                </p>
            </div>

            <!-- Login Form Card -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 sm:p-8 border border-surface-light-border dark:border-surface-dark-border"
            >
                <!-- Global error message -->
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

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <!-- Email / Username -->
                    <BaseInput
                        v-model="form.email"
                        label="Email or Username"
                        placeholder="Enter email or username"
                        :error="errors.email?.[0] || errors.email"
                        required
                        autocomplete="username"
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
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <!-- Password -->
                    <BaseInput
                        v-model="form.password"
                        type="password"
                        label="Password"
                        placeholder="Enter password"
                        :error="errors.password?.[0] || errors.password"
                        required
                        autocomplete="current-password"
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

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between">
                        <label
                            class="flex items-center gap-2 cursor-pointer select-none"
                        >
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded border-slate-300 dark:border-slate-600 text-primary-500 focus:ring-primary-500"
                            />
                            <span
                                class="text-sm text-slate-600 dark:text-slate-400"
                                >Remember me</span
                            >
                        </label>
                        <a
                            href="/forgot-password"
                            class="text-sm text-primary-500 hover:text-primary-400 transition"
                            >Forgot password?</a
                        >
                    </div>

                    <!-- Submit -->
                    <BaseButton
                        type="submit"
                        variant="primary"
                        size="lg"
                        block
                        :loading="loading"
                    >
                        Sign In
                    </BaseButton>
                </form>

                <!-- Divider -->
                <div class="flex items-center gap-3 my-6">
                    <div
                        class="flex-1 border-t border-surface-light-border dark:border-surface-dark-border"
                    ></div>
                    <span class="text-sm text-slate-400">or continue with</span>
                    <div
                        class="flex-1 border-t border-surface-light-border dark:border-surface-dark-border"
                    ></div>
                </div>

                <!-- Social Login -->
                <div class="grid grid-cols-2 gap-3">
                    <a
                        href="/auth/google"
                        class="flex items-center justify-center gap-2 py-3 px-4 border border-surface-light-border dark:border-surface-dark-border rounded-xl hover:bg-slate-50 dark:hover:bg-surface-dark transition text-slate-700 dark:text-slate-300 active:scale-95"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path
                                fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                            />
                            <path
                                fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            />
                            <path
                                fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            />
                            <path
                                fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            />
                        </svg>
                        Google
                    </a>
                    <a
                        href="/auth/github"
                        class="flex items-center justify-center gap-2 py-3 px-4 border border-surface-light-border dark:border-surface-dark-border rounded-xl hover:bg-slate-50 dark:hover:bg-surface-dark transition text-slate-700 dark:text-slate-300 active:scale-95"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                            />
                        </svg>
                        GitHub
                    </a>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="text-center mt-6 space-y-3">
                <p class="text-slate-500 dark:text-slate-400">
                    Don't have an account?
                    <a
                        href="/register"
                        class="text-primary-500 hover:text-primary-400 font-medium transition"
                        >Sign Up</a
                    >
                </p>
                <a
                    href="/"
                    class="inline-block text-sm text-slate-400 hover:text-primary-500 transition"
                    >← Play as Guest</a
                >
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, reactive } from "vue";
import axios from "axios";
import { BaseInput, BaseButton } from "@/Components/UI";

const loading = ref(false);
const globalError = ref("");
const errors = reactive({});

const form = reactive({
    email: "",
    password: "",
    remember: false,
});

const appData = window.__BET4GAIN__ || {};

const siteName = computed(
    () => appData.siteName || appData.appName || "Bet4Gain",
);

const siteLogoUrl = computed(() => {
    const raw = appData.siteLogo;
    if (!raw) return "";

    const logo = String(raw);
    if (logo.startsWith("http")) return logo;
    if (logo.startsWith("/storage/")) return logo;
    if (logo.startsWith("storage/")) return `/${logo}`;

    return `/storage/${logo.replace(/^\/+/, "")}`;
});

const handleLogin = async () => {
    loading.value = true;
    globalError.value = "";
    Object.keys(errors).forEach((k) => delete errors[k]);

    try {
        await axios.post("/login", form, {
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
                Accept: "application/json",
            },
        });
        window.location.href = "/";
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
                "Too many login attempts. Please try again later.";
        } else {
            globalError.value =
                "An unexpected error occurred. Please try again.";
        }
    } finally {
        loading.value = false;
    }
};
</script>
