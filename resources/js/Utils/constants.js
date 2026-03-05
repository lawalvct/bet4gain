// Game constants
export const GAME_STATUS = {
    WAITING: "waiting",
    BETTING: "betting",
    RUNNING: "running",
    CRASHED: "crashed",
};

export const BET_STATUS = {
    PENDING: "pending",
    ACTIVE: "active",
    WON: "won",
    LOST: "lost",
    CANCELLED: "cancelled",
};

// Quick bet amounts
export const QUICK_BET_AMOUNTS = [10, 50, 100, 500, 1000, 5000];

// Auto-cashout presets
export const AUTO_CASHOUT_PRESETS = [1.5, 2.0, 3.0, 5.0, 10.0];

// Canvas rendering
export const CANVAS = {
    CURVE_COLOR: "#22c55e",
    CURVE_COLOR_CRASHED: "#ef4444",
    CURVE_WIDTH: 3,
    GRID_COLOR_DARK: "rgba(255, 255, 255, 0.05)",
    GRID_COLOR_LIGHT: "rgba(0, 0, 0, 0.05)",
    MULTIPLIER_FONT: "bold 48px Inter",
    FPS_TARGET: 60,
};

// Sound effects paths
export const SOUNDS = {
    BET_PLACED: "/assets/sounds/bet-placed.mp3",
    CASHOUT: "/assets/sounds/cashout.mp3",
    CRASH: "/assets/sounds/crash.mp3",
    TICK: "/assets/sounds/tick.mp3",
    CHAT: "/assets/sounds/chat.mp3",
    WIN: "/assets/sounds/win.mp3",
};

// WebSocket channels
export const CHANNELS = {
    GAME: "game",
    CHAT: "chat",
    ONLINE: "presence-online",
    USER: (id) => `private-user.${id}`,
};

// WebSocket events
export const EVENTS = {
    GAME_ROUND_STARTED: "GameRoundStarted",
    GAME_RUNNING: "GameRunning",
    MULTIPLIER_TICK: "MultiplierTick",
    GAME_CRASHED: "GameCrashed",
    BET_PLACED: "BetPlaced",
    PLAYER_CASHED_OUT: "PlayerCashedOut",
    CHAT_MESSAGE: "ChatMessageSent",
    BALANCE_UPDATED: "BalanceUpdated",
};
