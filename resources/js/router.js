import { createRouter, createWebHistory } from "vue-router";

// We use Blade for page routing, Vue Router handles in-page SPA navigation
const routes = [
    // These routes are used for programmatic navigation within Vue components
    // Actual page loading is handled by Laravel/Blade
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
