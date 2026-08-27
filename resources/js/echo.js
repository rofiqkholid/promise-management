import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        const authUrlMeta = document.querySelector('meta[name="broadcasting-auth-url"]');
        let authEndpoint = authUrlMeta ? authUrlMeta.getAttribute('content') : null;

        if (!authEndpoint) {
            const path = window.location.pathname;
            const mngIndex = path.indexOf('/management');
            const appPrefix = mngIndex !== -1 ? path.substring(0, mngIndex) : '';
            authEndpoint = (appPrefix ? appPrefix.replace(/\/$/, '') : '') + '/broadcasting/auth';
        }

        const wsHost = (import.meta.env.VITE_REVERB_HOST && import.meta.env.VITE_REVERB_HOST !== '127.0.0.1' && import.meta.env.VITE_REVERB_HOST !== 'localhost')
            ? import.meta.env.VITE_REVERB_HOST
            : window.location.hostname;
        const wsScheme = import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');
        const isTls = wsScheme === 'https' || window.location.protocol === 'https:';
        const wsPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 8443;

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: wsHost,
            wsPort: wsPort,
            wssPort: wsPort,
            forceTLS: isTls,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: authEndpoint,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            }
        });
    } catch (e) {
        console.warn('Laravel Echo Reverb initialization failed (running in fallback mode):', e);
    }
}


