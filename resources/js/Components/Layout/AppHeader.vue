<template>
    <header
        class="sticky top-0 z-50 bg-surface-light-card/80 dark:bg-surface-dark-card/80 backdrop-blur-xl border-b border-surface-light-border dark:border-surface-dark-border safe-area-top"
    >
        <div
            class="max-w-[1920px] mx-auto px-3 sm:px-4 h-14 flex items-center justify-between"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2 group">
                    <span
                        class="text-2xl transition-transform group-hover:scale-110"
                        >🎮</span
                    >
                    <span
                        class="text-lg font-bold text-primary-500 hidden sm:inline"
                        >Bet4Gain</span
                    >
                </a>
            </div>

            <!-- Center: Online count -->
            <div class="flex items-center gap-4">
                <div
                    class="flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400"
                >
                    <span
                        class="w-2 h-2 rounded-full bg-game-green animate-pulse"
                    ></span>
                    <span>{{ onlineCount }} online</span>
                </div>
            </div>

            <!-- Right: Controls + Balance + User -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Sound Toggle -->
                <button
                    @click="sound.toggle()"
                    class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-surface-light dark:hover:bg-surface-dark transition"
                    :title="
                        sound.soundEnabled.value
                            ? 'Mute sounds'
                            : 'Unmute sounds'
                    "
                >
                    <svg
                        v-if="sound.soundEnabled.value"
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.536 8.464a5 5 0 010 7.072M17.95 6.05a8 8 0 010 11.9M11 5L6 9H2v6h4l5 4V5z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5v14a1 1 0 01-1.707.707L5.586 15z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"
                        />
                    </svg>
                </button>

                <!-- Theme Toggle -->
                <ThemeToggle />

                <!-- Balance (authenticated) -->
                <template v-if="user">
                    <a
                        href="/wallet"
                        class="hidden sm:flex items-center gap-2 bg-surface-light dark:bg-surface-dark rounded-xl px-3 py-1.5 border border-surface-light-border dark:border-surface-dark-border hover:border-primary-500/40 transition cursor-pointer"
                    >
                        <span class="text-xs text-slate-400">₦</span>
                        <span
                            class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white"
                            >{{ formatCurrency(walletBalance) }}</span
                        >
                    </a>
                    <a
                        href="/wallet"
                        class="hidden sm:flex items-center gap-2 bg-surface-light dark:bg-surface-dark rounded-xl px-3 py-1.5 border border-surface-light-border dark:border-surface-dark-border hover:border-primary-500/40 transition cursor-pointer"
                    >
                        <span class="text-xs text-amber-500">🪙</span>
                        <span
                            class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white"
                            >{{ formatCoins(coinBalance) }}</span
                        >
                    </a>

                    <!-- User Menu -->
                    <BaseDropdown position="bottom-end">
                        <template #trigger>
                            <button
                                class="flex items-center gap-2 rounded-xl px-2 py-1 hover:bg-surface-light dark:hover:bg-surface-dark transition"
                            >
                                <BaseAvatar
                                    :src="userAvatarSrc"
                                    :name="user.username"
                                    size="sm"
                                />
                                <span
                                    class="hidden sm:inline text-sm font-medium text-slate-700 dark:text-slate-300"
                                    >{{ user.username }}</span
                                >
                                <svg
                                    class="w-4 h-4 text-slate-400 hidden sm:block"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>
                        </template>

                        <!-- Mobile-only balance display -->
                        <div
                            class="sm:hidden px-4 py-2 border-b border-surface-light-border dark:border-surface-dark-border"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-slate-400">Balance</span>
                                <span
                                    class="font-semibold tabular-nums text-slate-900 dark:text-white"
                                    >₦{{ formatCurrency(walletBalance) }}</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between text-sm mt-1"
                            >
                                <span class="text-slate-400">Coins</span>
                                <span
                                    class="font-semibold tabular-nums text-amber-500"
                                    >🪙 {{ formatCoins(coinBalance) }}</span
                                >
                            </div>
                        </div>

                        <a
                            href="/wallet"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-surface-light dark:hover:bg-surface-dark transition"
                        >
                            <span>💰</span> <span>Wallet</span>
                        </a>
                        <a
                            href="/profile"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-surface-light dark:hover:bg-surface-dark transition"
                        >
                            <span>👤</span> <span>Profile</span>
                        </a>
                        <a
                            href="/history"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-surface-light dark:hover:bg-surface-dark transition"
                        >
                            <span>📊</span> <span>History</span>
                        </a>
                        <hr
                            class="border-surface-light-border dark:border-surface-dark-border my-1"
                        />
                        <button
                            @click="logout"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-surface-light dark:hover:bg-surface-dark transition"
                        >
                            <span>🚪</span> <span>Logout</span>
                        </button>
                    </BaseDropdown>
                </template>

                <!-- Guest -->
                <template v-else>
                    <span class="text-xs text-slate-400 hidden sm:inline"
                        >Guest Mode</span
                    >
                    <a
                        href="/login"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition active:scale-95"
                        >Sign In</a
                    >
                </template>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from "vue";
import ThemeToggle from "./ThemeToggle.vue";
import { BaseDropdown, BaseAvatar } from "@/Components/UI";
import { formatCurrency, formatCoins } from "@/Utils/formatters";
import { useSound } from "@/Composables/useSound";
import axios from "axios";

const props = defineProps({
    user: { type: Object, default: null },
    walletBalance: { type: Number, default: 0 },
    coinBalance: { type: Number, default: 0 },
    onlineCount: { type: Number, default: 0 },
});

const sound = useSound();

const userAvatarSrc = computed(() => {
    if (!props.user) return "";

    const raw = props.user.avatar_url || props.user.avatar;
    if (!raw) return "";

    const avatar = String(raw);
    if (avatar.startsWith("http")) return avatar;
    if (avatar.startsWith("/storage/")) return avatar;
    if (avatar.startsWith("storage/")) return `/${avatar}`;

    return `/storage/${avatar.replace(/^\/+/, "")}`;
});

const logout = async () => {
    try {
        await axios.post("/logout");
        window.location.href = "/";
    } catch {
        window.location.href = "/";
    }
};
</script>
