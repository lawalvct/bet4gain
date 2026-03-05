<template>
    <div class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden">
        <!-- Header skeleton -->
        <div class="px-4 py-2.5 border-b border-surface-light-border dark:border-surface-dark-border flex items-center justify-between">
            <SkeletonLoader width="100px" height="14px" />
            <SkeletonLoader width="40px" height="14px" />
        </div>

        <!-- Items -->
        <div class="divide-y divide-surface-light-border dark:divide-surface-dark-border">
            <div v-for="i in rows" :key="i" class="flex items-center gap-3 px-4 py-3">
                <SkeletonLoader v-if="avatar" width="24px" shape="circle" />
                <SkeletonLoader :width="randomWidth()" height="12px" />
                <SkeletonLoader width="50px" height="12px" class="ml-auto" />
            </div>
        </div>
    </div>
</template>

<script setup>
import SkeletonLoader from './SkeletonLoader.vue';

defineProps({
    rows: { type: Number, default: 5 },
    avatar: { type: Boolean, default: true },
});

const randomWidth = () => `${Math.floor(Math.random() * 40) + 40}%`;
</script>
