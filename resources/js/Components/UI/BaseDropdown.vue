<template>
    <div class="relative inline-flex" ref="dropdownRef">
        <!-- Trigger -->
        <div @click="toggle">
            <slot name="trigger" />
        </div>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="animate-scale-in"
            leave-active-class="animate-fade-out"
        >
            <div
                v-if="isOpen"
                :class="[
                    'absolute z-50 min-w-[180px]',
                    'bg-surface-light-card dark:bg-surface-dark-card',
                    'border border-surface-light-border dark:border-surface-dark-border',
                    'rounded-xl shadow-dropdown py-1',
                    positionClasses,
                ]"
            >
                <slot />
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    position: { type: String, default: 'bottom-right', validator: v => ['bottom-left', 'bottom-right', 'top-left', 'top-right'].includes(v) },
});

const isOpen = ref(false);
const dropdownRef = ref(null);

const positionClasses = computed(() => {
    const positions = {
        'bottom-right': 'top-full right-0 mt-2',
        'bottom-left': 'top-full left-0 mt-2',
        'top-right': 'bottom-full right-0 mb-2',
        'top-left': 'bottom-full left-0 mb-2',
    };
    return positions[props.position];
});

const toggle = () => { isOpen.value = !isOpen.value; };
const close = () => { isOpen.value = false; };

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        close();
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));

defineExpose({ close, toggle, isOpen });
</script>
