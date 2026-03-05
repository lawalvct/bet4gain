<template>
    <nav
        class="fixed bottom-0 inset-x-0 z-50 bg-surface-light-card/95 dark:bg-surface-dark-card/95 backdrop-blur-xl border-t border-surface-light-border dark:border-surface-dark-border lg:hidden safe-area-bottom"
    >
        <div class="flex items-center justify-around h-16">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="$emit('tab-change', tab.id)"
                :class="[
                    'relative flex flex-col items-center justify-center gap-0.5 w-14 h-14 rounded-xl transition-all active:scale-90',
                    activeTab === tab.id
                        ? 'text-primary-500'
                        : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300',
                ]"
            >
                <!-- Active indicator dot -->
                <span
                    v-if="activeTab === tab.id"
                    class="absolute -top-1 w-1 h-1 rounded-full bg-primary-500"
                ></span>

                <span class="text-xl leading-none">{{ tab.icon }}</span>
                <span class="text-[10px] font-medium leading-tight">{{ tab.label }}</span>

                <!-- Badge -->
                <span
                    v-if="tab.badge"
                    class="absolute top-0.5 right-0.5 min-w-[16px] h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center"
                >{{ tab.badge > 99 ? '99+' : tab.badge }}</span>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    activeTab: { type: String, default: 'game' },
    unreadMessages: { type: Number, default: 0 },
});

defineEmits(['tab-change']);

const tabs = computed(() => [
    { id: 'game', icon: '🎮', label: 'Game' },
    { id: 'bet', icon: '💰', label: 'Bet' },
    { id: 'chat', icon: '💬', label: 'Chat', badge: props.unreadMessages > 0 ? props.unreadMessages : null },
    { id: 'leaderboard', icon: '🏆', label: 'Top' },
    { id: 'menu', icon: '☰', label: 'More' },
]);
</script>
