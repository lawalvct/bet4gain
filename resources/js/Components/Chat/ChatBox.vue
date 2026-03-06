<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border flex flex-col h-full"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-3 py-2 lg:py-1.5 border-b border-surface-light-border dark:border-surface-dark-border flex-shrink-0"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >
                💬 Chat
            </h3>
            <span class="text-xs text-slate-400"
                >{{ chatStore.messages.length }} messages</span
            >
        </div>

        <!-- Messages -->
        <div
            ref="messagesContainer"
            class="flex-1 overflow-y-auto scrollbar-thin px-3 py-2 space-y-2 min-h-0"
            @scroll="onScroll"
        >
            <!-- Load Older -->
            <div v-if="hasOlder" class="text-center py-1">
                <button
                    @click="loadOlder"
                    :disabled="loadingOlder"
                    class="text-xs text-primary-500 hover:text-primary-600 transition disabled:opacity-50"
                >
                    {{ loadingOlder ? "Loading..." : "↑ Load older messages" }}
                </button>
            </div>

            <div
                v-for="msg in chatStore.messages"
                :key="msg.id"
                :class="[
                    'group flex gap-2',
                    msg.type === 'system' ? 'justify-center' : '',
                ]"
            >
                <!-- System message -->
                <template v-if="msg.type === 'system'">
                    <div
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 max-w-full"
                    >
                        <span
                            class="text-xs text-primary-600 dark:text-primary-400 font-medium truncate"
                            >{{ msg.message }}</span
                        >
                    </div>
                </template>

                <!-- User message -->
                <template v-else>
                    <img
                        :src="
                            msg.avatar ||
                            msg.user?.avatar_url ||
                            defaultAvatar(msg.username || msg.user?.username)
                        "
                        class="w-6 h-6 rounded-full object-cover flex-shrink-0 mt-0.5 cursor-pointer hover:ring-2 hover:ring-primary-500 transition"
                        @click="showProfile($event, msg.user_id)"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <span
                                class="text-xs font-semibold text-primary-500 truncate cursor-pointer hover:underline"
                                @click="showProfile($event, msg.user_id)"
                                >{{
                                    msg.username ||
                                    msg.user?.username ||
                                    "Guest"
                                }}</span
                            >
                            <span
                                class="text-[10px] text-slate-400 flex-shrink-0"
                                >{{ formatTimeAgo(msg.created_at) }}</span
                            >
                            <!-- Admin delete button -->
                            <button
                                v-if="isModerator"
                                @click="deleteMessage(msg.id)"
                                class="text-[10px] text-slate-300 hover:text-game-red opacity-0 group-hover:opacity-100 transition ml-auto flex-shrink-0"
                                title="Delete message"
                            >
                                ✕
                            </button>
                        </div>
                        <p
                            class="text-sm text-slate-700 dark:text-slate-300 break-words"
                        >
                            {{ msg.message }}
                        </p>
                    </div>
                </template>
            </div>

            <div
                v-if="!chatStore.messages.length"
                class="flex items-center justify-center h-full text-sm text-slate-400"
            >
                No messages yet. Start chatting!
            </div>
        </div>

        <!-- Rate limit indicator -->
        <div v-if="rateLimitRemaining > 0" class="px-4 py-1 text-center">
            <span class="text-[10px] text-slate-400"
                >Wait {{ rateLimitRemaining }}s</span
            >
        </div>

        <!-- Input -->
        <div
            class="border-t border-surface-light-border dark:border-surface-dark-border p-2 flex-shrink-0"
        >
            <!-- Error message -->
            <p v-if="sendError" class="text-xs text-game-red mb-1.5">
                {{ sendError }}
            </p>

            <form
                @submit.prevent="sendMessage"
                class="flex gap-1.5 items-center relative"
            >
                <!-- Emoji Picker Toggle -->
                <button
                    type="button"
                    @click.stop="showEmojiPicker = !showEmojiPicker"
                    class="p-1.5 text-slate-400 hover:text-primary-500 transition flex-shrink-0"
                    :disabled="!canChat"
                    title="Emoji"
                >
                    😊
                </button>

                <!-- Emoji Picker -->
                <EmojiPicker v-if="showEmojiPicker" @select="insertEmoji" />

                <input
                    ref="inputRef"
                    v-model="newMessage"
                    type="text"
                    maxlength="200"
                    :disabled="!canChat"
                    class="flex-1 min-w-0 px-2.5 py-1.5 rounded-lg border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition disabled:opacity-50"
                    :placeholder="
                        canChat ? 'Message... (/help)' : 'Login to chat'
                    "
                    @keydown.escape="showEmojiPicker = false"
                />
                <button
                    type="submit"
                    :disabled="
                        !newMessage.trim() || !canChat || chatStore.sending
                    "
                    class="px-2.5 py-1.5 bg-primary-500 hover:bg-primary-600 text-white text-xs font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                >
                    {{ chatStore.sending ? "..." : "Send" }}
                </button>
            </form>
        </div>

        <!-- Player Profile Popover -->
        <PlayerProfilePopover
            :visible="popover.visible"
            :user-id="popover.userId"
            :anchor-x="popover.x"
            :anchor-y="popover.y"
            :is-moderator="isModerator"
            @close="popover.visible = false"
            @mute="muteUser"
        />
    </div>
