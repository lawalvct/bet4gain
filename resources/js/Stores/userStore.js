import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/Utils/api";

export const useUserStore = defineStore("user", () => {
    // State
    const user = ref(window.__BET4GAIN__?.user || null);
    const isGuest = ref(!window.__BET4GAIN__?.user);
    const loading = ref(false);

    // Computed
    const isAuthenticated = computed(() => !!user.value && !isGuest.value);
    const isAdmin = computed(() => user.value?.role === "admin");
    const isModerator = computed(() =>
        ["admin", "moderator"].includes(user.value?.role),
    );
    const username = computed(() => user.value?.username || "Guest");
    const avatarUrl = computed(
        () => user.value?.avatar_url || "/images/default-avatar.png",
    );

    // Actions
    const fetchUser = async () => {
        loading.value = true;
        try {
            const response = await api.get("/api/user");
            user.value = response.data;
            isGuest.value = false;
        } catch (error) {
            user.value = null;
            isGuest.value = true;
        } finally {
            loading.value = false;
        }
    };

    const setUser = (userData) => {
        user.value = userData;
        isGuest.value = !userData;
    };

    const updateProfile = async (data) => {
        try {
            const response = await api.put("/api/user/profile", data);
            user.value = { ...user.value, ...response.data.data };
            return response.data;
        } catch (error) {
            throw error;
        }
    };

    const logout = async () => {
        try {
            await api.post("/logout");
        } finally {
            user.value = null;
            isGuest.value = true;
            window.location.href = "/";
        }
    };

    const createGuestSession = async () => {
        try {
            const response = await api.post("/api/guest");
            user.value = response.data.data;
            isGuest.value = true;
            return response.data;
        } catch (error) {
            console.error("Failed to create guest session:", error);
        }
    };

    return {
        // State
        user,
        isGuest,
        loading,
        // Computed
        isAuthenticated,
        isAdmin,
        isModerator,
        username,
        avatarUrl,
        // Actions
        fetchUser,
        setUser,
        updateProfile,
        logout,
        createGuestSession,
    };
});
