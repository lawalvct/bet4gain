import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import "./echo";
import { useTheme } from "./Composables/useTheme";

// Layout Components
import GamePage from "./Components/Pages/GamePage.vue";
import LoginPage from "./Components/Pages/LoginPage.vue";
import RegisterPage from "./Components/Pages/RegisterPage.vue";

const app = createApp({});
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Register page-level components as global (used in Blade templates)
app.component("game-page", GamePage);
app.component("login-page", LoginPage);
app.component("register-page", RegisterPage);

// Initialize theme system
const { init: initTheme } = useTheme();
initTheme();

app.mount("#app");
