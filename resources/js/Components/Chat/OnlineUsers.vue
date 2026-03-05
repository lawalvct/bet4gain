<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-4 py-2.5 border-b border-surface-light-border dark:border-surface-dark-border"
        >
            <h3
                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >
                👥 Online
                <span class="text-xs text-slate-400 font-normal ml-1"
                    >({{ users.length }})</span
                >
            </h3>
            <span
                class="w-2 h-2 rounded-full bg-game-green animate-pulse"
            ></span>
        </div>

        <!-- Users List -->
        <div
            class="p-3 flex flex-wrap gap-2 max-h-32 overflow-y-auto scrollbar-thin"
        >
            <div
                v-for="user in users"
                :key="user.id"
                class="flex items-center gap-1.5 bg-surface-light dark:bg-surface-dark rounded-full pl-1 pr-3 py-1"
                :title="user.username"
            >
                <img
                    :src="user.avatar_url || '/images/default-avatar.png'"
                    :alt="user.username"
                    class="w-5 h-5 rounded-full object-cover"
                />
                <span
                    class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[80px]"
                    >{{ user.username }}</span
                >
            </div>

            <div
                v-if="!users.length"
                class="text-xs text-slate-400 py-2 w-full text-center"
            >
                No users online
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    users: { type: Array, default: () => [] },
});
</script>
