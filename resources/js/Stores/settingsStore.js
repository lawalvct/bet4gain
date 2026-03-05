import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/Utils/api";

export const useSettingsStore = defineStore("settings", () => {
    // State
    const settings = ref({});
    const loading = ref(false);

    // Getters
    const get = (key, defaultValue = null) => {
        return settings.value[key] ?? defaultValue;
    };

    // Actions
    const fetchSettings = async () => {
        loading.value = true;
        try {
            const response = await api.get("/api/settings");
            settings.value = response.data.data || {};
        } catch (error) {
            console.error("Failed to fetch settings:", error);
        } finally {
            loading.value = false;
        }
    };

    const setSetting = (key, value) => {
        settings.value[key] = value;
    };

    const setAll = (newSettings) => {
        settings.value = { ...settings.value, ...newSettings };
    };

    return {
        // State
        settings,
        loading,
        // Getters
        get,
        // Actions
        fetchSettings,
        setSetting,
        setAll,
    };
});
