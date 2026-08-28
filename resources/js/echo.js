import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function initEcho() {
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

        const keyMeta = document.querySelector('meta[name="pusher-key"]');
        const clusterMeta = document.querySelector('meta[name="pusher-cluster"]');

        const pusherKey = (keyMeta && keyMeta.getAttribute('content')) || import.meta.env.VITE_PUSHER_APP_KEY;
        const pusherCluster = (clusterMeta && clusterMeta.getAttribute('content')) || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1';

        if (!pusherKey) {
            return;
        }

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true,
            authEndpoint: authEndpoint,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            }
        });
    } catch (e) {
        console.warn('Laravel Echo Pusher initialization failed:', e);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEcho);
} else {
    initEcho();
}