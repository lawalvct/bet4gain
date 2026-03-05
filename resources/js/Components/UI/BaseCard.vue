<template>
    <div
        :class="[
            'bg-surface-light-card dark:bg-surface-dark-card',
            'border border-surface-light-border dark:border-surface-dark-border',
            'transition-shadow duration-200',
            hoverable
                ? 'hover:shadow-card-hover cursor-pointer'
                : 'shadow-card',
            roundedClasses,
            padding ? paddingClasses : '',
        ]"
    >
        <!-- Header -->
        <div
            v-if="title || $slots.header"
            :class="[
                'flex items-center justify-between border-b border-surface-light-border dark:border-surface-dark-border',
                headerPadding,
            ]"
        >
            <slot name="header">
                <div class="flex items-center gap-2">
                    <span v-if="icon" class="text-lg">{{ icon }}</span>
                    <h3
                        class="text-sm font-semibold text-slate-700 dark:text-slate-300"
                    >
                        {{ title }}
                    </h3>
                    <slot name="header-badge" />
                </div>
            </slot>
            <slot name="header-actions" />
        </div>

        <!-- Body -->
        <div :class="bodyClass">
            <slot />
        </div>

        <!-- Footer -->
        <div
            v-if="$slots.footer"
            :class="[
                'border-t border-surface-light-border dark:border-surface-dark-border',
                footerPadding,
            ]"
        >
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    title: { type: String, default: "" },
    icon: { type: String, default: "" },
    rounded: { type: String, default: "xl" },
    padding: { type: Boolean, default: false },
    hoverable: { type: Boolean, default: false },
    bodyClass: { type: String, default: "" },
});

const roundedClasses = computed(() => `rounded-${props.rounded}`);
const paddingClasses = "p-4";
const headerPadding = "px-4 py-2.5";
const footerPadding = "px-4 py-3";
</script>
