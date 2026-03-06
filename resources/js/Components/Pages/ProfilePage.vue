<template>
    <div class="min-h-screen bg-surface-light dark:bg-surface-dark px-4 py-8">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <a
                    href="/"
                    class="text-slate-400 hover:text-primary-500 transition"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    Profile Settings
                </h1>
            </div>

            <!-- Avatar Section -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 border border-surface-light-border dark:border-surface-dark-border"
            >
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white mb-4"
                >
                    Avatar
                </h2>

                <div class="flex items-center gap-6">
                    <div class="relative group">
                        <div
                            class="w-20 h-20 rounded-full bg-primary-500/10 flex items-center justify-center overflow-hidden border-2 border-surface-light-border dark:border-surface-dark-border"
                        >
                            <img
                                v-if="avatarPreview || user?.avatar"
                                :src="avatarPreview || avatarUrl"
                                alt="Avatar"
                                class="w-full h-full object-cover"
                            />
                            <span
                                v-else
                                class="text-3xl text-primary-500 font-bold"
                            >
                                {{ user?.username?.[0]?.toUpperCase() || "?" }}
                            </span>
                        </div>
                        <label
                            class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <input
                                type="file"
                                accept="image/jpg,image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="handleAvatarSelect"
                            />
                        </label>
                    </div>

                    <div class="flex-1 space-y-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            JPG, PNG or WebP. Max 2MB.
                        </p>
                        <div class="flex gap-2">
                            <BaseButton
                                v-if="selectedAvatarFile"
                                variant="primary"
                                size="sm"
                                :loading="avatarLoading"
                                @click="uploadAvatar"
                            >
                                Upload
                            </BaseButton>
                            <BaseButton
                                v-if="user?.avatar"
                                variant="ghost"
                                size="sm"
                                @click="removeAvatar"
                            >
                                Remove
                            </BaseButton>
                        </div>
                        <p v-if="avatarError" class="text-xs text-red-500">
                            {{ avatarError }}
                        </p>
                        <p v-if="avatarSuccess" class="text-xs text-green-500">
                            {{ avatarSuccess }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 border border-surface-light-border dark:border-surface-dark-border"
            >
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white mb-4"
                >
                    Profile Information
                </h2>

                <div
                    v-if="profileSuccess"
                    class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-500 text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ profileSuccess }}</span>
                </div>
                <div
                    v-if="profileError"
                    class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ profileError }}</span>
                </div>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <BaseInput
                        v-model="profileForm.username"
                        label="Username"
                        placeholder="Your username"
                        :error="profileErrors.username?.[0]"
                        hint="3-20 characters. Letters, numbers, underscores only."
                        required
                        size="lg"
                    >
                        <template #prefix>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <BaseInput
                        v-model="profileForm.email"
                        type="email"
                        label="Email"
                        placeholder="Your email address"
                        :error="profileErrors.email?.[0]"
                        required
                        size="lg"
                    >
                        <template #prefix>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <!-- Email verification badge -->
                    <div class="flex items-center gap-2" v-if="user">
                        <span
                            v-if="user.email_verified_at"
                            class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-green-500/10 text-green-500"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            Email Verified
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-amber-500/10 text-amber-500"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4m0 4h.01"
                                />
                            </svg>
                            Not Verified
                        </span>
                    </div>

                    <div class="pt-2">
                        <BaseButton
                            type="submit"
                            variant="primary"
                            :loading="profileLoading"
                        >
                            Save Changes
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 border border-surface-light-border dark:border-surface-dark-border"
            >
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white mb-4"
                >
                    Change Password
                </h2>

                <div
                    v-if="passwordSuccess"
                    class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-500 text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ passwordSuccess }}</span>
                </div>
                <div
                    v-if="passwordError"
                    class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ passwordError }}</span>
                </div>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <BaseInput
                        v-model="passwordForm.current_password"
                        type="password"
                        label="Current Password"
                        placeholder="Enter current password"
                        :error="passwordErrors.current_password?.[0]"
                        required
                        autocomplete="current-password"
                        size="lg"
                    >
                        <template #prefix>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <BaseInput
                        v-model="passwordForm.password"
                        type="password"
                        label="New Password"
                        placeholder="Enter new password"
                        :error="passwordErrors.password?.[0]"
                        hint="At least 8 characters"
                        required
                        autocomplete="new-password"
                        size="lg"
                    >
                        <template #prefix>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <BaseInput
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        label="Confirm New Password"
                        placeholder="Confirm new password"
                        :error="pwMismatch ? 'Passwords do not match' : ''"
                        required
                        autocomplete="new-password"
                        size="lg"
                    >
                        <template #prefix>
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </template>
                    </BaseInput>

                    <div class="pt-2">
                        <BaseButton
                            type="submit"
                            variant="primary"
                            :loading="passwordLoading"
                        >
                            Update Password
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- Preferences -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 border border-surface-light-border dark:border-surface-dark-border"
            >
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white mb-4"
                >
                    Preferences
                </h2>

                <div class="space-y-5">
                    <!-- Theme -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white"
                            >
                                Theme
                            </p>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Choose your preferred appearance
                            </p>
                        </div>
                        <div
                            class="flex rounded-xl border border-surface-light-border dark:border-surface-dark-border overflow-hidden"
                        >
                            <button
                                v-for="opt in themeOptions"
                                :key="opt.value"
                                @click="setTheme(opt.value)"
                                :class="[
                                    'px-3 py-1.5 text-xs font-medium transition',
                                    currentTheme === opt.value
                                        ? 'bg-primary-500 text-white'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-surface-dark',
                                ]"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Sound -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white"
                            >
                                Sound Effects
                            </p>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Game sounds and notifications
                            </p>
                        </div>
                        <button
                            @click="toggleSound"
                            :class="[
                                'relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none',
                                soundEnabled
                                    ? 'bg-primary-500'
                                    : 'bg-slate-300 dark:bg-slate-600',
                            ]"
                        >
                            <span
                                :class="[
                                    'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                                    soundEnabled
                                        ? 'translate-x-5'
                                        : 'translate-x-0',
                                ]"
                            ></span>
                        </button>
                    </div>

                    <!-- Sound Volume -->
                    <div v-if="soundEnabled" class="flex items-center gap-3">
                        <svg
                            class="w-5 h-5 text-slate-400 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.536 8.464a5 5 0 010 7.072M17.95 6.05a8 8 0 010 11.9M6.343 9H3.757a1 1 0 00-.757.343L1 12l2 2.657A1 1 0 003.757 15h2.586l4.95 4.95A.5.5 0 0012 19.657V4.343a.5.5 0 00-.707-.707L6.343 9z"
                            />
                        </svg>
                        <input
                            type="range"
                            min="0"
                            max="1"
                            step="0.1"
                            v-model.number="soundVolume"
                            @change="updateSoundVolume"
                            class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-primary-500"
                        />
                        <span class="text-xs text-slate-500 w-8 text-right"
                            >{{ Math.round(soundVolume * 100) }}%</span
                        >
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div
                class="bg-surface-light-card dark:bg-surface-dark-card rounded-2xl shadow-xl p-6 border border-surface-light-border dark:border-surface-dark-border"
            >
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white mb-4"
                >
                    Account
                </h2>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">
                            Member since
                        </dt>
                        <dd class="text-slate-900 dark:text-white font-medium">
                            {{ memberSince }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">
                            Provider
                        </dt>
                        <dd
                            class="text-slate-900 dark:text-white font-medium capitalize"
                        >
                            {{ user?.provider || "Email" }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500 dark:text-slate-400">Role</dt>
                        <dd
                            class="text-slate-900 dark:text-white font-medium capitalize"
                        >
                            {{ user?.role || "Player" }}
                        </dd>
                    </div>
                </dl>

                <div
                    class="border-t border-surface-light-border dark:border-surface-dark-border mt-4 pt-4"
                >
                    <BaseButton
                        variant="danger"
                        size="sm"
                        @click="handleLogout"
                    >
                        Log Out
                    </BaseButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import api from "@/Utils/api";
import { BaseInput, BaseButton } from "@/Components/UI";
import { useTheme } from "@/Composables/useTheme";
import { useSound } from "@/Composables/useSound";

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
const headers = { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" };

// User data
const user = ref(null);
const loading = ref(true);

// Theme
const { theme: currentTheme, setTheme: applyTheme } = useTheme();
const themeOptions = [
    { value: "light", label: "Light" },
    { value: "dark", label: "Dark" },
    { value: "system", label: "System" },
];

// Sound
const {
    enabled: soundEnabled,
    volume: soundVolume,
    toggle: toggleSoundComposable,
    setVolume: setSoundVolume,
} = useSound();

// Avatar
const avatarLoading = ref(false);
const avatarError = ref("");
const avatarSuccess = ref("");
const avatarPreview = ref(null);
const selectedAvatarFile = ref(null);

const avatarUrl = computed(() => {
    if (!user.value?.avatar) return null;
    const avatar = String(user.value.avatar);

    if (avatar.startsWith("http")) return avatar;
    if (avatar.startsWith("/storage/")) return avatar;
    if (avatar.startsWith("storage/")) return `/${avatar}`;

    return `/storage/${avatar.replace(/^\/+/, "")}`;
});

// Profile form
const profileForm = reactive({ username: "", email: "" });
const profileErrors = reactive({});
const profileLoading = ref(false);
const profileSuccess = ref("");
const profileError = ref("");

// Password form
const passwordForm = reactive({
    current_password: "",
    password: "",
    password_confirmation: "",
});
const passwordErrors = reactive({});
const passwordLoading = ref(false);
const passwordSuccess = ref("");
const passwordError = ref("");

const pwMismatch = computed(() => {
    return (
        passwordForm.password_confirmation.length > 0 &&
        passwordForm.password !== passwordForm.password_confirmation
    );
});

const memberSince = computed(() => {
    if (!user.value?.created_at) return "—";
    return new Date(user.value.created_at).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
});

// Fetch user data
onMounted(async () => {
    try {
        const { data } = await api.get("/user");
        user.value = data;
        profileForm.username = data.username;
        profileForm.email = data.email;
    } catch {
        // Not authenticated — redirect
        window.location.href = "/login";
    } finally {
        loading.value = false;
    }
});

// Avatar handlers
const handleAvatarSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        avatarError.value = "File must be less than 2MB.";
        return;
    }

    selectedAvatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
    avatarError.value = "";
    avatarSuccess.value = "";
};

const uploadAvatar = async () => {
    if (!selectedAvatarFile.value) return;

    avatarLoading.value = true;
    avatarError.value = "";
    avatarSuccess.value = "";

    const formData = new FormData();
    formData.append("avatar", selectedAvatarFile.value);

    try {
        const { data } = await api.post("/user/avatar", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        user.value.avatar =
            typeof data.avatar === "string"
                ? data.avatar.replace(/^https?:\/\/[^/]+/, "")
                : data.avatar;
        avatarSuccess.value = "Avatar updated!";
        selectedAvatarFile.value = null;
        avatarPreview.value = null;
    } catch (error) {
        avatarError.value = error.response?.data?.message || "Upload failed.";
    } finally {
        avatarLoading.value = false;
    }
};

const removeAvatar = async () => {
    try {
        await api.delete("/user/avatar");
        user.value.avatar = null;
        avatarPreview.value = null;
        selectedAvatarFile.value = null;
        avatarSuccess.value = "Avatar removed.";
    } catch (error) {
        avatarError.value =
            error.response?.data?.message || "Failed to remove avatar.";
    }
};

// Profile update (Fortify: PUT /user/profile-information)
const updateProfile = async () => {
    profileLoading.value = true;
    profileSuccess.value = "";
    profileError.value = "";
    Object.keys(profileErrors).forEach((k) => delete profileErrors[k]);

    try {
        await axios.put(
            "/user/profile-information",
            {
                username: profileForm.username,
                email: profileForm.email,
            },
            { headers },
        );
        profileSuccess.value = "Profile updated successfully.";
        user.value.username = profileForm.username;
        if (profileForm.email !== user.value.email) {
            profileSuccess.value =
                "Profile updated. Please check your email for verification.";
        }
        user.value.email = profileForm.email;
    } catch (error) {
        if (error.response?.status === 422) {
            const data = error.response.data;
            if (data.errors) Object.assign(profileErrors, data.errors);
            if (data.message && !data.errors) profileError.value = data.message;
        } else {
            profileError.value = "An unexpected error occurred.";
        }
    } finally {
        profileLoading.value = false;
    }
};

// Password update (Fortify: PUT /user/password)
const updatePassword = async () => {
    if (pwMismatch.value) return;

    passwordLoading.value = true;
    passwordSuccess.value = "";
    passwordError.value = "";
    Object.keys(passwordErrors).forEach((k) => delete passwordErrors[k]);

    try {
        await axios.put(
            "/user/password",
            {
                current_password: passwordForm.current_password,
                password: passwordForm.password,
                password_confirmation: passwordForm.password_confirmation,
            },
            { headers },
        );
        passwordSuccess.value = "Password changed successfully.";
        passwordForm.current_password = "";
        passwordForm.password = "";
        passwordForm.password_confirmation = "";
    } catch (error) {
        if (error.response?.status === 422) {
            const data = error.response.data;
            if (data.errors) Object.assign(passwordErrors, data.errors);
            if (data.message && !data.errors)
                passwordError.value = data.message;
        } else {
            passwordError.value = "An unexpected error occurred.";
        }
    } finally {
        passwordLoading.value = false;
    }
};

// Theme
const setTheme = (theme) => {
    applyTheme(theme);
    savePreferences({ theme });
};

// Sound
const toggleSound = () => {
    toggleSoundComposable();
    savePreferences({ sound_enabled: !soundEnabled.value });
};

const updateSoundVolume = () => {
    setSoundVolume(soundVolume.value);
    savePreferences({ sound_volume: soundVolume.value });
};

// Save preferences to server
const savePreferences = async (settings) => {
    try {
        await api.put("/user/settings", { settings });
    } catch {
        // Silently fail — preferences saved locally anyway
    }
};

// Logout
const handleLogout = async () => {
    try {
        await axios.post("/logout", {}, { headers });
        window.location.href = "/";
    } catch {
        window.location.href = "/";
    }
};
</script>
