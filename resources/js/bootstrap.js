import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Ключ и кластер публичны; секрет остаётся в .env на сервере.
const settings = window.__BROADCAST_PUBLIC__ || {};

if (settings.enabled && settings.key && settings.cluster) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: settings.key,
        cluster: settings.cluster,
        forceTLS: true,
    });
}
