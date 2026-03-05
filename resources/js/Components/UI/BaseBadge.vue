<template>
    <span
        :class="[
            'inline-flex items-center font-medium tabular-nums',
            sizeClasses,
            variantClasses,
            roundedClasses,
            dot ? 'gap-1' : '',
        ]"
    >
        <span v-if="dot" :class="['rounded-full', dotClasses]" />
        <slot>{{ label }}</slot>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: [String, Number], default: '' },
    variant: { type: String, default: 'default', validator: v => ['default', 'primary', 'success', 'danger', 'warning', 'info', 'accent'].includes(v) },
    size: { type: String, default: 'sm', validator: v => ['xs', 'sm', 'md'].includes(v) },
    rounded: { type: String, default: 'full' },
    dot: { type: Boolean, default: false },
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'px-1.5 py-0.5 text-[10px]',
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
    };
    return sizes[props.size];
});

const dotClasses = computed(() => {
    const sizes = { xs: 'w-1 h-1', sm: 'w-1.5 h-1.5', md: 'w-2 h-2' };
    const colors = {
        default: 'bg-slate-400',
        primary: 'bg-primary-500',
        success: 'bg-game-green',
        danger: 'bg-game-red',
        warning: 'bg-game-yellow',
        info: 'bg-game-blue',
        accent: 'bg-accent-500',
    };
    return `${sizes[props.size]} ${colors[props.variant]}`;
});

const roundedClasses = computed(() => `rounded-${props.rounded}`);

const variantClasses = computed(() => {
    const variants = {
        default: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
        primary: 'bg-primary-500/10 text-primary-600 dark:text-primary-400',
        success: 'bg-green-500/10 text-green-600 dark:text-green-400',
        danger: 'bg-red-500/10 text-red-600 dark:text-red-400',
        warning: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
        info: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        accent: 'bg-accent-500/10 text-accent-600 dark:text-accent-400',
    };
    return variants[props.variant];
});
</script>
