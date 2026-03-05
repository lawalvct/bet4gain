import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/Utils/api";

export const useGameStore = defineStore("game", () => {
    // State
    const status = ref("waiting"); // waiting, betting, running, crashed
    const currentMultiplier = ref(1.0);
    const crashPoint = ref(null);
    const roundId = ref(null);
    const roundHash = ref("");
    const countdown = ref(0);
    const bettingCountdown = ref(0);
    const history = ref([]);
    const serverSeedHash = ref("");

    // Computed
    const isAcceptingBets = computed(() =>
        ["waiting", "betting"].includes(status.value),
    );
    const isRunning = computed(() => status.value === "running");
    const hasCrashed = computed(() => status.value === "crashed");

    // Actions
    const setStatus = (newStatus) => {
        status.value = newStatus;
    };

    const setMultiplier = (value) => {
        currentMultiplier.value = value;
    };

    const setCrashPoint = (value) => {
        crashPoint.value = value;
    };

    const startNewRound = (data) => {
        roundId.value = data.round_id;
        roundHash.value = data.hash || "";
        serverSeedHash.value = data.server_seed_hash || "";
        status.value = "betting";
        currentMultiplier.value = 1.0;
        crashPoint.value = null;
    };

    const endRound = (data) => {
        crashPoint.value = data.crash_point;
        status.value = "crashed";

        // Add to history (keep last 50)
        history.value.unshift(data.crash_point);
        if (history.value.length > 50) {
            history.value = history.value.slice(0, 50);
        }
    };

    const fetchHistory = async () => {
        try {
            const response = await api.get("/api/game/history");
            history.value = response.data.data || [];
        } catch (error) {
            console.error("Failed to fetch game history:", error);
        }
    };

    const reset = () => {
        status.value = "waiting";
        currentMultiplier.value = 1.0;
        crashPoint.value = null;
        countdown.value = 0;
        bettingCountdown.value = 0;
    };

    return {
        // State
        status,
        currentMultiplier,
        crashPoint,
        roundId,
        roundHash,
        countdown,
        bettingCountdown,
        history,
        serverSeedHash,
        // Computed
        isAcceptingBets,
        isRunning,
        hasCrashed,
        // Actions
        setStatus,
        setMultiplier,
        setCrashPoint,
        startNewRound,
        endRound,
        fetchHistory,
        reset,
    };
});
