import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/Utils/api";
import { CHANNELS, EVENTS } from "@/Utils/constants";

export const useChatStore = defineStore("chat", () => {
    // State
    const messages = ref([]);
    const onlineUsers = ref([]);
    const sending = ref(false);
    const initialized = ref(false);
    const maxMessages = 150;

    let chatChannel = null;

    // ── Actions ──

    /**
     * Initialize: fetch messages + subscribe to Echo channel.
     */
    const init = async () => {
        if (initialized.value) return;
        initialized.value = true;

        await fetchMessages();
        subscribeToChatChannel();
    };

    /**
     * Teardown: leave Echo channel.
     */
    const destroy = () => {
        if (chatChannel && window.Echo) {
            window.Echo.leave(CHANNELS.CHAT);
            chatChannel = null;
        }
        initialized.value = false;
    };

    /**
     * Fetch recent messages from API.
     */
    const fetchMessages = async () => {
        try {
            const response = await api.get("/chat/messages");
            messages.value = response.data.data || [];
        } catch (error) {
            console.error("Failed to fetch messages:", error);
        }
    };

    /**
     * Load older messages (infinite scroll / load-more).
     * Returns the number of messages loaded.
     */
    const loadOlderMessages = async (beforeId) => {
        try {
            const response = await api.get("/chat/messages/older", {
                params: { before_id: beforeId, limit: 30 },
            });
            const older = response.data.data || [];
            if (older.length > 0) {
                messages.value = [...older, ...messages.value];
            }
            return older.length;
        } catch (error) {
            console.error("Failed to load older messages:", error);
            return 0;
        }
    };

    /**
     * Send a message via API. The broadcast event will add it to the list.
     */
    const sendMessage = async (content) => {
        sending.value = true;
        try {
            const response = await api.post("/chat/messages", {
                message: content,
            });
            return response.data;
        } catch (error) {
            throw error;
        } finally {
            sending.value = false;
        }
    };

    /**
     * Subscribe to the chat channel for real-time messages.
     */
    const subscribeToChatChannel = () => {
        if (!window.Echo) return;

        chatChannel = window.Echo.channel(CHANNELS.CHAT);

        chatChannel.listen(`.${EVENTS.CHAT_MESSAGE}`, (data) => {
            addMessage(data);
        });

        chatChannel.listen(".ChatMessageDeleted", (data) => {
            removeMessage(data.id);
        });
    };

    /**
     * Add a message to the list (from broadcast or local).
     */
    const addMessage = (message) => {
        // Avoid duplicates
        if (messages.value.find((m) => m.id === message.id)) return;

        messages.value.push(message);

        // Keep only the last N messages
        if (messages.value.length > maxMessages) {
            messages.value = messages.value.slice(-maxMessages);
        }
    };

    /**
     * Remove a message by ID (admin delete).
     */
    const removeMessage = (messageId) => {
        messages.value = messages.value.filter((m) => m.id !== messageId);
    };

    // ── Online users (managed by presence composable, synced here) ──

    const setOnlineUsers = (users) => {
        onlineUsers.value = users;
    };

    const addOnlineUser = (user) => {
        if (!onlineUsers.value.find((u) => u.id === user.id)) {
            onlineUsers.value.push(user);
        }
    };

    const removeOnlineUser = (userId) => {
        onlineUsers.value = onlineUsers.value.filter((u) => u.id !== userId);
    };

    const clearMessages = () => {
        messages.value = [];
    };

    return {
        // State
        messages,
        onlineUsers,
        sending,
        initialized,
        // Actions
        init,
        destroy,
        fetchMessages,
        loadOlderMessages,
        sendMessage,
        addMessage,
        removeMessage,
        setOnlineUsers,
        addOnlineUser,
        removeOnlineUser,
        clearMessages,
    };
});
