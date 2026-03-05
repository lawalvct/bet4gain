<template>
    <div class="flex items-center justify-center" :class="containerClass">
        <svg
            class="animate-spin"
            :class="[sizeClasses, colorClasses]"
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
        <span v-if="label" class="ml-2 text-sm text-slate-400">{{
            label
        }}</span>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    size: {
        type: String,
        default: "md",
        validator: (v) => ["xs", "sm", "md", "lg", "xl"].includes(v),
    },
    color: { type: String, default: "primary" },
    label: { type: String, default: "" },
    containerClass: { type: String, default: "" },
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: "w-3 h-3",
        sm: "w-4 h-4",
        md: "w-6 h-6",
        lg: "w-8 h-8",
        xl: "w-12 h-12",
    };
    return sizes[props.size];
});

const colorClasses = computed(() => {
    const colors = {
        primary: "text-primary-500",
        white: "text-white",
        slate: "text-slate-400",
    };
    return colors[props.color] || "text-primary-500";
});
</script>
