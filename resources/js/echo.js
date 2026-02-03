import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const envHost = import.meta.env.VITE_REVERB_HOST;
const resolvedHost = !envHost || envHost === 'reverb' ? window.location.hostname : envHost;
const scheme = import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', '');

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: resolvedHost,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});
