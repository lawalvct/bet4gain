<template>
    <div>
        <!-- Tab Headers -->
        <div
            :class="[
                'flex border-b border-surface-light-border dark:border-surface-dark-border',
                headerClass,
            ]"
        >
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="selectTab(tab.value)"
                :class="[
                    'flex items-center gap-1.5 py-2.5 transition-all duration-200 border-b-2 -mb-[1px]',
                    sizeClasses,
                    modelValue === tab.value
                        ? 'text-primary-500 border-primary-500 font-medium'
                        : 'text-slate-400 border-transparent hover:text-slate-600 dark:hover:text-slate-300',
                    stretch ? 'flex-1 justify-center' : '',
                ]"
            >
                <span v-if="tab.icon" class="text-sm">{{ tab.icon }}</span>
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-primary-500/10 text-primary-500 rounded-full"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </div>

        <!-- Tab Content -->
        <div :class="contentClass">
            <slot :activeTab="modelValue" />
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: String, required: true },
    tabs: {
        type: Array,
        required: true,
        validator: (v) => v.every((t) => t.value && t.label),
    },
    size: {
        type: String,
        default: "sm",
        validator: (v) => ["xs", "sm", "md"].includes(v),
    },
    stretch: { type: Boolean, default: true },
    headerClass: { type: String, default: "" },
    contentClass: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const sizeClasses = computed(() => {
    const sizes = {
        xs: "px-2 text-xs",
        sm: "px-3 text-sm",
        md: "px-4 text-sm",
    };
    return sizes[props.size];
});

const selectTab = (value) => {
    emit("update:modelValue", value);
};
</script>
