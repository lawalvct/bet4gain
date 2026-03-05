<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="fixed inset-0 z-50"
            @click.self="$emit('close')"
        >
            <div
                :style="popoverStyle"
                class="absolute bg-surface-light-card dark:bg-surface-dark-card border border-surface-light-border dark:border-surface-dark-border rounded-xl shadow-modal p-4 w-64 z-[100]"
                @click.stop
            >
                <!-- Loading -->
                <div
                    v-if="loading"
                    class="flex items-center justify-center py-6"
                >
                    <svg
                        class="animate-spin w-6 h-6 text-primary-500"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                </div>

                <!-- Profile -->
                <div v-else-if="profile" class="space-y-3">
                    <!-- Avatar + Name -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-500/10 flex items-center justify-center overflow-hidden border-2 border-surface-light-border dark:border-surface-dark-border"
                        >
                            <img
                                v-if="profile.avatar_url"
                                :src="profile.avatar_url"
                                :alt="profile.username"
                                class="w-full h-full object-cover"
                            />
                            <span
                                v-else
                                class="text-lg font-bold text-primary-500"
                            >
                                {{
                                    profile.username?.[0]?.toUpperCase() || "?"
                                }}
                            </span>
                        </div>
                        <div>
                            <div
                                class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5"
                            >
                                {{ profile.username }}
                                <span
                                    v-if="profile.role === 'admin'"
                                    class="text-[10px] px-1.5 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium"
                                    >ADMIN</span
                                >
                                <span
                                    v-else-if="profile.role === 'moderator'"
                                    class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium"
                                    >MOD</span
                                >
                            </div>
                            <div class="text-xs text-slate-400">
                                Joined {{ profile.joined }}
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            class="bg-slate-50 dark:bg-surface-dark-alt rounded-lg p-2 text-center"
                        >
                            <div
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                {{ profile.total_bets }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Total Bets
                            </div>
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-surface-dark-alt rounded-lg p-2 text-center"
                        >
                            <div class="text-sm font-bold text-game-green">
                                {{ profile.win_rate }}%
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Win Rate
                            </div>
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-surface-dark-alt rounded-lg p-2 text-center"
                        >
                            <div
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                {{ profile.wins }}
                            </div>
                            <div class="text-[10px] text-slate-400">Wins</div>
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-surface-dark-alt rounded-lg p-2 text-center"
                        >
                            <div class="text-sm font-bold text-amber-500">
                                {{
                                    Number(
                                        profile.best_multiplier || 0,
                                    ).toFixed(2)
                                }}x
                            </div>
                            <div class="text-[10px] text-slate-400">
                                Best Multi
                            </div>
                        </div>
                    </div>

                    <!-- Mod Actions -->
                    <div
                        v-if="isModerator && profile.role !== 'admin'"
                        class="flex gap-2 pt-1 border-t border-surface-light-border dark:border-surface-dark-border"
                    >
                        <button
                            @click="$emit('mute', profile.id)"
                            class="flex-1 text-xs py-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/50 transition font-medium"
                        >
                            🔇 Mute
                        </button>
                    </div>
                </div>

                <!-- Error -->
                <div v-else class="text-center text-sm text-slate-400 py-4">
                    Failed to load profile
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import api from "@/Utils/api";

const props = defineProps({
    visible: { type: Boolean, default: false },
    userId: { type: Number, default: null },
    anchorX: { type: Number, default: 0 },
    anchorY: { type: Number, default: 0 },
    isModerator: { type: Boolean, default: false },
});

defineEmits(["close", "mute"]);

const profile = ref(null);
const loading = ref(false);

const popoverStyle = computed(() => {
    // Position relative to anchor, adjust if near viewport edge
    let x = props.anchorX;
    let y = props.anchorY;

    // Ensure it doesn't go off-screen right
    if (x + 264 > window.innerWidth) {
        x = window.innerWidth - 274;
    }
    // Ensure it doesn't go off-screen bottom
    if (y + 300 > window.innerHeight) {
        y = y - 300;
    }

    return {
        left: `${Math.max(10, x)}px`,
        top: `${Math.max(10, y)}px`,
    };
});

watch(
    () => [props.visible, props.userId],
    async ([visible, userId]) => {
        if (!visible || !userId) {
            profile.value = null;
            return;
        }

        loading.value = true;
        try {
            const response = await api.get(`/api/chat/user/${userId}`);
            profile.value = response.data.data;
        } catch (e) {
            console.error("Failed to load profile:", e);
            profile.value = null;
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);
</script>
