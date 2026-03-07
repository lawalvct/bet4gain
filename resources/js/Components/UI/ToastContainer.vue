<template>
    <Teleport to="body">
        <div class="toast-container top-right">
            <TransitionGroup
                enter-active-class="animate-slide-in-right"
                leave-active-class="animate-slide-out-right"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'pointer-events-auto flex items-start gap-3 min-w-[300px] max-w-[420px] p-4',
                        'bg-surface-light-card dark:bg-surface-dark-card',
                        'border border-surface-light-border dark:border-surface-dark-border',
                        'rounded-xl shadow-dropdown',
                        borderColorClass(toast.type),
                    ]"
                >
                    <!-- Icon -->
                    <span class="text-xl flex-shrink-0 mt-0.5">{{
                        iconForType(toast.type)
                    }}</span>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p
                            v-if="toast.title"
                            class="text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ toast.title }}
                        </p>
                        <p
                            class="text-sm text-slate-600 dark:text-slate-400"
                            :class="{ 'mt-0.5': toast.title }"
                        >
                            {{ toast.message }}
                        </p>
                    </div>

                    <!-- Close -->
                    <button
                        @click="removeToast(toast.id)"
                        class="flex-shrink-0 p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToast } from "@/Composables/useToast";

const { toasts, removeToast } = useToast();

const iconForType = (type) => {
    const icons = {
        success: "✅",
        error: "❌",
        warning: "⚠️",
        info: "ℹ️",
        win: "🎉",
        cashout: "💰",
        transfer: "🪙",
    };
    return icons[type] || "ℹ️";
};

const borderColorClass = (type) => {
    const colors = {
        success: "border-l-4 !border-l-game-green",
        error: "border-l-4 !border-l-game-red",
        warning: "border-l-4 !border-l-game-yellow",
        info: "border-l-4 !border-l-game-blue",
        win: "border-l-4 !border-l-game-green",
        cashout: "border-l-4 !border-l-primary-500",
        transfer: "border-l-4 !border-l-amber-500",
    };
    return colors[type] || "";
};
</script>
