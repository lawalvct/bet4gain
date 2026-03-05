import { defineStore } from "pinia";
import { ref, computed } from "vue";
import axios from "axios";
import api from "@/Utils/api";

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

export const useUserStore = defineStore("user", () => {
    // State
    const user = ref(window.__BET4GAIN__?.user || null);
    const isGuest = ref(!window.__BET4GAIN__?.user);
    const loading = ref(false);
    const preferences = ref({
        theme: "dark",
        sound_enabled: true,
        sound_volume: 0.7,
        notifications_enabled: true,
    });

    // Computed
    const isAuthenticated = computed(() => !!user.value && !isGuest.value);
    const isAdmin = computed(() => user.value?.role === "admin");
    const isModerator = computed(() =>
        ["admin", "moderator"].includes(user.value?.role),
    );
    const username = computed(() => user.value?.username || "Guest");
    const avatarUrl = computed(() => {
        if (!user.value?.avatar) return null;
        return user.value.avatar.startsWith("http")
            ? user.value.avatar
            : `/storage/${user.value.avatar}`;
    });
    const walletBalance = computed(() => user.value?.wallet?.balance ?? 0);
    const coinBalance = computed(() => user.value?.coin_balance?.balance ?? 0);
    const isEmailVerified = computed(() => !!user.value?.email_verified_at);

    // Actions
    const fetchUser = async () => {
        loading.value = true;
        try {
            const response = await api.get("/user");
            user.value = response.data;
            isGuest.value = !!response.data.is_guest;
            // Load server-side preferences into local state
            if (response.data.settings) {
                preferences.value = {
                    ...preferences.value,
                    ...response.data.settings,
                };
            }
        } catch (error) {
            user.value = null;
            isGuest.value = true;
        } finally {
            loading.value = false;
        }
    };

    const setUser = (userData) => {
        user.value = userData;
        isGuest.value = !userData || !!userData.is_guest;
    };

    const updateProfile = async (data) => {
        try {
            await axios.put("/user/profile-information", data, {
                headers: {
                    "X-CSRF-TOKEN": csrfToken(),
                    Accept: "application/json",
                },
            });
            // Refresh user data from server
            await fetchUser();
        } catch (error) {
            throw error;
        }
    };

    const updatePassword = async (data) => {
        try {
            await axios.put("/user/password", data, {
                headers: {
                    "X-CSRF-TOKEN": csrfToken(),
                    Accept: "application/json",
                },
            });
        } catch (error) {
            throw error;
        }
    };

    const updatePreferences = async (settings) => {
        preferences.value = { ...preferences.value, ...settings };
        try {
            await api.put("/user/settings", { settings });
        } catch {
            // Silently fail — local prefs still applied
        }
    };

    const logout = async () => {
        try {
            await axios.post(
                "/logout",
                {},
                {
                    headers: { "X-CSRF-TOKEN": csrfToken() },
                },
            );
        } finally {
            user.value = null;
            isGuest.value = true;
            window.location.href = "/";
        }
    };

    const createGuestSession = async () => {
        try {
            const response = await api.post("/guest");
            user.value = response.data.data;
            isGuest.value = true;
            return response.data;
        } catch (error) {
            console.error("Failed to create guest session:", error);
        }
    };

    const resumeGuestSession = async () => {
        try {
            const response = await api.post("/guest/resume");
            user.value = response.data.data;
            isGuest.value = true;
            return response.data;
        } catch {
            // No guest session to resume
            return null;
        }
    };

    return {
        // State
        user,
        isGuest,
        loading,
        preferences,
        // Computed
        isAuthenticated,
        isAdmin,
        isModerator,
        username,
        avatarUrl,
        walletBalance,
        coinBalance,
        isEmailVerified,
        // Actions
        fetchUser,
        setUser,
        updateProfile,
        updatePassword,
        updatePreferences,
        logout,
        createGuestSession,
        resumeGuestSession,
    };
});
