import { ref, onMounted, onUnmounted } from "vue";
import { CHANNELS } from "@/Utils/constants";

/**
 * Composable for managing Reverb presence channel subscription.
 * Tracks online users in real time.
 */
export function usePresence() {
    const onlineUsers = ref([]);
    const onlineCount = ref(0);
    let channel = null;

    const join = () => {
        if (!window.Echo) return;

        channel = window.Echo.join(CHANNELS.ONLINE.replace("presence-", ""));

        channel
            .here((users) => {
                onlineUsers.value = users;
                onlineCount.value = users.length;
            })
            .joining((user) => {
                if (!onlineUsers.value.find((u) => u.id === user.id)) {
                    onlineUsers.value.push(user);
                }
                onlineCount.value = onlineUsers.value.length;
            })
            .leaving((user) => {
                onlineUsers.value = onlineUsers.value.filter(
                    (u) => u.id !== user.id,
                );
                onlineCount.value = onlineUsers.value.length;
            })
            .error((error) => {
                console.error("Presence channel error:", error);
            });
    };

    const leave = () => {
        if (window.Echo && channel) {
            window.Echo.leave(CHANNELS.ONLINE.replace("presence-", ""));
            channel = null;
        }
    };

    onMounted(() => {
        join();
    });

    onUnmounted(() => {
        leave();
    });

    return {
        onlineUsers,
        onlineCount,
        join,
        leave,
    };
}
