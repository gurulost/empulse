<template>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060">
        <TransitionGroup name="toast">
            <div v-for="toast in toasts" 
                 :key="toast.id" 
                 class="toast show align-items-center border-0 mb-2 shadow-lg" 
                 :class="getToastClass(toast.type)"
                 role="alert" 
                 aria-live="assertive" 
                 aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body text-white d-flex align-items-center">
                        <i :class="getIconClass(toast.type)" class="me-2 fs-5"></i>
                        <span class="fw-medium">{{ toast.message }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="removeToast(toast.id)" aria-label="Close"></button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { useToast } from '../../composables/useToast';

const { toasts, removeToast } = useToast();

const getToastClass = (type) => {
    switch (type) {
        case 'success': return 'bg-success text-white';
        case 'error': return 'bg-danger text-white';
        case 'warning': return 'bg-warning text-dark';
        case 'info': return 'bg-info text-white';
        default: return 'bg-primary text-white';
    }
};

const getIconClass = (type) => {
    switch (type) {
        case 'success': return 'bi bi-check-circle-fill';
        case 'error': return 'bi bi-x-circle-fill';
        case 'warning': return 'bi bi-exclamation-triangle-fill';
        case 'info': return 'bi bi-info-circle-fill';
        default: return 'bi bi-bell-fill';
    }
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
</style>
