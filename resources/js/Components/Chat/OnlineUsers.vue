<template>
    <div
        class="bg-surface-light-card dark:bg-surface-dark-card rounded-xl border border-surface-light-border dark:border-surface-dark-border"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between px-3 py-2 lg:py-1.5 border-b border-surface-light-border dark:border-surface-dark-border"
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
            class="p-3 flex flex-wrap gap-2 max-h-32 lg:max-h-20 overflow-y-auto scrollbar-thin"
        >
            <div
                v-for="user in users"
                :key="user.id"
                class="flex items-center gap-1.5 bg-surface-light dark:bg-surface-dark rounded-full pl-1 pr-3 py-1"
                :title="user.username"
            >
                <div
                    class="w-5 h-5 rounded-full bg-primary-500/20 flex items-center justify-center overflow-hidden"
                >
                    <img
                        v-if="user.avatar"
                        :src="avatarSrc(user.avatar)"
                        :alt="user.username"
                        class="w-full h-full object-cover"
                    />
                    <span v-else class="text-[10px] font-bold text-primary-500">
                        {{ user.username?.[0]?.toUpperCase() || "?" }}
                    </span>
                </div>
                <span
                    class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[80px]"
                >
                    {{ user.username }}
                    <span
                        v-if="user.is_guest"
                        class="text-slate-400 text-[10px]"
                        >(guest)</span
                    >
                </span>
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

const avatarSrc = (avatar) => {
    const value = String(avatar || "");
    if (!value) return "";
    if (value.startsWith("http")) return value;
    if (value.startsWith("/storage/")) return value;
    if (value.startsWith("storage/")) return `/${value}`;
    return `/storage/${value.replace(/^\/+/, "")}`;
};
</script>
