<template>
    <div v-if="ad && !dismissed" :class="containerClasses">
        <a
            :href="ad.target_url || '#'"
            :target="ad.target_url ? '_blank' : undefined"
            :rel="ad.target_url ? 'noopener noreferrer' : undefined"
            @click="trackClick"
            class="block relative group"
        >
            <img
                :src="ad.image_url"
                :alt="ad.title || 'Advertisement'"
                :class="imageClasses"
                loading="lazy"
                @load="trackImpression"
            />
            <!-- Hover overlay -->
            <div
                class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition rounded-t-xl"
            ></div>
        </a>
        <div
            class="bg-surface-light-card dark:bg-surface-dark-card px-2 py-1 flex items-center justify-between rounded-b-xl"
        >
            <span class="text-[10px] text-slate-400 uppercase tracking-wider"
                >Sponsored</span
            >
            <button
                @click="dismiss"
                class="text-[10px] text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition p-0.5"
                title="Dismiss ad"
            >
                <svg
                    class="w-3 h-3"
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
    </div>

    <!-- Placeholder when no ad is available -->
    <div
        v-else-if="!ad && showPlaceholder && !dismissed"
        :class="containerClasses"
    >
        <div
            :class="[
                'flex items-center justify-center bg-surface-light dark:bg-surface-dark rounded-xl border border-dashed border-surface-light-border dark:border-surface-dark-border text-slate-400 text-xs',
                placeholderHeightClass,
            ]"
        >
            <span>Ad Space — {{ placement }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import api from "@/Utils/api";

const props = defineProps({
    ad: { type: Object, default: null },
    placement: {
        type: String,
        default: "sidebar",
        validator: (v) =>
            [
                "sidebar",
                "banner",
                "between-rounds",
                "leaderboard",
                "header",
            ].includes(v),
    },
    showPlaceholder: { type: Boolean, default: false },
});

const dismissed = ref(false);

const containerClasses = computed(() => {
    const base =
        "rounded-xl overflow-hidden border border-surface-light-border dark:border-surface-dark-border";
    const placements = {
        sidebar: `${base}`,
        banner: `${base} w-full`,
        "between-rounds": `${base} w-full max-w-lg mx-auto`,
        leaderboard: `${base} w-full max-w-[728px] mx-auto`,
        header: `${base} w-full max-w-[468px] mx-auto`,
    };
    return placements[props.placement] || base;
});

const imageClasses = computed(() => {
    const base = "w-full object-cover rounded-t-xl";
    const heights = {
        sidebar: `${base} max-h-[250px]`,
        banner: `${base} max-h-[90px]`,
        "between-rounds": `${base} max-h-[120px]`,
        leaderboard: `${base} max-h-[90px]`,
        header: `${base} max-h-[60px]`,
    };
    return heights[props.placement] || base;
});

const placeholderHeightClass = computed(() => {
    const heights = {
        sidebar: "h-[200px]",
        banner: "h-[90px]",
        "between-rounds": "h-[100px]",
        leaderboard: "h-[90px]",
        header: "h-[60px]",
    };
    return heights[props.placement] || "h-[200px]";
});

const trackImpression = () => {
    if (props.ad?.id) {
        api.post(`/ads/${props.ad.id}/impression`).catch(() => {});
    }
};

const trackClick = () => {
    if (props.ad?.id) {
        if (!props.ad.target_url) {
            return;
        }
        api.post(`/ads/${props.ad.id}/click`).catch(() => {});
    }
};

const dismiss = () => {
    dismissed.value = true;
};
</script>
