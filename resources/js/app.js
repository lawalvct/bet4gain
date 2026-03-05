import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import "./echo";
import { useTheme } from "./Composables/useTheme";

// Layout Components
import GamePage from "./Components/Pages/GamePage.vue";
import LoginPage from "./Components/Pages/LoginPage.vue";
import RegisterPage from "./Components/Pages/RegisterPage.vue";
import EmailVerifyPage from "./Components/Pages/EmailVerifyPage.vue";
import ForgotPasswordPage from "./Components/Pages/ForgotPasswordPage.vue";
import ResetPasswordPage from "./Components/Pages/ResetPasswordPage.vue";
import ConfirmPasswordPage from "./Components/Pages/ConfirmPasswordPage.vue";
import ProfilePage from "./Components/Pages/ProfilePage.vue";

const app = createApp({});
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Register page-level components as global (used in Blade templates)
app.component("game-page", GamePage);
app.component("login-page", LoginPage);
app.component("register-page", RegisterPage);
app.component("verify-email-page", EmailVerifyPage);
app.component("forgot-password-page", ForgotPasswordPage);
app.component("reset-password-page", ResetPasswordPage);
app.component("confirm-password-page", ConfirmPasswordPage);
app.component("profile-page", ProfilePage);

// Initialize theme system
const { init: initTheme } = useTheme();
initTheme();

app.mount("#app");
