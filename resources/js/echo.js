import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const reverbConfig = window.__BET4GAIN__?.reverb || {};

window.Echo = new Echo({
    broadcaster: "reverb",
    key: reverbConfig.key || import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbConfig.host || import.meta.env.VITE_REVERB_HOST,
    wsPort: reverbConfig.port || (import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: reverbConfig.port || (import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS:
        (reverbConfig.scheme ||
            (import.meta.env.VITE_REVERB_SCHEME ?? "https")) === "https",
    enabledTransports: ["ws", "wss"],
});
