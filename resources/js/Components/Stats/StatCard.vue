<template>
    <div
        :class="[
            'bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border',
            size === 'sm' ? 'p-3' : 'p-4',
        ]"
    >
        <div class="flex items-center gap-2 mb-1">
            <span class="text-base">{{ icon }}</span>
            <span class="text-xs text-slate-500 font-medium truncate">{{
                label
            }}</span>
        </div>
        <div
            :class="[
                'font-bold tabular-nums truncate',
                size === 'sm' ? 'text-base' : 'text-xl',
                colorClass,
            ]"
        >
            {{ value }}
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, default: "📊" },
    color: { type: String, default: "" },
    size: { type: String, default: "md" },
});

const colorClass = computed(() => {
    switch (props.color) {
        case "green":
            return "text-game-green";
        case "red":
            return "text-game-red";
        case "purple":
            return "text-purple-500";
        case "amber":
            return "text-amber-500";
        default:
            return "text-slate-800 dark:text-white";
    }
});
</script>
