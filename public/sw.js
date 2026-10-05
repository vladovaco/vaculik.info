// Service worker: app shell cache-first, pages network-first with offline fallback,
// document files (insurance cards) cache-first so they work in a waiting room without signal,
// and Web Push display. Bump CACHE_VERSION when the shell changes.
const CACHE_VERSION = 'v2';
const SHELL_CACHE = `shell-${CACHE_VERSION}`;
const PAGE_CACHE = `pages-${CACHE_VERSION}`;
const FILE_CACHE = `files-${CACHE_VERSION}`;
const SHELL = [
  '/offline',
  '/assets/app.css',
  '/assets/push.js',
  '/assets/vendor/htmx.min.js',
  '/assets/vendor/alpine.min.js',
  '/assets/icons/icon.svg',
  '/assets/icons/icon-192.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => ![SHELL_CACHE, PAGE_CACHE, FILE_CACHE].includes(k)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Static assets: cache first.
  if (url.pathname.startsWith('/assets/')) {
    event.respondWith(cacheFirst(request, SHELL_CACHE));
    return;
  }

  // Document files and thumbnails (insurance cards): cache first, they never change.
  if (/^\/dokumenty\/\d+\/subor\/\d+/.test(url.pathname)) {
    event.respondWith(cacheFirst(request, FILE_CACHE));
    return;
  }

  // Pages: network first, fall back to last cached copy, then to /offline.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((res) => {
          if (res.ok) {
            const copy = res.clone();
            caches.open(PAGE_CACHE).then((cache) => cache.put(request, copy));
          }
          return res;
        })
        .catch(() => caches.match(request).then((hit) => hit || caches.match('/offline'))),
    );
  }
});

function cacheFirst(request, cacheName) {
  return caches.match(request).then((hit) => hit || fetch(request).then((res) => {
    if (res.ok) {
      const copy = res.clone();
      caches.open(cacheName).then((cache) => cache.put(request, copy));
    }
    return res;
  }));
}

self.addEventListener('push', (event) => {
  let data = { title: 'vaculik.info', body: '', url: '/' };
  try { data = { ...data, ...event.data.json() }; } catch (e) { if (event.data) data.body = event.data.text(); }
  event.waitUntil(self.registration.showNotification(data.title, {
    body: data.body,
    icon: '/assets/icons/icon-192.png',
    badge: '/assets/icons/icon-192.png',
    data: { url: data.url },
    tag: data.url,
  }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = event.notification.data?.url || '/';
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
    for (const client of clients) {
      if ('focus' in client) { client.navigate(target); return client.focus(); }
    }
    return self.clients.openWindow(target);
  }));
});
