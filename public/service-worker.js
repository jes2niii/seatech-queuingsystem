const CACHE_NAME = 'seatech-queue-v1';

const STATIC_ASSETS = [
    '/',
    '/userPurpose',
    '/mainView',
    '/registrationDashboard',
    '/accountLogin',
    '/adminDashboard',
    '/manifest.json',
    '/css/userPurpose.css',
    '/css/mainView.css',
    '/css/registrationDashboard.css',
    '/css/adminDashboard.css',
    '/css/accountLogin.css',
    '/css/accountLog-in.css',
    '/js/userPurpose.js',
    '/js/registrationDashboard.js',
    '/js/mainView.js',
    '/js/clock.js',
    '/js/adminDashboard.js',
    '/icons/icon-192.png',
    '/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/tv/') || url.pathname.startsWith('/livewire/')) {
        return;
    }

    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response && response.status === 200 && response.type === 'basic') {
                    const cloned = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, cloned);
                    });
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then((cached) => {
                    return cached || new Response('Offline - Page not cached', { status: 503 });
                });
            })
    );
});
