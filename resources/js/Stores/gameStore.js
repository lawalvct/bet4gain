import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/Utils/api";
import { useBetStore } from "@/Stores/betStore";
import { useWalletStore } from "@/Stores/walletStore";

export const useGameStore = defineStore("game", () => {
    // ── State ──────────────────────────────────────────────────────────────────
    const status = ref("waiting"); // waiting | betting | running | crashed
    const currentMultiplier = ref(1.0);
    const crashPoint = ref(null);
    const roundId = ref(null);
    const roundHash = ref("");
    const countdown = ref(0);
    const bettingCountdown = ref(0);
    const history = ref([]);
    const serverSeedHash = ref("");
    const liveBets = ref([]); // active bets for current round (Phase 5)

    let echoChannel = null;
    let countdownTimer = null;

    // ── Computed ───────────────────────────────────────────────────────────────
    const isAcceptingBets = computed(() =>
        ["waiting", "betting"].includes(status.value),
    );
    const isRunning = computed(() => status.value === "running");
    const hasCrashed = computed(() => status.value === "crashed");

    // ── Helpers ────────────────────────────────────────────────────────────────
    const clearCountdown = () => {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    };

    const startLocalCountdown = (seconds, onTick) => {
        clearCountdown();
        let remaining = seconds;
        onTick(remaining);
        countdownTimer = setInterval(() => {
            remaining -= 1;
            onTick(remaining);
            if (remaining <= 0) clearCountdown();
        }, 1000);
    };

    // ── WebSocket handlers ─────────────────────────────────────────────────────

    /** Event: countdown.tick  – broadcasted every second during waiting phase */
    const onCountdownTick = (e) => {
        // Transition from crashed → waiting: clear stale bet data
        if (status.value === "crashed") {
            const betStore = useBetStore();
            betStore.clearRound();
            status.value = "waiting";
        }
        countdown.value = e.seconds_left ?? e.secondsLeft ?? 0;
    };

    /** Event: betting.started */
    const onBettingStarted = (e) => {
        // Clear bets from previous round. Only preserve bets if they
        // were already placed for THIS exact round (e.g. placed during
        // the waiting phase of the same round).
        const betStore = useBetStore();
        const hasCurrentRoundBet = [1, 2].some(
            (slot) => betStore.bets[slot]?.game_round_id === e.round_id,
        );
        if (!hasCurrentRoundBet) {
            betStore.clearRound();
        } else {
            // Even if we keep bets, clear the live bets list for the new round
            betStore.liveBets = [];
        }

        roundId.value = e.round_id;
        serverSeedHash.value = e.server_seed_hash ?? "";
        roundHash.value = e.server_seed_hash ?? "";
        status.value = "betting";
        currentMultiplier.value = 1.0;
        crashPoint.value = null;
        liveBets.value = [];
        clearCountdown();
        startLocalCountdown(e.betting_duration ?? 10, (s) => {
            bettingCountdown.value = s;
        });
    };

    /** Event: round.started */
    const onRoundStarted = (e) => {
        roundId.value = e.round_id;
        status.value = "running";
        clearCountdown();
        bettingCountdown.value = 0;
    };

    /** Event: multiplier.updated  – ~100 ms ticks */
    const onMultiplierUpdated = (e) => {
        if (e.round_id !== roundId.value) return;
        currentMultiplier.value = parseFloat(e.multiplier);
    };

    /** Event: round.crashed */
    const onRoundCrashed = (e) => {
        crashPoint.value = parseFloat(e.crash_point);
        status.value = "crashed";
        currentMultiplier.value = parseFloat(e.crash_point);
        clearCountdown();

        // Mark all remaining active bets as lost
        liveBets.value = liveBets.value.map((b) =>
            !b.cashed_out_at ? { ...b, lost: true } : b,
        );

        // Mark user's own bet slots as resolved so they don't block the next round
        const betStore = useBetStore();
        [1, 2].forEach((slot) => {
            const bet = betStore.bets[slot];
            if (bet && bet.status !== "won" && !bet.cashed_out_at) {
                betStore.bets[slot] = { ...bet, status: "lost" };
            }
        });

        // Add to history (keep newest 50)
        history.value.unshift(crashPoint.value);
        if (history.value.length > 50) history.value.length = 50;
    };

    /** Event: bet.placed – a player placed a bet */
    const onBetPlaced = (e) => {
        if (e.round_id !== roundId.value) return;

        // Normalize avatar: backend sends avatar_url (full URL) or fallback
        const avatarUrl =
            e.avatar_url || e.avatar || "/images/default-avatar.png";

        liveBets.value.push({
            id: e.id,
            username: e.username,
            user: { username: e.username, avatar_url: avatarUrl },
            amount: parseFloat(e.amount),
            currency: e.currency,
            bet_slot: e.bet_slot,
            cashed_out_at: null,
            cashout_multiplier: null,
            profit: null,
            lost: false,
        });
    };

    /** Event: bet.cashed_out – a player cashed out */
    const onBetCashedOut = (e) => {
        if (e.round_id !== roundId.value) return;
        const index = liveBets.value.findIndex((b) => b.id === e.id);
        if (index !== -1) {
            liveBets.value[index] = {
                ...liveBets.value[index],
                cashed_out_at: parseFloat(e.cashed_out_at),
                cashout_multiplier: parseFloat(e.cashed_out_at),
                payout: parseFloat(e.payout),
                profit: parseFloat(e.profit),
            };
        }

        // If this is the current user's bet (auto-cashout from server),
        // update betStore so BetPanel reflects the cashout immediately.
        const betStore = useBetStore();
        for (const slot of [1, 2]) {
            const userBet = betStore.bets[slot];
            if (userBet && userBet.id === e.id) {
                betStore.bets[slot] = {
                    ...userBet,
                    cashed_out_at: parseFloat(e.cashed_out_at),
                    payout: parseFloat(e.payout),
                    status: "won",
                };
                // Refresh wallet balance after auto-cashout credit
                const walletStore = useWalletStore();
                walletStore.fetchWallet();
                break;
            }
        }
    };

    // ── Init WebSocket channel ─────────────────────────────────────────────────
    const initGameChannel = () => {
        if (!window.Echo) return;
        if (echoChannel) return; // already subscribed

        echoChannel = window.Echo.channel("game")
            .listen(".countdown.tick", onCountdownTick)
            .listen(".betting.started", onBettingStarted)
            .listen(".round.started", onRoundStarted)
            .listen(".multiplier.updated", onMultiplierUpdated)
            .listen(".round.crashed", onRoundCrashed)
            .listen(".bet.placed", onBetPlaced)
            .listen(".bet.cashed_out", onBetCashedOut);
    };

    const leaveGameChannel = () => {
        if (window.Echo && echoChannel) {
            window.Echo.leaveChannel("game");
            echoChannel = null;
        }
        clearCountdown();
    };

    // ── Actions ────────────────────────────────────────────────────────────────
    const setStatus = (v) => {
        status.value = v;
    };
    const setMultiplier = (v) => {
        currentMultiplier.value = v;
    };
    const setCrashPoint = (v) => {
        crashPoint.value = v;
    };

    const startNewRound = (data) => {
        roundId.value = data.round_id;
        roundHash.value = data.hash ?? "";
        serverSeedHash.value = data.server_seed_hash ?? "";
        status.value = "betting";
        currentMultiplier.value = 1.0;
        crashPoint.value = null;
    };

    const endRound = (data) => {
        crashPoint.value = data.crash_point;
        status.value = "crashed";
        history.value.unshift(data.crash_point);
        if (history.value.length > 50) history.value.length = 50;
    };

    const fetchHistory = async () => {
        try {
            const res = await api.get("/game/history");
            const rows = res.data.data ?? [];
            // history can be array of objects {crash_point} or plain numbers
            history.value = rows.map((r) =>
                typeof r === "object"
                    ? parseFloat(r.crash_point)
                    : parseFloat(r),
            );
        } catch (err) {
            console.error("Failed to fetch game history:", err);
        }
    };

    const fetchCurrentState = async () => {
        try {
            const res = await api.get("/game/state");
            const round = res.data.data;
            if (!round) return;
            roundId.value = round.round_id;
            serverSeedHash.value = round.server_seed_hash ?? "";
            roundHash.value = round.server_seed_hash ?? "";
            status.value = round.status ?? "waiting";
        } catch (err) {
            console.error("Failed to fetch game state:", err);
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
        liveBets,
        // Computed
        isAcceptingBets,
        isRunning,
        hasCrashed,
        // Actions
        initGameChannel,
        leaveGameChannel,
        fetchHistory,
        fetchCurrentState,
        setStatus,
        setMultiplier,
        setCrashPoint,
        startNewRound,
        endRound,
        reset,
    };
});
