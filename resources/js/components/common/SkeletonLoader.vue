<template>
    <div class="skeleton-loader" :style="{ width: width, height: height, borderRadius: borderRadius }"></div>
</template>

<script setup>
defineProps({
    width: { type: String, default: '100%' },
    height: { type: String, default: '1rem' },
    borderRadius: { type: String, default: '0.25rem' }
});
</script>

<style scoped>
.skeleton-loader {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    display: inline-block;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
