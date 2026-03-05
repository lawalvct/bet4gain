<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark">
        <!-- Header -->
        <header
            class="sticky top-0 z-50 bg-surface-light-card/80 dark:bg-surface-dark-card/80 backdrop-blur-xl border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <div
                class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between"
            >
                <a href="/" class="flex items-center gap-2 group">
                    <span
                        class="text-2xl transition-transform group-hover:scale-110"
                        >🎮</span
                    >
                    <span class="text-lg font-bold text-primary-500"
                        >Bet4Gain</span
                    >
                </a>
                <nav class="flex items-center gap-3 text-sm">
                    <a
                        href="/"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >Game</a
                    >
                    <a
                        href="/leaderboard"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >Leaderboard</a
                    >
                    <a
                        href="/history"
                        class="text-slate-500 hover:text-primary-500 transition"
                        >History</a
                    >
                    <span class="text-primary-500 font-semibold"
                        >Provably Fair</span
                    >
                </nav>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 py-6 space-y-6">
            <!-- Title -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    🔒 Provably Fair
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Verify that every game round is cryptographically fair and
                    has not been manipulated.
                </p>
            </div>

            <!-- How it works -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-5"
            >
                <h3
                    class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3"
                >
                    📖 How It Works
                </h3>
                <div
                    class="space-y-2 text-sm text-slate-600 dark:text-slate-400"
                >
                    <p>
                        <strong>1.</strong> Before each round, the server
                        generates a <em>server seed</em> and shows you its
                        <strong>SHA-256 hash</strong> (seed hash). This proves
                        the crash point was determined before play.
                    </p>
                    <p>
                        <strong>2.</strong> A <em>client seed</em> (shared
                        public seed) and a <em>nonce</em> (round counter) are
                        combined with the server seed to compute the crash point
                        using HMAC-SHA256.
                    </p>
                    <p>
                        <strong>3.</strong> After the round crashes, the server
                        seed is revealed. You can independently verify the crash
                        point by entering the seeds and nonce below.
                    </p>
                    <p class="text-xs text-slate-400 mt-2">
                        Formula:
                        <code
                            class="px-1.5 py-0.5 bg-surface-light dark:bg-surface-dark rounded text-primary-500"
                            >crash_point = max(1.00, (2^52 / (2^52 - h)) × (1 -
                            house_edge))</code
                        >
                        where
                        <code
                            class="px-1 py-0.5 bg-surface-light dark:bg-surface-dark rounded"
                            >h</code
                        >
                        is derived from HMAC-SHA256(server_seed,
                        client_seed:nonce).
                    </p>
                </div>
            </div>

            <!-- Verification Form -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-5"
            >
                <h3
                    class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4"
                >
                    🔍 Verify a Round
                </h3>

                <!-- Quick load from round -->
                <div v-if="!showManual" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-medium text-slate-500 mb-1.5"
                            >Round ID</label
                        >
                        <div class="flex gap-2">
                            <input
                                v-model="roundId"
                                type="number"
                                min="1"
                                placeholder="Enter round ID (e.g. 42)"
                                class="flex-1 px-3 py-2.5 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            />
                            <button
                                @click="loadRound"
                                :disabled="!roundId || loadingRound"
                                class="px-4 py-2.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-xl transition disabled:opacity-50"
                            >
                                {{ loadingRound ? "Loading..." : "Load Round" }}
                            </button>
                        </div>
                    </div>

                    <div class="text-center">
                        <button
                            @click="showManual = true"
                            class="text-xs text-primary-500 hover:text-primary-600 transition"
                        >
                            Or enter seeds manually ↓
                        </button>
                    </div>
                </div>

                <!-- Manual / Loaded seed entry -->
                <div v-if="showManual || roundData" class="space-y-4">
                    <div v-if="showManual && !roundData" class="text-right">
                        <button
                            @click="
                                showManual = false;
                                clearForm();
                            "
                            class="text-xs text-slate-400 hover:text-slate-600 transition"
                        >
                            ← Back to round lookup
                        </button>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-slate-500 mb-1.5"
                            >Server Seed</label
                        >
                        <input
                            v-model="form.serverSeed"
                            type="text"
                            placeholder="64-character hex string"
                            class="w-full px-3 py-2.5 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm font-mono placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-slate-500 mb-1.5"
                            >Server Seed Hash (SHA-256)</label
                        >
                        <input
                            v-model="form.serverSeedHash"
                            type="text"
                            placeholder="64-character hex hash"
                            class="w-full px-3 py-2.5 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm font-mono placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                                >Client Seed</label
                            >
                            <input
                                v-model="form.clientSeed"
                                type="text"
                                placeholder="Public seed"
                                class="w-full px-3 py-2.5 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm font-mono placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5"
                                >Nonce</label
                            >
                            <input
                                v-model.number="form.nonce"
                                type="number"
                                min="0"
                                placeholder="Round nonce"
                                class="w-full px-3 py-2.5 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm font-mono placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                            />
                        </div>
                    </div>

                    <button
                        @click="verify"
                        :disabled="!canVerify || verifying"
                        class="w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-xl transition disabled:opacity-50"
                    >
                        {{
                            verifying ? "Verifying..." : "🔍 Verify Crash Point"
                        }}
                    </button>
                </div>

                <!-- Error -->
                <p v-if="error" class="text-sm text-game-red mt-3">
                    {{ error }}
                </p>
            </div>

            <!-- Verification Result -->
            <div
                v-if="result"
                :class="[
                    'rounded-xl border p-5',
                    result.valid
                        ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800',
                ]"
            >
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">{{
                        result.valid ? "✅" : "❌"
                    }}</span>
                    <div>
                        <h3
                            :class="[
                                'font-bold text-lg',
                                result.valid
                                    ? 'text-green-700 dark:text-green-400'
                                    : 'text-red-700 dark:text-red-400',
                            ]"
                        >
                            {{
                                result.valid
                                    ? "Verified — Fair!"
                                    : "Verification Failed"
                            }}
                        </h3>
                        <p class="text-sm text-slate-500">
                            {{
                                result.valid
                                    ? "The crash point matches the provided seeds."
                                    : "The seeds do not match. The server seed hash does not correspond to the provided server seed."
                            }}
                        </p>
                    </div>
                </div>

                <div v-if="result.valid" class="grid grid-cols-2 gap-4">
                    <div class="bg-white/50 dark:bg-black/10 rounded-lg p-3">
                        <div class="text-xs text-slate-500 mb-1">
                            Calculated Crash Point
                        </div>
                        <div
                            class="text-2xl font-bold text-primary-500 tabular-nums"
                        >
                            {{ formatMultiplier(result.crash_point) }}
                        </div>
                    </div>
                    <div class="bg-white/50 dark:bg-black/10 rounded-lg p-3">
                        <div class="text-xs text-slate-500 mb-1">
                            Hash Verified
                        </div>
                        <div
                            class="text-lg font-bold text-green-600 dark:text-green-400"
                        >
                            {{ result.hash_valid ? "✓ Valid" : "✗ Invalid" }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loaded Round Info -->
            <div
                v-if="roundData"
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border p-5"
            >
                <h3
                    class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3"
                >
                    📋 Round #{{ roundData.id }} Details
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-500 block">Status</span>
                        <span
                            class="font-medium text-slate-800 dark:text-white capitalize"
                            >{{ roundData.status }}</span
                        >
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block"
                            >Crash Point</span
                        >
                        <span class="font-bold text-primary-500 tabular-nums">
                            {{
                                roundData.crash_point
                                    ? formatMultiplier(roundData.crash_point)
                                    : "N/A"
                            }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Bets</span>
                        <span
                            class="font-medium text-slate-800 dark:text-white"
                            >{{ roundData.bet_count }}</span
                        >
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block"
                            >Duration</span
                        >
                        <span
                            class="font-medium text-slate-800 dark:text-white"
                        >
                            {{
                                roundData.duration_ms
                                    ? (roundData.duration_ms / 1000).toFixed(
                                          1,
                                      ) + "s"
                                    : "N/A"
                            }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block"
                            >Crashed At</span
                        >
                        <span
                            class="font-medium text-slate-800 dark:text-white text-xs"
                        >
                            {{
                                roundData.crashed_at
                                    ? formatDate(roundData.crashed_at)
                                    : "N/A"
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { formatMultiplier, formatDate } from "@/Utils/formatters";
import api from "@/Utils/api";

const roundId = ref("");
const loadingRound = ref(false);
const roundData = ref(null);
const showManual = ref(false);
const verifying = ref(false);
const error = ref("");
const result = ref(null);

const form = reactive({
    serverSeed: "",
    serverSeedHash: "",
    clientSeed: "",
    nonce: 0,
});

const canVerify = computed(
    () =>
        form.serverSeed.length === 64 &&
        form.serverSeedHash.length === 64 &&
        form.clientSeed.length > 0 &&
        form.nonce >= 0,
);

const loadRound = async () => {
    if (!roundId.value) return;
    loadingRound.value = true;
    error.value = "";
    result.value = null;

    try {
        const response = await api.get(`/api/game/round/${roundId.value}`);
        const data = response.data.data;
        roundData.value = data;

        // Populate form if seeds are revealed (round is crashed)
        if (data.server_seed) {
            form.serverSeed = data.server_seed;
            form.serverSeedHash = data.server_seed_hash;
            form.clientSeed = data.client_seed || "";
            form.nonce = data.nonce || 0;
            showManual.value = true;
        } else {
            form.serverSeedHash = data.server_seed_hash;
            error.value = "Seeds are hidden until this round completes.";
        }
    } catch (e) {
        error.value = e.response?.data?.message || "Round not found.";
        roundData.value = null;
    } finally {
        loadingRound.value = false;
    }
};

const verify = async () => {
    verifying.value = true;
    error.value = "";
    result.value = null;

    try {
        const response = await api.post("/api/game/verify", {
            server_seed: form.serverSeed,
            server_seed_hash: form.serverSeedHash,
            client_seed: form.clientSeed,
            nonce: form.nonce,
        });
        result.value = response.data.data;
    } catch (e) {
        error.value = e.response?.data?.message || "Verification failed.";
    } finally {
        verifying.value = false;
    }
};

const clearForm = () => {
    form.serverSeed = "";
    form.serverSeedHash = "";
    form.clientSeed = "";
    form.nonce = 0;
    result.value = null;
    roundData.value = null;
    error.value = "";
};

// Check URL params for ?round=X
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const round = params.get("round");
    if (round) {
        roundId.value = round;
        loadRound();
    }
});
</script>
