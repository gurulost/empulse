import { reactive } from 'vue';

const state = reactive({
    toasts: []
});

let idCounter = 0;

export function useToast() {
    const addToast = (message, type = 'info', duration = 3000) => {
        const id = idCounter++;
        const toast = { id, message, type, duration };
        state.toasts.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }
    };

    const removeToast = (id) => {
        const index = state.toasts.findIndex(t => t.id === id);
        if (index !== -1) {
            state.toasts.splice(index, 1);
        }
    };

    const success = (message, duration = 3000) => addToast(message, 'success', duration);
    const error = (message, duration = 5000) => addToast(message, 'error', duration);
    const info = (message, duration = 3000) => addToast(message, 'info', duration);
    const warning = (message, duration = 4000) => addToast(message, 'warning', duration);

    return {
        toasts: state.toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
        warning
    };
}
