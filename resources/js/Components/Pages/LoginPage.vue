<template>
    <div
        class="min-h-screen flex items-center justify-center bg-surface-light dark:bg-surface-dark px-4"
    >
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary-500">🎮 Bet4Gain</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Sign in to your account
                </p>
            </div>

            <!-- Login Form Card -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 sm:p-8 border border-surface-light-border dark:border-surface-dark-border"
            >
                <form @submit.prevent="handleLogin" class="space-y-5">
                    <!-- Email / Username -->
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                            >Email or Username</label
                        >
                        <input
                            v-model="form.email"
                            type="text"
                            required
                            class="w-full px-4 py-3 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="Enter email or username"
                        />
                        <p
                            v-if="errors.email"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"
                            >Password</label
                        >
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-3 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            placeholder="Enter password"
                        />
                        <p
                            v-if="errors.password"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.password }}
                        </p>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
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
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3 px-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!loading">Sign In</span>
                        <span v-else>Signing in...</span>
                    </button>
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
                        class="flex items-center justify-center gap-2 py-3 px-4 border border-surface-light-border dark:border-surface-dark-border rounded-xl hover:bg-slate-50 dark:hover:bg-surface-dark transition text-slate-700 dark:text-slate-300"
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
                        class="flex items-center justify-center gap-2 py-3 px-4 border border-surface-light-border dark:border-surface-dark-border rounded-xl hover:bg-slate-50 dark:hover:bg-surface-dark transition text-slate-700 dark:text-slate-300"
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
                >
                    ← Play as Guest
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import api from "@/Utils/api";

const loading = ref(false);
const errors = reactive({});

const form = reactive({
    email: "",
    password: "",
    remember: false,
});

const handleLogin = async () => {
    loading.value = true;
    Object.keys(errors).forEach((k) => delete errors[k]);

    try {
        await api.post("/login", form);
        window.location.href = "/";
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(errors, error.response.data.errors);
        }
    } finally {
        loading.value = false;
    }
};
</script>
