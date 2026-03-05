import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/Utils/api";

export const useLeaderboardStore = defineStore("leaderboard", () => {
    // State
    const entries = ref([]);
    const activePeriod = ref("daily");
    const loading = ref(false);
    const liveStats = ref({
        total_wagered_today: 0,
        biggest_win_today: 0,
        unique_players: 0,
        total_bets_today: 0,
    });

    // Personal stats
    const personalStats = ref(null);
    const myBets = ref([]);
    const myBetsPagination = ref({ current_page: 1, last_page: 1, total: 0 });
    const loadingStats = ref(false);
    const loadingBets = ref(false);

    // Game history
    const gameHistory = ref([]);
    const gameHistoryPagination = ref({
        current_page: 1,
        last_page: 1,
        total: 0,
    });
    const loadingHistory = ref(false);

    // ── Leaderboard ──

    const fetchLeaderboard = async (period = null) => {
        if (period) activePeriod.value = period;
        loading.value = true;

        try {
            const response = await api.get(
                `/api/leaderboard/${activePeriod.value}`,
            );
            entries.value = response.data.data || [];
        } catch (error) {
            console.error("Failed to fetch leaderboard:", error);
        } finally {
            loading.value = false;
        }
    };

    const setPeriod = (period) => {
        activePeriod.value = period;
        fetchLeaderboard(period);
    };

    // ── Live Stats ──

    const fetchLiveStats = async () => {
        try {
            const response = await api.get("/api/stats/live");
            liveStats.value = response.data.data;
        } catch (error) {
            console.error("Failed to fetch live stats:", error);
        }
    };

    // ── Personal Stats ──

    const fetchPersonalStats = async () => {
        loadingStats.value = true;
        try {
            const response = await api.get("/api/stats/me");
            personalStats.value = response.data.data;
        } catch (error) {
            console.error("Failed to fetch personal stats:", error);
        } finally {
            loadingStats.value = false;
        }
    };

    // ── My Bets History ──

    const fetchMyBets = async (page = 1) => {
        loadingBets.value = true;
        try {
            const response = await api.get("/api/stats/my-bets", {
                params: { page, per_page: 20 },
            });
            myBets.value = response.data.data || [];
            myBetsPagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                total: response.data.total,
            };
        } catch (error) {
            console.error("Failed to fetch my bets:", error);
        } finally {
            loadingBets.value = false;
        }
    };

    // ── Game History (Rounds) ──

    const fetchGameHistory = async (page = 1) => {
        loadingHistory.value = true;
        try {
            const response = await api.get("/api/game/rounds", {
                params: { page, per_page: 30 },
            });
            gameHistory.value = response.data.data || [];
            gameHistoryPagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                total: response.data.total,
            };
        } catch (error) {
            console.error("Failed to fetch game history:", error);
        } finally {
            loadingHistory.value = false;
        }
    };

    return {
        // State
        entries,
        activePeriod,
        loading,
        liveStats,
        personalStats,
        myBets,
        myBetsPagination,
        loadingStats,
        loadingBets,
        gameHistory,
        gameHistoryPagination,
        loadingHistory,
        // Actions
        fetchLeaderboard,
        setPeriod,
        fetchLiveStats,
        fetchPersonalStats,
        fetchMyBets,
        fetchGameHistory,
    };
});
