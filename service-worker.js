const CACHE_NAME = 'tooltrack-shell-v1';

const SHELL_FILES = [
    './mobile/',
    './mobile/index.php',
    './login.php',
    './assets/css/style.css',
    './assets/js/pwa-register.js',
    './manifest.webmanifest'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL_FILES))
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    if (
        url.pathname.includes('/api_') ||
        url.pathname.includes('/checkout/') ||
        url.pathname.includes('/maintenance/')
    ) {
        event.respondWith(
            fetch(event.request).catch(() =>
                new Response(
                    JSON.stringify({
                        success: false,
                        message: 'This action requires an internet connection.'
                    }),
                    {
                        status: 503,
                        headers: { 'Content-Type': 'application/json' }
                    }
                )
            )
        );
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                return response;
            })
            .catch(() => caches.match(event.request).then(cached => cached || caches.match('./mobile/')))
    );
});
