<template>
    <div :class="['skeleton', shapeClasses]" :style="customStyle" />
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    width: { type: String, default: "100%" },
    height: { type: String, default: "1rem" },
    shape: {
        type: String,
        default: "rect",
        validator: (v) => ["rect", "circle", "pill"].includes(v),
    },
});

const shapeClasses = computed(() => {
    if (props.shape === "circle") return "!rounded-full aspect-square";
    if (props.shape === "pill") return "!rounded-full";
    return "";
});

const customStyle = computed(() => ({
    width: props.width,
    height: props.shape === "circle" ? props.width : props.height,
}));
</script>
