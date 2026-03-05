<template>
    <div
        class="absolute bottom-full left-0 mb-2 bg-surface-light-card dark:bg-surface-dark-card border border-surface-light-border dark:border-surface-dark-border rounded-xl shadow-modal p-3 w-72 z-50"
        @click.stop
    >
        <!-- Search -->
        <input
            v-model="search"
            type="text"
            placeholder="Search emoji..."
            class="w-full px-3 py-1.5 text-sm rounded-lg border border-surface-light-border dark:border-surface-dark-border bg-surface-light dark:bg-surface-dark text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500/30 focus:border-transparent mb-2"
        />

        <!-- Category Tabs -->
        <div class="flex gap-1 mb-2 overflow-x-auto scrollbar-thin pb-1">
            <button
                v-for="(cat, key) in categories"
                :key="key"
                @click="activeCategory = key"
                :class="[
                    'px-2 py-1 rounded-md text-sm transition flex-shrink-0',
                    activeCategory === key
                        ? 'bg-primary-500 text-white'
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-surface-dark-alt',
                ]"
                :title="cat.name"
            >
                {{ cat.icon }}
            </button>
        </div>

        <!-- Emoji Grid -->
        <div
            class="grid grid-cols-8 gap-1 max-h-40 overflow-y-auto scrollbar-thin"
        >
            <button
                v-for="emoji in filteredEmojis"
                :key="emoji"
                @click="$emit('select', emoji)"
                class="w-8 h-8 flex items-center justify-center text-lg hover:bg-slate-100 dark:hover:bg-surface-dark-alt rounded-md transition"
            >
                {{ emoji }}
            </button>
        </div>

        <div
            v-if="filteredEmojis.length === 0"
            class="text-center text-sm text-slate-400 py-4"
        >
            No emoji found
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

defineEmits(["select"]);

const search = ref("");
const activeCategory = ref("smileys");

const categories = {
    smileys: {
        name: "Smileys",
        icon: "😀",
        emojis: [
            "😀",
            "😃",
            "😄",
            "😁",
            "😆",
            "😅",
            "🤣",
            "😂",
            "🙂",
            "😊",
            "😇",
            "🥰",
            "😍",
            "🤩",
            "😘",
            "😗",
            "😚",
            "😙",
            "🥲",
            "😋",
            "😛",
            "😜",
            "🤪",
            "😝",
            "🤑",
            "🤗",
            "🤭",
            "🤫",
            "🤔",
            "🫡",
            "🤐",
            "🤨",
            "😐",
            "😑",
            "😶",
            "😏",
            "😒",
            "🙄",
            "😬",
            "😮‍💨",
            "🤥",
            "😌",
            "😔",
            "😪",
            "🤤",
            "😴",
            "😷",
            "🤒",
            "🤕",
            "🤢",
            "🤮",
            "🥴",
            "😵",
            "🤯",
            "🥳",
            "🥸",
            "😎",
            "🤓",
            "🧐",
            "😕",
        ],
    },
    gestures: {
        name: "Gestures",
        icon: "👋",
        emojis: [
            "👋",
            "🤚",
            "🖐️",
            "✋",
            "🖖",
            "👌",
            "🤌",
            "🤏",
            "✌️",
            "🤞",
            "🤟",
            "🤘",
            "🤙",
            "👈",
            "👉",
            "👆",
            "🖕",
            "👇",
            "☝️",
            "👍",
            "👎",
            "✊",
            "👊",
            "🤛",
            "🤜",
            "👏",
            "🙌",
            "👐",
            "🤲",
            "🤝",
            "🙏",
            "💪",
            "🦾",
            "🫶",
            "❤️",
            "🧡",
            "💛",
            "💚",
            "💙",
            "💜",
        ],
    },
    objects: {
        name: "Objects",
        icon: "🎮",
        emojis: [
            "🎮",
            "🕹️",
            "🎰",
            "🎲",
            "🃏",
            "🏆",
            "🥇",
            "🥈",
            "🥉",
            "🏅",
            "🎯",
            "🎪",
            "🎭",
            "🎨",
            "🎬",
            "🎤",
            "🎧",
            "🎵",
            "🎶",
            "🎹",
            "💰",
            "💵",
            "💸",
            "💳",
            "🪙",
            "💎",
            "⚡",
            "🔥",
            "💥",
            "✨",
            "🌟",
            "⭐",
            "🎉",
            "🎊",
            "🎈",
            "🎁",
            "🃏",
            "🚀",
            "🛸",
            "💫",
        ],
    },
    food: {
        name: "Food",
        icon: "🍕",
        emojis: [
            "🍕",
            "🍔",
            "🍟",
            "🌭",
            "🍿",
            "🧂",
            "🥓",
            "🥚",
            "🍳",
            "🧇",
            "🥞",
            "🧈",
            "🍞",
            "🥐",
            "🥨",
            "🧀",
            "🍖",
            "🍗",
            "🥩",
            "🥪",
            "🌮",
            "🌯",
            "🫔",
            "🥙",
            "🧆",
            "🥗",
            "🍝",
            "🍜",
            "🍲",
            "🍛",
            "🍣",
            "🍱",
            "🥟",
            "🦐",
            "🍩",
            "🍪",
            "🎂",
            "🍰",
            "🧁",
            "🍫",
        ],
    },
    animals: {
        name: "Animals",
        icon: "🐶",
        emojis: [
            "🐶",
            "🐱",
            "🐭",
            "🐹",
            "🐰",
            "🦊",
            "🐻",
            "🐼",
            "🐨",
            "🐯",
            "🦁",
            "🐮",
            "🐷",
            "🐸",
            "🐵",
            "🙈",
            "🙉",
            "🙊",
            "🐔",
            "🐧",
            "🐦",
            "🐤",
            "🦅",
            "🦆",
            "🦉",
            "🐺",
            "🐗",
            "🐴",
            "🦄",
            "🐝",
            "🦋",
            "🐌",
            "🐞",
            "🐜",
            "🦟",
            "🐢",
            "🐍",
            "🦎",
            "🦂",
            "🐙",
        ],
    },
    flags: {
        name: "Flags",
        icon: "🏁",
        emojis: [
            "🏁",
            "🚩",
            "🎌",
            "🏴",
            "🏳️",
            "🇳🇬",
            "🇺🇸",
            "🇬🇧",
            "🇨🇦",
            "🇦🇺",
            "🇩🇪",
            "🇫🇷",
            "🇪🇸",
            "🇮🇹",
            "🇧🇷",
            "🇮🇳",
            "🇨🇳",
            "🇯🇵",
            "🇰🇷",
            "🇿🇦",
            "🇬🇭",
            "🇰🇪",
            "🇪🇬",
            "🇹🇿",
            "🇪🇹",
            "🇸🇳",
            "🇨🇲",
            "🇨🇮",
            "🇲🇦",
            "🇹🇳",
        ],
    },
};

const filteredEmojis = computed(() => {
    const cat = categories[activeCategory.value];
    if (!cat) return [];

    if (!search.value.trim()) {
        return cat.emojis;
    }

    // When searching, search across all categories
    const q = search.value.toLowerCase();
    const all = Object.values(categories).flatMap((c) => c.emojis);
    // Simple search: just return all unique emojis (emoji search by text is limited without a mapping)
    return [...new Set(all)];
});
</script>
