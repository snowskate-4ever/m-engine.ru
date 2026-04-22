import './messenger-float-panel.js';
import './messenger-realtime.js';
import './music-landing.js';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
}

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
if (reverbKey) {
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
    const useTLS = scheme === 'https';
    const port = Number(import.meta.env.VITE_REVERB_PORT ?? (useTLS ? 443 : 8080));

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
        wsPort: port,
        wssPort: port,
        forceTLS: useTLS,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                ...(csrf ? { 'X-CSRF-TOKEN': csrf.getAttribute('content') } : {}),
            },
        },
        withCredentials: true,
    });
}
