// Web Push registration. Exposes window.pushSetup() for the settings page (Alpine component)
// and silently refreshes an existing subscription on every page load.
(function () {
  const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
  const vapidKey = meta('vapid-public-key');
  const subscribeUrl = meta('push-subscribe-url');
  const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && vapidKey !== '';

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
  }

  async function post(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': meta('csrf-token') },
      body: JSON.stringify(body),
    });
  }

  async function currentSubscription() {
    const reg = await navigator.serviceWorker.ready;
    return reg.pushManager.getSubscription();
  }

  async function subscribe() {
    const reg = await navigator.serviceWorker.ready;
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') throw new Error('Povolenie bolo zamietnuté.');
    const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(vapidKey) });
    await post(subscribeUrl, sub.toJSON());
    return sub;
  }

  async function unsubscribe() {
    const sub = await currentSubscription();
    if (!sub) return;
    await post(subscribeUrl + '/zrusit', { endpoint: sub.endpoint });
    await sub.unsubscribe();
  }

  // Keep the server copy fresh (endpoints rotate occasionally).
  if (supported) {
    window.addEventListener('load', async () => {
      try {
        const sub = await currentSubscription();
        if (sub) await post(subscribeUrl, sub.toJSON());
      } catch (e) { /* offline or not logged in */ }
    });
  }

  window.pushSetup = function () {
    return {
      supported,
      subscribed: false,
      status: supported ? 'Zisťujem stav…' : 'Tento prehliadač push upozornenia nepodporuje.',
      async init() {
        if (!supported) return;
        if (Notification.permission === 'denied') { this.status = 'Upozornenia sú v prehliadači zablokované. Povoľte ich v nastaveniach stránky.'; return; }
        const sub = await currentSubscription();
        this.subscribed = !!sub;
        this.status = sub ? 'Upozornenia sú na tomto zariadení zapnuté.' : 'Upozornenia na tomto zariadení ešte nie sú povolené.';
      },
      async subscribe() {
        try { await subscribe(); this.subscribed = true; this.status = 'Hotovo, upozornenia sú zapnuté.'; }
        catch (e) { this.status = 'Nepodarilo sa: ' + e.message; }
      },
      async unsubscribe() {
        try { await unsubscribe(); this.subscribed = false; this.status = 'Upozornenia na tomto zariadení sú vypnuté.'; }
        catch (e) { this.status = 'Nepodarilo sa: ' + e.message; }
      },
    };
  };
})();
