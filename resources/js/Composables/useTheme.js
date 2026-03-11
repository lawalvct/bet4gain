import { ref } from "vue";
import api from "@/Utils/api";

const STORAGE_KEY = "bet4gain_theme";
const isDark = ref(true);
let initialized = false;

/**
 * Composable for managing dark/light theme.
 * Persists to localStorage + optionally to user DB settings.
 */
export function useTheme() {
    const init = () => {
        if (initialized) return;
        initialized = true;

        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === "light") {
            isDark.value = false;
        } else if (saved === "dark") {
            isDark.value = true;
        } else {
            // Enforce dark mode as the app default for first-time visitors.
            isDark.value = true;
        }
        applyTheme();
    };

    const applyTheme = () => {
        document.documentElement.classList.toggle("dark", isDark.value);
        // Update meta theme-color for mobile browsers
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute("content", isDark.value ? "#0f1419" : "#f8fafc");
        }
    };

    const toggle = () => {
        isDark.value = !isDark.value;
        localStorage.setItem(STORAGE_KEY, isDark.value ? "dark" : "light");
        applyTheme();

        // Persist to DB for authenticated users (fire & forget)
        if (window.__BET4GAIN__?.user) {
            api.put("/user/profile", {
                theme: isDark.value ? "dark" : "light",
            }).catch(() => {});
        }
    };

    const setTheme = (dark) => {
        isDark.value = dark;
        localStorage.setItem(STORAGE_KEY, dark ? "dark" : "light");
        applyTheme();
    };

    return { isDark, init, toggle, setTheme };
}
