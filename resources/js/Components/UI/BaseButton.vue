<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
            sizeClasses,
            variantClasses,
            roundedClasses,
            { 'opacity-50 cursor-not-allowed': disabled || loading },
            { 'btn-pulse': pulse && !disabled },
        ]"
        @click="$emit('click', $event)"
    >
        <!-- Loading Spinner -->
        <svg
            v-if="loading"
            class="animate-spin"
            :class="iconSize"
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

        <!-- Icon Slot -->
        <slot v-if="!loading" name="icon" />

        <!-- Label -->
        <span v-if="$slots.default"><slot /></span>
    </button>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    type: { type: String, default: "button" },
    variant: {
        type: String,
        default: "primary",
        validator: (v) =>
            [
                "primary",
                "secondary",
                "success",
                "danger",
                "ghost",
                "outline",
            ].includes(v),
    },
    size: {
        type: String,
        default: "md",
        validator: (v) => ["xs", "sm", "md", "lg", "xl"].includes(v),
    },
    rounded: {
        type: String,
        default: "xl",
        validator: (v) => ["md", "lg", "xl", "2xl", "full"].includes(v),
    },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    pulse: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

defineEmits(["click"]);

const sizeClasses = computed(() => {
    const sizes = {
        xs: "px-2 py-1 text-xs",
        sm: "px-3 py-1.5 text-sm",
        md: "px-4 py-2.5 text-sm",
        lg: "px-5 py-3 text-base",
        xl: "px-6 py-4 text-lg",
    };
    return [sizes[props.size], props.block ? "w-full" : ""].join(" ");
});

const iconSize = computed(() => {
    const sizes = {
        xs: "w-3 h-3",
        sm: "w-4 h-4",
        md: "w-4 h-4",
        lg: "w-5 h-5",
        xl: "w-6 h-6",
    };
    return sizes[props.size];
});

const roundedClasses = computed(() => `rounded-${props.rounded}`);

const variantClasses = computed(() => {
    const variants = {
        primary:
            "bg-primary-500 hover:bg-primary-600 text-white shadow-sm focus-visible:ring-primary-500",
        secondary:
            "bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-surface-dark-card dark:hover:bg-surface-dark-hover dark:text-slate-300 focus-visible:ring-slate-400",
        success:
            "bg-game-green hover:brightness-110 text-white shadow-sm focus-visible:ring-game-green",
        danger: "bg-game-red hover:brightness-110 text-white shadow-sm focus-visible:ring-game-red",
        ghost: "hover:bg-slate-100 dark:hover:bg-surface-dark-card text-slate-600 dark:text-slate-400 focus-visible:ring-slate-400",
        outline:
            "border border-surface-light-border dark:border-surface-dark-border hover:bg-slate-50 dark:hover:bg-surface-dark-card text-slate-700 dark:text-slate-300 focus-visible:ring-primary-500",
    };
    return variants[props.variant];
});
</script>
