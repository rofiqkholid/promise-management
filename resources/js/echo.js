import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function initEcho() {
    try {
        const csrfMeta = document.querySelector(
            'meta[name="csrf-token"]'
        );

        const csrfToken = csrfMeta
            ? csrfMeta.getAttribute('content')
            : '';

        const authUrlMeta = document.querySelector(
            'meta[name="broadcasting-auth-url"]'
        );

        let authEndpoint = authUrlMeta
            ? authUrlMeta.getAttribute('content')
            : null;

        if (!authEndpoint) {
            const path = window.location.pathname;

            const mngIndex = path.indexOf('/management');

            const appPrefix = mngIndex !== -1
                ? path.substring(0, mngIndex)
                : '';

            authEndpoint =
                (appPrefix
                    ? appPrefix.replace(/\/$/, '')
                    : ''
                ) + '/broadcasting/auth';
        }

        const keyMeta = document.querySelector(
            'meta[name="reverb-key"]'
        );

        const hostMeta = document.querySelector(
            'meta[name="reverb-host"]'
        );

        const portMeta = document.querySelector(
            'meta[name="reverb-port"]'
        );

        const schemeMeta = document.querySelector(
            'meta[name="reverb-scheme"]'
        );

        const reverbKey =
            (keyMeta && keyMeta.getAttribute('content'))
            || import.meta.env.VITE_REVERB_APP_KEY;

        if (!reverbKey) {
            return;
        }

        const customHost =
            (hostMeta && hostMeta.getAttribute('content'))
            || import.meta.env.VITE_REVERB_HOST;

        const wsHost =
            customHost &&
            customHost !== '127.0.0.1' &&
            customHost !== 'localhost'
                ? customHost
                : window.location.hostname;

        const wsScheme =
            (schemeMeta && schemeMeta.getAttribute('content'))
            || import.meta.env.VITE_REVERB_SCHEME
            || (
                window.location.protocol === 'https:'
                    ? 'https'
                    : 'http'
            );

        const isTls =
            wsScheme === 'https'
            || window.location.protocol === 'https:';

        let wsPort = isTls ? 443 : 80;

        if (
            portMeta &&
            portMeta.getAttribute('content')
        ) {
            wsPort =
                Number(portMeta.getAttribute('content'))
                || (isTls ? 443 : 80);

        } else if (
            import.meta.env.VITE_REVERB_PORT
        ) {
            wsPort = Number(
                import.meta.env.VITE_REVERB_PORT
            );

        } else if (window.location.port) {
            wsPort = Number(
                window.location.port
            );
        }

        window.Echo = new Echo({
            broadcaster: 'reverb',

            key: reverbKey,

            wsHost: wsHost,

            wsPort: wsPort,

            wssPort: wsPort,

            forceTLS: isTls,

            enabledTransports: ['ws', 'wss'],

            wsPath: '/reverb',

            authEndpoint: authEndpoint,

            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            }
        });

    } catch (e) {
        console.warn(
            'Laravel Echo Reverb initialization failed:',
            e
        );
    }
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initEcho
    );
} else {
    initEcho();
}