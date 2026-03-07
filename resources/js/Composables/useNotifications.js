import { useToast } from "@/Composables/useToast";
import { useWalletStore } from "@/Stores/walletStore";
import { useSound } from "@/Composables/useSound";

let privateChannel = null;
let subscribedUserId = null;

/**
 * Composable for listening to private user notifications via WebSocket.
 * Handles: coin transfer received alerts, balance refresh.
 *
 * Call this in any page component that should show real-time alerts.
 * Singleton pattern — only one subscription active at a time.
 */
export function useNotifications(userId) {
    const { toast } = useToast();
    const walletStore = useWalletStore();
    const sound = useSound();

    const subscribe = () => {
        if (!userId) return;

        // Already subscribed for this user
        if (privateChannel && subscribedUserId === userId) return;

        // Clean up previous subscription if user changed
        if (privateChannel) {
            unsubscribe();
        }

        // Wait for Echo to be ready (it may not exist at the instant setup runs)
        if (!window.Echo) {
            const checkInterval = setInterval(() => {
                if (window.Echo) {
                    clearInterval(checkInterval);
                    doSubscribe();
                }
            }, 200);
            // Give up after 10 seconds
            setTimeout(() => clearInterval(checkInterval), 10000);
            return;
        }

        doSubscribe();
    };

    const doSubscribe = () => {
        subscribedUserId = userId;
        privateChannel = window.Echo.private(`user.${userId}`);

        // ── Coin Transfer Received ─────────────────────────────────────────
        privateChannel.listen(".coin.transfer.received", (data) => {
            const amount = Number(data.amount).toLocaleString();
            const sender = data.sender;
            const type = data.type === "gift" ? "🎁 Gift" : "💸 Transfer";
            const note = data.note ? ` — "${data.note}"` : "";

            toast.transfer(
                `${type} — You received 🪙 ${amount} coins from @${sender}${note}`,
                "Coins Received!",
            );

            // Play the win sound for a nice alert
            sound.win();

            // Refresh wallet balance immediately
            walletStore.fetchWallet();
        });
    };

    const unsubscribe = () => {
        if (window.Echo && privateChannel) {
            window.Echo.leaveChannel(`private-user.${subscribedUserId}`);
            privateChannel = null;
            subscribedUserId = null;
        }
    };

    // Auto-subscribe on setup
    subscribe();

    // Don't auto-cleanup on unmount — keep listening across page navigation
    // The singleton pattern ensures only one subscription exists.
    // Only clean up if we want to fully disconnect.

    return { subscribe, unsubscribe };
}
