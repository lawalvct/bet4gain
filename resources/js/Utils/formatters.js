/**
 * Format a number as currency (NGN).
 */
export function formatCurrency(amount, currency = "NGN") {
    return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: currency,
        minimumFractionDigits: 2,
    }).format(amount);
}

/**
 * Format coins with appropriate decimals.
 */
export function formatCoins(amount) {
    return new Intl.NumberFormat("en", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

/**
 * Format a multiplier value (e.g., 2.45x).
 */
export function formatMultiplier(value) {
    return parseFloat(value).toFixed(2) + "x";
}

/**
 * Format a date relative to now (e.g., "2 mins ago").
 */
export function timeAgo(date) {
    const now = new Date();
    const then = new Date(date);
    const seconds = Math.floor((now - then) / 1000);

    if (seconds < 60) return "just now";
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
    return then.toLocaleDateString();
}

/**
 * Format a date to a readable string.
 */
export function formatDate(date) {
    return new Date(date).toLocaleDateString("en", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

/**
 * Get crash point color class based on value.
 */
export function getCrashPointColor(value) {
    if (value >= 10) return "crash-pill-purple";
    if (value >= 2) return "crash-pill-green";
    if (value >= 1.5) return "crash-pill-yellow";
    return "crash-pill-red";
}

/**
 * Shorten a username for display.
 */
export function shortenUsername(username, maxLength = 12) {
    if (username.length <= maxLength) return username;
    return username.substring(0, maxLength - 2) + "..";
}
