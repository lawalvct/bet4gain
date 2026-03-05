<template>
    <div class="relative inline-flex flex-shrink-0">
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            :class="[
                'object-cover',
                sizeClasses,
                rounded ? 'rounded-full' : 'rounded-lg',
                ring ? 'ring-2 ring-white dark:ring-surface-dark' : '',
            ]"
            @error="handleError"
        />
        <div
            v-else
            :class="[
                'flex items-center justify-center bg-primary-500/10 text-primary-500 font-semibold',
                sizeClasses,
                rounded ? 'rounded-full' : 'rounded-lg',
                ring ? 'ring-2 ring-white dark:ring-surface-dark' : '',
            ]"
        >
            {{ initials }}
        </div>

        <!-- Online indicator -->
        <span
            v-if="showStatus"
            :class="[
                'absolute bottom-0 right-0 block rounded-full ring-2 ring-white dark:ring-surface-dark',
                online ? 'bg-game-green' : 'bg-slate-400',
                statusSizeClasses,
            ]"
        />
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    src: { type: String, default: "" },
    alt: { type: String, default: "" },
    name: { type: String, default: "" },
    size: {
        type: String,
        default: "md",
        validator: (v) => ["xs", "sm", "md", "lg", "xl"].includes(v),
    },
    rounded: { type: Boolean, default: true },
    ring: { type: Boolean, default: false },
    online: { type: Boolean, default: false },
    showStatus: { type: Boolean, default: false },
});

const fallbackSrc = ref("");

const sizeClasses = computed(() => {
    const sizes = {
        xs: "w-5 h-5 text-[8px]",
        sm: "w-6 h-6 text-[10px]",
        md: "w-8 h-8 text-xs",
        lg: "w-10 h-10 text-sm",
        xl: "w-14 h-14 text-base",
    };
    return sizes[props.size];
});

const statusSizeClasses = computed(() => {
    const sizes = {
        xs: "w-1.5 h-1.5",
        sm: "w-2 h-2",
        md: "w-2.5 h-2.5",
        lg: "w-3 h-3",
        xl: "w-3.5 h-3.5",
    };
    return sizes[props.size];
});

const initials = computed(() => {
    const name = props.name || props.alt || "?";
    return name.charAt(0).toUpperCase();
});

const handleError = () => {
    fallbackSrc.value = "";
};
</script>
