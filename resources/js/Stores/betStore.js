import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/Utils/api";

export const useBetStore = defineStore("bet", () => {
    // State — supports dual bet slots
    const bets = ref({ 1: null, 2: null }); // slot 1 and slot 2
    const liveBets = ref([]); // all players' bets for current round
    const placing = ref(false);
    const cashingOut = ref(false);

    // Auto-bet state
    const autoBetActive = ref(false);
    const autoBetConfig = ref(null);
    const autoBetRoundsPlayed = ref(0);
    const autoBetProfit = ref(0);

    // Computed
    const hasBet = computed(() => (slot) => bets.value[slot] !== null);
    const currentBet = computed(() => (slot) => bets.value[slot]);
    const totalPool = computed(() =>
        liveBets.value.reduce((sum, b) => sum + (b.amount || 0), 0),
    );
    const liveBetCount = computed(() => liveBets.value.length);

    // Actions
    const placeBet = async ({
        amount,
        autoCashout,
        slot = 1,
        currency = "COINS",
    }) => {
        placing.value = true;
        try {
            const response = await api.post("/game/bet", {
                amount,
                auto_cashout_at: autoCashout,
                bet_slot: slot,
                currency,
            });
            bets.value[slot] = response.data.data;
            return response.data;
        } catch (error) {
            throw error;
        } finally {
            placing.value = false;
        }
    };

    const cashout = async ({ slot = 1 }) => {
        cashingOut.value = true;
        try {
            const bet = bets.value[slot];
            const response = await api.post("/game/cashout", {
                bet_slot: slot,
                bet_id: bet?.id ?? null,
            });
            if (bets.value[slot]) {
                bets.value[slot] = {
                    ...bets.value[slot],
                    ...response.data.data,
                };
            }
            return response.data;
        } catch (error) {
            throw error;
        } finally {
            cashingOut.value = false;
        }
    };

    const cancelBet = async ({ slot = 1 }) => {
        try {
            await api.post("/game/cancel-bet", { bet_slot: slot });
            bets.value[slot] = null;
        } catch (error) {
            throw error;
        }
    };

    const setLiveBets = (newBets) => {
        liveBets.value = newBets;
    };

    const addLiveBet = (bet) => {
        liveBets.value.push(bet);
    };

    const updateLiveBet = (updatedBet) => {
        const index = liveBets.value.findIndex((b) => b.id === updatedBet.id);
        if (index !== -1) {
            liveBets.value[index] = { ...liveBets.value[index], ...updatedBet };
        }
    };

    const clearRound = () => {
        bets.value = { 1: null, 2: null };
        liveBets.value = [];
    };

    // Auto-bet
    const startAutoBet = (config) => {
        autoBetActive.value = true;
        autoBetConfig.value = config;
        autoBetRoundsPlayed.value = 0;
        autoBetProfit.value = 0;
    };

    const stopAutoBet = () => {
        autoBetActive.value = false;
        autoBetConfig.value = null;
    };

    const incrementAutoRound = (profit) => {
        autoBetRoundsPlayed.value++;
        autoBetProfit.value += profit;

        // Check stop conditions
        if (autoBetConfig.value) {
            const config = autoBetConfig.value;
            if (
                config.rounds > 0 &&
                autoBetRoundsPlayed.value >= config.rounds
            ) {
                stopAutoBet();
            }
            if (
                config.stopProfit > 0 &&
                autoBetProfit.value >= config.stopProfit
            ) {
                stopAutoBet();
            }
            if (
                config.stopLoss > 0 &&
                autoBetProfit.value <= -config.stopLoss
            ) {
                stopAutoBet();
            }
        }
    };

    return {
        // State
        bets,
        liveBets,
        placing,
        cashingOut,
        autoBetActive,
        autoBetConfig,
        autoBetRoundsPlayed,
        autoBetProfit,
        // Computed
        hasBet,
        currentBet,
        totalPool,
        liveBetCount,
        // Actions
        placeBet,
        cashout,
        cancelBet,
        setLiveBets,
        addLiveBet,
        updateLiveBet,
        clearRound,
        startAutoBet,
        stopAutoBet,
        incrementAutoRound,
    };
});
