<template>
    <div :class="['relative', block ? 'w-full' : '']">
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ label }}
            <span v-if="required" class="text-game-red">*</span>
        </label>

        <div class="relative">
            <!-- Prefix Icon/Slot -->
            <div v-if="$slots.prefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <slot name="prefix" />
            </div>

            <input
                :id="inputId"
                ref="inputRef"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :min="min"
                :max="max"
                :step="step"
                :maxlength="maxlength"
                :autocomplete="autocomplete"
                :class="[
                    'w-full transition-all duration-200 border text-slate-900 dark:text-white placeholder-slate-400',
                    'focus:ring-2 focus:border-transparent',
                    sizeClasses,
                    $slots.prefix ? 'pl-10' : '',
                    $slots.suffix ? 'pr-10' : '',
                    error
                        ? 'border-game-red focus:ring-game-red/30 bg-red-50/50 dark:bg-red-900/10'
                        : 'border-surface-light-border dark:border-surface-dark-border focus:ring-primary-500/30 bg-surface-light dark:bg-surface-dark',
                    disabled ? 'opacity-50 cursor-not-allowed' : '',
                    roundedClasses,
                ]"
                @input="$emit('update:modelValue', $event.target.value)"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
                @keydown.enter="$emit('enter', $event)"
            />

            <!-- Suffix Icon/Slot -->
            <div v-if="$slots.suffix" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                <slot name="suffix" />
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-game-red animate-slide-in-up">{{ error }}</p>

        <!-- Hint -->
        <p v-else-if="hint" class="mt-1 text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    size: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg'].includes(v) },
    rounded: { type: String, default: 'xl', validator: v => ['md', 'lg', 'xl', '2xl', 'full'].includes(v) },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    block: { type: Boolean, default: true },
    min: { type: [String, Number], default: undefined },
    max: { type: [String, Number], default: undefined },
    step: { type: [String, Number], default: undefined },
    maxlength: { type: [String, Number], default: undefined },
    autocomplete: { type: String, default: undefined },
});

defineEmits(['update:modelValue', 'blur', 'focus', 'enter']);

const inputRef = ref(null);
const inputId = `input-${useId()}`;

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-3 text-sm',
        lg: 'px-4 py-3.5 text-base',
    };
    return sizes[props.size];
});

const roundedClasses = computed(() => `rounded-${props.rounded}`);

const focus = () => inputRef.value?.focus();

defineExpose({ focus, inputRef });
</script>