</template>

<script setup>
import {
    ref,
    reactive,
    computed,
    nextTick,
    watch,
    onMounted,
    onUnmounted,
} from "vue";
import { timeAgo } from "@/Utils/formatters";
import { useChatStore } from "@/Stores/chatStore";
import { useUserStore } from "@/Stores/userStore";
import EmojiPicker from "./EmojiPicker.vue";
import PlayerProfilePopover from "./PlayerProfilePopover.vue";
import api from "@/Utils/api";

const chatStore = useChatStore();
const userStore = useUserStore();

const newMessage = ref("");
const messagesContainer = ref(null);
const inputRef = ref(null);
const showEmojiPicker = ref(false);
const loadingOlder = ref(false);
const hasOlder = ref(true);
const sendError = ref("");
const rateLimitRemaining = ref(0);
let rateLimitTimer = null;

const canChat = computed(() => !!userStore.user && !userStore.user.is_banned);
const isModerator = computed(() => {
    const role = userStore.user?.role;
    return role === "admin" || role === "moderator";
});

// ── Popover state ──
const popover = reactive({
    visible: false,
    userId: null,
    x: 0,
    y: 0,
});

const defaultAvatar = (username) => {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "?")}&background=random&color=fff&size=32`;
};

const formatTimeAgo = (date) => timeAgo(date);

const sendMessage = async () => {
    const content = newMessage.value.trim();
    if (!content || chatStore.sending) return;

    sendError.value = "";
    newMessage.value = "";

    try {
        const result = await chatStore.sendMessage(content);

        // Handle system response (e.g. /help command)
        if (result?.system) {
            sendError.value = result.system; // show as info, not error
        }

        // Start rate limit countdown
        startRateLimitTimer();
    } catch (e) {
        sendError.value =
            e.response?.data?.message || "Failed to send message.";
        // Restore message on error
        newMessage.value = content;
    }
};

const startRateLimitTimer = () => {
    const rateLimit = 3; // seconds
    rateLimitRemaining.value = rateLimit;

    if (rateLimitTimer) clearInterval(rateLimitTimer);
    rateLimitTimer = setInterval(() => {
        rateLimitRemaining.value--;
        if (rateLimitRemaining.value <= 0) {
            clearInterval(rateLimitTimer);
            rateLimitTimer = null;
        }
    }, 1000);
};

const insertEmoji = (emoji) => {
    newMessage.value += emoji;
    showEmojiPicker.value = false;
    inputRef.value?.focus();
};

const loadOlder = async () => {
    if (loadingOlder.value || !chatStore.messages.length) return;
    loadingOlder.value = true;

    const oldestId = chatStore.messages[0]?.id;
    const count = await chatStore.loadOlderMessages(oldestId);

    if (count === 0) {
        hasOlder.value = false;
    }

    loadingOlder.value = false;
};

const onScroll = () => {
    // Could trigger load-older when scrolled to top
    if (messagesContainer.value?.scrollTop === 0 && hasOlder.value) {
        // User scrolled to top — they can click the button
    }
};

const deleteMessage = async (messageId) => {
    try {
        await api.delete(`/api/chat/messages/${messageId}`);
        chatStore.removeMessage(messageId);
    } catch (e) {
        console.error("Failed to delete message:", e);
    }
};

const muteUser = async (userId) => {
    try {
        await api.post("/api/chat/mute", { user_id: userId, minutes: 10 });
        popover.visible = false;
    } catch (e) {
        console.error("Failed to mute user:", e);
    }
};

const showProfile = (event, userId) => {
    if (!userId) return;
    popover.userId = userId;
    popover.x = event.clientX;
    popover.y = event.clientY + 10;
    popover.visible = true;
};

// Close emoji picker on outside click
const closeEmojiPicker = (e) => {
    if (showEmojiPicker.value) {
        showEmojiPicker.value = false;
    }
};

// Auto scroll to bottom on new messages
watch(
    () => chatStore.messages.length,
    async (newLen, oldLen) => {
        // Only auto-scroll if user hasn't scrolled up
        if (!messagesContainer.value) return;
        const el = messagesContainer.value;
        const isNearBottom =
            el.scrollHeight - el.scrollTop - el.clientHeight < 80;

        if (isNearBottom || newLen > oldLen) {
            await nextTick();
            el.scrollTop = el.scrollHeight;
        }
    },
);

onMounted(() => {
    chatStore.init();
    document.addEventListener("click", closeEmojiPicker);
});

onUnmounted(() => {
    chatStore.destroy();
    document.removeEventListener("click", closeEmojiPicker);
    if (rateLimitTimer) clearInterval(rateLimitTimer);
});
</script>
