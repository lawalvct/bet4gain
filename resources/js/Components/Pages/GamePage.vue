<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark">
        <!-- Header -->
        <AppHeader
            :user="user"
            :wallet-balance="walletBalance"
            :coin-balance="coinBalance"
            :online-count="onlineCount"
        />

        <!-- Main Game Layout: 3-column on desktop, tabbed on mobile -->
        <main
            class="max-w-[1920px] mx-auto px-2 sm:px-4 py-2 pb-20 lg:py-1 lg:pb-1 lg:h-[calc(100vh-3.5rem)] lg:overflow-hidden"
        >
            <div
                class="grid grid-cols-1 lg:grid-cols-12 gap-2 sm:gap-3 lg:gap-2 lg:h-full"
            >
                <!-- Left Column: Chat (hidden on mobile, shown via tab) -->
                <aside
                    :class="[
                        'lg:col-span-3 space-y-2 lg:space-y-0 lg:flex lg:flex-col lg:gap-1 lg:min-h-0 lg:h-full',
                        mobileTab === 'chat'
                            ? 'block lg:block'
                            : 'hidden lg:block',
                    ]"
                >
                    <ChatBox class="lg:flex-1 lg:min-h-0" />
                    <OnlineUsers
                        :users="onlineUsers"
                        class="lg:flex-shrink-0"
                    />
                </aside>

                <!-- Center Column: Game + Bet Panel (always visible on desktop) -->
                <section
                    :class="[
                        'lg:col-span-6 space-y-2 lg:space-y-1 lg:overflow-y-auto lg:min-h-0 scrollbar-thin',
                        mobileTab === 'game' || mobileTab === 'bet'
                            ? 'block lg:block'
                            : 'hidden lg:block',
                    ]"
                >
                    <!-- Live Stats Bar -->
                    <LiveStatsBar />

                    <!-- Game History Rail -->
                    <GameHistory :history="gameStore.history" />

                    <!-- Game Canvas -->
                    <div
                        class="game-canvas-container aspect-[16/9] min-h-[240px] lg:min-h-[220px] lg:max-h-[52vh] lg:max-w-[calc(52vh*16/9)] lg:mx-auto lg:w-full"
                    >
                        <GameCanvas
                            :status="gameStore.status"
                            :current-multiplier="gameStore.currentMultiplier"
                            :crash-point="gameStore.crashPoint"
                            :countdown="gameStore.countdown"
                            :betting-countdown="gameStore.bettingCountdown"
                            :round-id="gameStore.roundId"
                            :hash="gameStore.roundHash"
                        />
                    </div>

                    <!-- Bet Panel -->
                    <BetPanel
                        :status="gameStore.status"
                        :current-multiplier="gameStore.currentMultiplier"
                        :can-bet="canPlaceBet"
                        @start-auto="onStartAuto"
                        @stop-auto="onStopAuto"
                    />

                    <!-- Banner ad -->
                    <AdSlot placement="banner" :ad="ads.banner" />

                    <!-- Between-rounds ad (mobile only) -->
                    <div class="lg:hidden">
                        <AdSlot
                            placement="between-rounds"
                            :ad="ads.betweenRounds"
                        />
                    </div>
                </section>

                <!-- Right Column: Live Bets + Leaderboard -->
                <aside
                    :class="[
                        'lg:col-span-3 space-y-2 lg:space-y-1 lg:overflow-y-auto lg:min-h-0 scrollbar-thin',
                        mobileTab === 'leaderboard'
                            ? 'block lg:block'
                            : 'hidden lg:block',
                    ]"
                >
                    <LiveBets :bets="gameStore.liveBets" />
                    <LeaderboardPanel />
                    <AdSlot placement="sidebar" :ad="ads.sidebar" />
                </aside>
            </div>
        </main>

        <!-- Mobile Bottom Navigation -->
        <MobileBottomNav
            :active-tab="mobileTab"
            :unread-messages="unreadMessages"
            @tab-change="mobileTab = $event"
        />

        <!-- Toast Notifications -->
        <ToastContainer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import AppHeader from "@/Components/Layout/AppHeader.vue";
