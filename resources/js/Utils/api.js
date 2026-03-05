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
            // Redirect to login if unauthenticated
            window.location.href = "/login";
        }

        if (status === 419) {
            // CSRF token mismatch — reload page
            window.location.reload();
        }

        if (status === 429) {
            // Rate limited
            console.warn("Rate limited. Please slow down.");
        }

        return Promise.reject(error);
    },
);

export default api;
