import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    try {
        const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
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
        });
    } catch (e) {
        console.warn('Laravel Echo Reverb initialization failed (running in fallback mode):', e);
    }
}

