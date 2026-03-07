import { ref } from "vue";

const toasts = ref([]);
let nextId = 1;

/**
 * Composable for managing toast notifications.
 *
 * Usage:
 *   const { toast } = useToast();
 *   toast.success('Bet placed!');
 *   toast.error('Insufficient balance');
 *   toast.win('You won ₦5,000!', 'Cashout at 2.5x');
 */
export function useToast() {
    const addToast = (type, message, title = "", duration = 4000) => {
        const id = nextId++;
        toasts.value.push({ id, type, message, title });

        if (duration > 0) {
            setTimeout(() => removeToast(id), duration);
        }

        // Max 5 toasts visible
        if (toasts.value.length > 5) {
            toasts.value.shift();
        }
    };

    const removeToast = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const clearAll = () => {
        toasts.value.splice(0);
    };

    const toast = {
        success: (message, title = "") => addToast("success", message, title),
        error: (message, title = "") => addToast("error", message, title, 6000),
        warning: (message, title = "") =>
            addToast("warning", message, title, 5000),
        info: (message, title = "") => addToast("info", message, title),
        win: (message, title = "You Won!") =>
            addToast("win", message, title, 5000),
        cashout: (message, title = "Cashed Out!") =>
            addToast("cashout", message, title, 4000),
        transfer: (message, title = "Coins Received!") =>
            addToast("transfer", message, title, 8000),
    };

    return { toasts, toast, removeToast, clearAll };
}
