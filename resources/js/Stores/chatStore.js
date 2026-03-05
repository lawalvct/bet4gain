import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/Utils/api";

export const useChatStore = defineStore("chat", () => {
    // State
    const messages = ref([]);
    const onlineUsers = ref([]);
    const sending = ref(false);
    const maxMessages = 100;

    // Actions
    const fetchMessages = async () => {
        try {
            const response = await api.get("/chat/messages");
            messages.value = response.data.data || [];
        } catch (error) {
            console.error("Failed to fetch messages:", error);
        }
    };

    const sendMessage = async (content) => {
        sending.value = true;
        try {
            const response = await api.post("/chat/messages", { content });
            return response.data;
        } catch (error) {
            throw error;
        } finally {
            sending.value = false;
        }
    };

    const addMessage = (message) => {
        messages.value.push(message);
        // Keep only the last N messages
        if (messages.value.length > maxMessages) {
            messages.value = messages.value.slice(-maxMessages);
        }
    };

    const removeMessage = (messageId) => {
        messages.value = messages.value.filter((m) => m.id !== messageId);
    };

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
        // Actions
        fetchMessages,
        sendMessage,
        addMessage,
        removeMessage,
        setOnlineUsers,
        addOnlineUser,
        removeOnlineUser,
        clearMessages,
    };
});
