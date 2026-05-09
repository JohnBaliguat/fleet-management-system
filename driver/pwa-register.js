// Pantrucks Driver — service worker registration + push subscription helper.
(function () {
  if (!('serviceWorker' in navigator)) return;
  // SW lives at site root so its scope can include both driver/* and
  // index.php URLs. Scope falls out of the SW's own URL location.
  navigator.serviceWorker.register('sw-driver.js').then(function (reg) {
    // Visible online/offline pill at the bottom of the screen.
    function setOnline(online) {
      var pill = document.getElementById('ptOfflinePill');
      if (!pill) {
        pill = document.createElement('div');
        pill.id = 'ptOfflinePill';
        pill.style.cssText = 'position:fixed;left:50%;transform:translateX(-50%);bottom:90px;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:600;z-index:9999;box-shadow:0 4px 10px rgba(0,0,0,.15);transition:opacity .3s';
        document.body.appendChild(pill);
      }
      if (online) {
        pill.style.background = '#16a34a';
        pill.style.color = '#fff';
        pill.textContent = '● Online';
        pill.style.opacity = '0';
        // Trigger queue replay when we come back online.
        if (navigator.serviceWorker.controller) {
          navigator.serviceWorker.controller.postMessage({ type: 'pt-replay' });
        }
      } else {
        pill.style.background = '#f59e0b';
        pill.style.color = '#1c1917';
        pill.textContent = '● Offline — actions will sync later';
        pill.style.opacity = '1';
      }
    }
    setOnline(navigator.onLine);
    window.addEventListener('online',  function () { setOnline(true); });
    window.addEventListener('offline', function () { setOnline(false); });

    // Push subscription — best-effort. If VAPID is configured server-side
    // we'll subscribe and POST the endpoint to push_subscription. Otherwise
    // silently no-op.
    if (!window.PT_VAPID_PUBLIC_KEY) return;
    if (!('PushManager' in window)) return;
    Notification.requestPermission().then(function (perm) {
      if (perm !== 'granted') return;
      reg.pushManager.getSubscription().then(function (existing) {
        if (existing) return existing;
        return reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8(window.PT_VAPID_PUBLIC_KEY),
        });
      }).then(function (sub) {
        if (!sub) return;
        var json = sub.toJSON();
        var fd = new FormData();
        fd.append('endpoint',   json.endpoint || sub.endpoint);
        fd.append('p256dh_key', json.keys && json.keys.p256dh ? json.keys.p256dh : '');
        fd.append('auth_key',   json.keys && json.keys.auth   ? json.keys.auth   : '');
        fd.append('user_agent', navigator.userAgent || '');
        fetch('php/crud/add/save_push_subscription.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      }).catch(function () { /* push not available */ });
    });
  }).catch(function (e) { console.warn('SW register failed:', e); });

  function urlBase64ToUint8(base64) {
    var pad = '='.repeat((4 - base64.length % 4) % 4);
    var s = (base64 + pad).replace(/-/g, '+').replace(/_/g, '/');
    var raw = atob(s);
    var arr = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
    return arr;
  }
})();
