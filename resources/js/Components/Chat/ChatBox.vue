<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border flex flex-col h-full"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-4 py-2.5 border-b border-surface-light-border dark:border-surface-dark-border flex-shrink-0"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >
                💬 Chat
            </h3>
            <span class="text-xs text-slate-400"
                >{{ messages.length }} messages</span
            >
        </div>

        <!-- Messages -->
        <div
            ref="messagesContainer"
            class="flex-1 overflow-y-auto scrollbar-thin px-3 py-2 space-y-2 min-h-0"
        >
            <div
                v-for="msg in messages"
                :key="msg.id"
                :class="[
                    'flex gap-2',
                    msg.type === 'system' ? 'justify-center' : '',
                ]"
            >
                <!-- System message -->
                <template v-if="msg.type === 'system'">
                    <span class="text-xs text-slate-400 italic">{{
                        msg.content
                    }}</span>
                </template>

                <!-- User message -->
                <template v-else>
                    <img
                        :src="
                            msg.user?.avatar_url || '/images/default-avatar.png'
                        "
                        class="w-6 h-6 rounded-full object-cover flex-shrink-0 mt-0.5"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <span
                                class="text-xs font-semibold text-primary-500 truncate"
                                >{{ msg.user?.username || "Guest" }}</span
                            >
                            <span
                                class="text-[10px] text-slate-400 flex-shrink-0"
                                >{{ formatTimeAgo(msg.created_at) }}</span
                            >
                        </div>
                        <p
                            class="text-sm text-slate-700 dark:text-slate-300 break-words"
                        >
                            {{ msg.content }}
                        </p>
                    </div>
                </template>
            </div>

            <div
                v-if="!messages.length"
                class="flex items-center justify-center h-full text-sm text-slate-400"
            >
                No messages yet. Start chatting!
            </div>
        </div>

        <!-- Input -->
        <div
            class="border-t border-surface-light-border dark:border-surface-dark-border p-3 flex-shrink-0"
        >
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input
                    v-model="newMessage"
                    type="text"
                    maxlength="500"
                    :disabled="!canChat"
                    class="flex-1 px-3 py-2 rounded-xl border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition disabled:opacity-50"
                    :placeholder="
                        canChat ? 'Type a message...' : 'Login to chat'
                    "
                />
                <button
                    type="submit"
                    :disabled="!newMessage.trim() || !canChat"
                    class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Send
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, watch } from "vue";
import { timeAgo } from "@/Utils/formatters";

const props = defineProps({
    messages: { type: Array, default: () => [] },
    canChat: { type: Boolean, default: false },
});

const emit = defineEmits(["send-message"]);

const newMessage = ref("");
const messagesContainer = ref(null);

const formatTimeAgo = (date) => timeAgo(date);

const sendMessage = () => {
    const content = newMessage.value.trim();
    if (!content) return;
    emit("send-message", content);
    newMessage.value = "";
};

// Auto scroll to bottom on new messages
watch(
    () => props.messages.length,
    async () => {
        await nextTick();
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop =
                messagesContainer.value.scrollHeight;
        }
    },
);
</script>
