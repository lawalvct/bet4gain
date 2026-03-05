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
        <main class="max-w-[1920px] mx-auto px-2 sm:px-4 py-2 pb-20 lg:pb-2">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-2 sm:gap-3">
                <!-- Left Column: Chat (hidden on mobile, shown via tab) -->
                <aside
                    :class="[
                        'lg:col-span-3 space-y-2',
                        mobileTab === 'chat'
                            ? 'block lg:block'
                            : 'hidden lg:block',
                    ]"
                >
                    <ChatBox />
                    <OnlineUsers :users="onlineUsers" />
                </aside>

                <!-- Center Column: Game + Bet Panel (always visible on desktop) -->
                <section
                    :class="[
                        'lg:col-span-6 space-y-2',
                        mobileTab === 'game' || mobileTab === 'bet'
                            ? 'block lg:block'
                            : 'hidden lg:block',
                    ]"
                >
                    <!-- Game History Rail -->
                    <GameHistory :history="gameStore.history" />

                    <!-- Game Canvas -->
                    <div
                        class="game-canvas-container aspect-[16/9] min-h-[240px]"
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
                    <BetPanel />

                    <!-- Between-rounds ad (mobile only) -->
                    <div class="lg:hidden">
                        <AdSlot placement="between-rounds" />
                    </div>
                </section>

                <!-- Right Column: Live Bets + Leaderboard -->
                <aside
                    :class="[
                        'lg:col-span-3 space-y-2',
                        mobileTab === 'leaderboard'
                            ? 'block lg:block'
                            : 'hidden lg:block',
                    ]"
                >
                    <LiveBets />
                    <LeaderboardPanel />
                    <AdSlot placement="sidebar" />
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
import { ref, onMounted, onBeforeUnmount } from "vue";
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
import AdSlot from "@/Components/Layout/AdSlot.vue";
import { usePresence } from "@/Composables/usePresence";
import { useGameStore } from "@/Stores/gameStore";
import { useUserStore } from "@/Stores/userStore";
import { storeToRefs } from "pinia";

// Mobile tab state
const mobileTab = ref("game");
const unreadMessages = ref(0);

// Online presence
const { onlineUsers, onlineCount } = usePresence();

// Stores
const gameStore = useGameStore();
const userStore = useUserStore();

// Convenience aliases for header (storeToRefs preserves reactivity)
const { user, walletBalance, coinBalance } = storeToRefs(userStore);

onMounted(async () => {
    // Load initial data
    await Promise.all([
        gameStore.fetchHistory(),
        gameStore.fetchCurrentState(),
    ]);

    // Subscribe to realtime events
    gameStore.initGameChannel();
});

onBeforeUnmount(() => {
    gameStore.leaveGameChannel();
});
</script>
