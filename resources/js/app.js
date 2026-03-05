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

const pageComponents = {
    "game-page": GamePage,
    "login-page": LoginPage,
    "register-page": RegisterPage,
    "verify-email-page": EmailVerifyPage,
    "forgot-password-page": ForgotPasswordPage,
    "reset-password-page": ResetPasswordPage,
    "confirm-password-page": ConfirmPasswordPage,
    "profile-page": ProfilePage,
};

const toCamelCase = (value) =>
    value.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());

const getPropsFromAttributes = (element) => {
    const props = {};

    for (const attribute of element.attributes) {
        props[toCamelCase(attribute.name)] = attribute.value;
    }

    return props;
};

const mountPageComponent = () => {
    for (const [selector, component] of Object.entries(pageComponents)) {
        const element = document.querySelector(selector);

        if (!element) {
            continue;
        }

        const app = createApp(component, getPropsFromAttributes(element));
        app.use(createPinia());
        app.use(router);
        app.mount(element);
        return;
    }
};

// Initialize theme system
const { init: initTheme } = useTheme();
initTheme();

mountPageComponent();
