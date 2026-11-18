/* African Ranked PWA: never persist private pages or stale article/homepage HTML. */
const CACHE_NAME = 'african-ranked-pwa-offline-v1';
const OFFLINE_PAGE = '/offline.html';

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME)
    .then((cache) => cache.add(OFFLINE_PAGE))
    .then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith('african-ranked-pwa-') && key !== CACHE_NAME)
        .map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || request.mode !== 'navigate' || url.origin !== self.location.origin
      || url.pathname.startsWith('/staff') || url.pathname.startsWith('/api/')
      || url.pathname.startsWith('/newsletter/')) return;

  // Network-only for public HTML: newly edited homepage content is never served
  // from the PWA cache. Only a dedicated offline fallback is stored.
  event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_PAGE)));
});
