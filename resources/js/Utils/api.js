import axios from "axios";

const api = axios.create({
    baseURL: "/api",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
    withCredentials: true,
});

// Request interceptor — attach CSRF token
api.interceptors.request.use((config) => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    if (token) {
        config.headers["X-CSRF-TOKEN"] = token;
    }
    return config;
});

// Response interceptor — handle common errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401) {
            // Only redirect to login if this was a user-initiated navigation,
            // not a background polling/broadcast request that happens to fail.
            // Let the caller handle 401 gracefully instead of force-reloading.
            console.warn(
                "Unauthenticated (401). Login required for this action.",
            );
        }

        if (status === 419) {
            // CSRF token expired — refresh the token from the meta tag silently.
            // Don't reload the whole page; let the caller retry or show an error.
            console.warn("CSRF token mismatch (419). Token may have expired.");
        }

        if (status === 429) {
            // Rate limited
            console.warn("Rate limited (429). Please slow down.");
        }

        return Promise.reject(error);
    },
);

export default api;
