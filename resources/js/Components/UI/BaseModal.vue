<template>
    <Teleport to="body">
        <Transition
            enter-active-class="animate-fade-in"
            leave-active-class="animate-fade-out"
        >
            <div v-if="modelValue" class="modal-backdrop" @click.self="closeOnBackdrop && close()">
                <Transition
                    enter-active-class="animate-scale-in"
                    leave-active-class="animate-fade-out"
                >
                    <div
                        v-if="modelValue"
                        :class="[
                            'fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-[91] w-full',
                            'bg-surface-light-card dark:bg-surface-dark-card',
                            'border border-surface-light-border dark:border-surface-dark-border',
                            'shadow-modal overflow-hidden',
                            sizeClasses,
                            roundedClasses,
                        ]"
                    >
                        <!-- Header -->
                        <div v-if="title || $slots.header" class="flex items-center justify-between px-6 py-4 border-b border-surface-light-border dark:border-surface-dark-border">
                            <slot name="header">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ title }}</h3>
                            </slot>
                            <button
                                v-if="closable"
                                @click="close"
                                class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-surface-dark transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-4 overflow-y-auto max-h-[70vh]">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="px-6 py-4 border-t border-surface-light-border dark:border-surface-dark-border bg-slate-50 dark:bg-surface-dark-alt">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg', 'xl', 'full'].includes(v) },
    closable: { type: Boolean, default: true },
    closeOnBackdrop: { type: Boolean, default: true },
    closeOnEscape: { type: Boolean, default: true },
    rounded: { type: String, default: '2xl' },
});

const emit = defineEmits(['update:modelValue', 'close']);

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        full: 'max-w-[90vw]',
    };
    return sizes[props.size];
});

const roundedClasses = computed(() => `rounded-${props.rounded}`);

const close = () => {
    emit('update:modelValue', false);
    emit('close');
};

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.closeOnEscape && props.modelValue) {
        close();
    }
};

// Prevent body scroll when modal is open
watch(() => props.modelValue, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => document.addEventListener('keydown', handleEscape));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>
