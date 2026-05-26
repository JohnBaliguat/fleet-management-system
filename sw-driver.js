// Pantrucks Driver — service worker
// Phase 5: app-shell cache + IndexedDB-backed offline POST queue.

const CACHE_VERSION = 'pt-driver-v1';
const APP_SHELL = [
  'driver-dashboard',
  'manifest.webmanifest',
  'assets/css/styles.min.css',
  'assets/css/enhancements.css',
  'assets/css/driver-modern.css',
  'assets/libs/jquery/dist/jquery.min.js',
  'assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js',
  'alert/node_modules/sweetalert2/dist/sweetalert2.min.js',
  'alert/node_modules/sweetalert2/dist/sweetalert2.min.css',
];

const SYNC_TAG = 'pt-driver-replay';
const DB_NAME  = 'pt-driver';
const DB_STORE = 'queue';
const QUEUEABLE_PATTERNS = [
  /\/php\/operations\/driver_/,           // accept/decline/status/etc
  /\/php\/operations\/save_pod/,
  /\/php\/operations\/save_gateless/,
  /\/php\/operations\/save_trailer_jackup/,
  /\/php\/operations\/save_breakdown/,
  /\/php\/operations\/save_pre_departure/,
  /\/php\/operations\/send_message/,
];

// ---- IDB helpers ---------------------------------------------------------
function idbOpen() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, 1);
    req.onupgradeneeded = () => {
      req.result.createObjectStore(DB_STORE, { keyPath: 'id', autoIncrement: true });
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror   = () => reject(req.error);
  });
}
async function idbAdd(item)    { const db = await idbOpen(); return new Promise((r, j) => { const tx = db.transaction(DB_STORE, 'readwrite'); tx.objectStore(DB_STORE).add(item); tx.oncomplete = r; tx.onerror = () => j(tx.error); }); }
async function idbGetAll()     { const db = await idbOpen(); return new Promise((r, j) => { const tx = db.transaction(DB_STORE, 'readonly'); const req = tx.objectStore(DB_STORE).getAll(); req.onsuccess = () => r(req.result); req.onerror = () => j(req.error); }); }
async function idbDelete(id)   { const db = await idbOpen(); return new Promise((r, j) => { const tx = db.transaction(DB_STORE, 'readwrite'); tx.objectStore(DB_STORE).delete(id); tx.oncomplete = r; tx.onerror = () => j(tx.error); }); }

// ---- install / activate --------------------------------------------------
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_VERSION).then(cache => cache.addAll(APP_SHELL).catch(() => {}))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_VERSION).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// ---- fetch handler -------------------------------------------------------
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Pass GETs through with cache fallback for app shell.
  if (req.method === 'GET') {
    if (url.origin === location.origin) {
      event.respondWith(
        fetch(req).then(res => {
          if (res && res.status === 200 && res.type === 'basic') {
            const copy = res.clone();
            caches.open(CACHE_VERSION).then(c => c.put(req, copy)).catch(() => {});
          }
          return res;
        }).catch(() => caches.match(req).then(r => r || caches.match('driver-dashboard')))
      );
    }
    return;
  }

  // POSTs to allowlisted endpoints — try network, queue on failure.
  if (req.method === 'POST' && QUEUEABLE_PATTERNS.some(p => p.test(url.pathname))) {
    event.respondWith(handleQueueablePost(req));
  }
});

async function handleQueueablePost(req) {
  try {
    return await fetch(req.clone());
  } catch (err) {
    // Offline: stash the body + headers + URL and return a synthetic 202.
    try {
      let body = null;
      const ct = req.headers.get('content-type') || '';
      if (ct.includes('application/json') || ct.includes('application/x-www-form-urlencoded') || ct.includes('text/plain')) {
        body = await req.clone().text();
      } else {
        // multipart/form-data — convert to JSON-safe array of fields
        const fd = await req.clone().formData();
        const flat = [];
        for (const [k, v] of fd.entries()) {
          if (typeof v === 'string') {
            flat.push({ k, v, kind: 'string' });
          } else {
            const buf = await v.arrayBuffer();
            flat.push({ k, kind: 'file', name: v.name, type: v.type, data: Array.from(new Uint8Array(buf)) });
          }
        }
        body = JSON.stringify({ __multipart: true, fields: flat });
        req = new Request(req.url, { method: 'POST', headers: { 'Content-Type': 'application/json; x-pt-multipart-replay=1' }, body });
      }
      await idbAdd({ url: req.url, method: 'POST', headers: [...req.headers.entries()], body, queuedAt: Date.now() });
      if ('sync' in self.registration) {
        try { await self.registration.sync.register(SYNC_TAG); } catch (_) { /* permission */ }
      }
      return new Response(JSON.stringify({ status: 'queued', message: 'Queued offline; will sync when back online.' }), {
        status: 202, headers: { 'Content-Type': 'application/json' }
      });
    } catch (e) {
      return new Response(JSON.stringify({ status: 'error', message: 'Offline and could not queue: ' + e.message }), {
        status: 503, headers: { 'Content-Type': 'application/json' }
      });
    }
  }
}

self.addEventListener('sync', (event) => {
  if (event.tag === SYNC_TAG) event.waitUntil(replayQueue());
});
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'pt-replay') event.waitUntil(replayQueue());
});

async function replayQueue() {
  const items = await idbGetAll();
  for (const item of items) {
    try {
      const headers = new Headers(item.headers);
      const opts = { method: item.method, headers };
      if (item.body) opts.body = item.body;
      const res = await fetch(item.url, opts);
      if (res.ok) await idbDelete(item.id);
    } catch (_) {
      // still offline — leave in queue
    }
  }
}

// ---- Push notifications --------------------------------------------------
self.addEventListener('push', (event) => {
  let payload = { title: 'Pantrucks', body: 'New update', url: 'driver-dashboard' };
  if (event.data) {
    try { payload = Object.assign(payload, event.data.json()); } catch (_) { payload.body = event.data.text(); }
  }
  event.waitUntil(self.registration.showNotification(payload.title, {
    body: payload.body,
    icon: 'assets/images/logos/LogoFleet.png',
    badge: 'assets/images/logos/LogoFleet.png',
    data: { url: payload.url || 'driver-dashboard' },
    tag: payload.tag || 'pt-driver',
  }));
});
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || 'driver-dashboard';
  event.waitUntil(self.clients.matchAll({ type: 'window' }).then(clients => {
    for (const c of clients) { if (c.url.includes(url) && 'focus' in c) return c.focus(); }
    if (self.clients.openWindow) return self.clients.openWindow(url);
  }));
});