import MobileBottomNav from "@/Components/Layout/MobileBottomNav.vue";
import { ToastContainer } from "@/Components/UI";
import GameCanvas from "@/Components/Game/GameCanvas.vue";
import GameHistory from "@/Components/Game/GameHistory.vue";
import BetPanel from "@/Components/Game/BetPanel.vue";
import LiveBets from "@/Components/Game/LiveBets.vue";
import ChatBox from "@/Components/Chat/ChatBox.vue";
import OnlineUsers from "@/Components/Chat/OnlineUsers.vue";
import LeaderboardPanel from "@/Components/Leaderboard/LeaderboardPanel.vue";
import LiveStatsBar from "@/Components/Stats/LiveStatsBar.vue";
import AdSlot from "@/Components/Layout/AdSlot.vue";
import { usePresence } from "@/Composables/usePresence";
import { useSound } from "@/Composables/useSound";
import { useGameStore } from "@/Stores/gameStore";
import { useUserStore } from "@/Stores/userStore";
import { useBetStore } from "@/Stores/betStore";
import { useWalletStore } from "@/Stores/walletStore";
import { storeToRefs } from "pinia";
import api from "@/Utils/api";

// Mobile tab state
const mobileTab = ref("game");
const unreadMessages = ref(0);
const ads = ref({
    sidebar: null,
    banner: null,
    betweenRounds: null,
});

// Online presence
const { onlineUsers, onlineCount } = usePresence();

// Stores
const gameStore = useGameStore();
const userStore = useUserStore();
const betStore = useBetStore();
const walletStore = useWalletStore();
const sound = useSound();

// Convenience aliases for header (storeToRefs preserves reactivity)
const { user } = storeToRefs(userStore);
const { balance: walletBalance, coins: coinBalance } = storeToRefs(walletStore);

const refreshHeaderBalances = () => {
    if (document.visibilityState === "hidden") {
        return;
    }

    walletStore.fetchWallet();
};

const fetchAds = async () => {
    try {
        const [sidebarRes, bannerRes, betweenRoundsRes] = await Promise.all([
            api.get("/ads/sidebar"),
            api.get("/ads/banner"),
            api.get("/ads/between-rounds"),
        ]);

        ads.value.sidebar = sidebarRes.data?.data || null;
        ads.value.banner = bannerRes.data?.data || null;
        ads.value.betweenRounds = betweenRoundsRes.data?.data || null;
    } catch {
        ads.value.sidebar = null;
        ads.value.banner = null;
        ads.value.betweenRounds = null;
    }
};

// ── Bet panel props ────────────────────────────────────────────────────────
const canPlaceBet = computed(() => {
    return ["waiting", "betting"].includes(gameStore.status);
});

// ── Bet event handlers ─────────────────────────────────────────────────────
// BetPanel now handles API calls directly via betStore + walletStore.
// These callbacks are kept for any extra parent-level side effects.
const onStartAuto = (config) => {
    betStore.startAutoBet(config);
};

const onStopAuto = () => {
    betStore.stopAutoBet();
};

// Clear bets when a new round starts (betting phase)
watch(
    () => gameStore.status,
    (newStatus, oldStatus) => {
        if (newStatus === "crashed" && oldStatus !== "crashed") {
            sound.crash();
        }

        if (newStatus === "waiting" && oldStatus === "crashed") {
            betStore.clearRound();
        }
    },
);

onMounted(async () => {
    // Load initial data
    await Promise.all([
        gameStore.fetchHistory(),
        gameStore.fetchCurrentState(),
        walletStore.fetchWallet(),
        userStore.fetchUser(),
        fetchAds(),
    ]);

    // Subscribe to realtime events
    gameStore.initGameChannel();

    // Keep balances fresh when returning from payment gateway/tab switches
    window.addEventListener("focus", refreshHeaderBalances);
    document.addEventListener("visibilitychange", refreshHeaderBalances);
});

onBeforeUnmount(() => {
    window.removeEventListener("focus", refreshHeaderBalances);
    document.removeEventListener("visibilitychange", refreshHeaderBalances);
    gameStore.leaveGameChannel();
});
</script>
