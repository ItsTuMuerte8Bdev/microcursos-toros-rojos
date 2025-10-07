// Service Worker: precache + activation + navigation fallback
const CACHE_NAME = 'microcursos-v4';
const ASSETS = [
  '/',
  '/js/microcursos.js',
  '/css/style.css'
];

// Instalación: precachear recursos esenciales y forzar activación.
// Se hace tolerantemente para evitar que rutas autenticadas (login redirect)
// rompan la instalación al intentar precachearlas.
self.addEventListener('install', (event) => {
  event.waitUntil(
    (async () => {
      const cache = await caches.open(CACHE_NAME);
      for (const url of ASSETS) {
        try{
          const resp = await fetch(url, { credentials: 'same-origin' });
          if (resp && resp.ok) await cache.put(url, resp.clone());
        }catch(e){ console.warn('[SW] precache failed for', url, e); }
      }
    })()
  );
  // Activa este SW inmediatamente sin esperar a cierre de páginas
  self.skipWaiting();
  console.log('[SW] install completed, attempted precache:', ASSETS);
});

// Intercepción de peticiones: servir desde caché o realizar petición de red
// Activate: claim clients and remove old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(keys.map(k => {
        if (k !== CACHE_NAME) return caches.delete(k);
        return Promise.resolve(true);
      }));
    }).then(() => self.clients.claim())
  );
  console.log('[SW] activate, claimed clients and cleaned old caches. Using cache:', CACHE_NAME);
});


self.addEventListener('fetch', (event) => {
  const req = event.request;
  const accept = req.headers.get('accept') || '';
  const isNavigation = req.mode === 'navigate' || accept.includes('text/html');

  if (isNavigation) {
    // If the navigation is for auth callbacks or any /auth/* path, skip SW handling.
    // Some hosts or auth flows are sensitive to interception (cookies/state). Let the
    // browser perform the navigation directly to avoid 403s or missing params.
    if (req.url && req.url.indexOf('/auth/') !== -1) {
      console.log('[SW] bypassing SW for auth navigation:', req.url);
      event.respondWith(
        fetch(req, { credentials: 'same-origin', redirect: 'follow' }).catch(() => {
          return new Response('Service Unavailable', { status: 503, statusText: 'Service Unavailable' });
        })
      );
      return;
    }

    // Network-first for navigation: try network, if it fails fall back to cache.
    console.log('[SW] navigation fetch for:', req.url);
    event.respondWith(
      fetch(req).then((networkResp) => {
        // Optionally cache a copy of the HTML response for offline fallback
        try{
          if (networkResp && networkResp.ok && networkResp.headers.get('content-type') && networkResp.headers.get('content-type').includes('text/html')){
            const copy = networkResp.clone();
            caches.open(CACHE_NAME).then(c => c.put(req, copy)).catch(()=>{});
          }
        }catch(e){/* ignore */}
        return networkResp;
      }).catch(() => {
        console.log('[SW] network failed for navigation, attempting cache fallback for:', req.url);
        // network failed — try exact match, then root shell, then generic offline
        return caches.match(req).then(cached => {
          if (cached) return cached;
          return caches.match('/').then(root => {
            if (root) return root;
            return new Response('<!doctype html><html><body><h1>Sin conexión</h1></body></html>', { status: 503, headers: { 'Content-Type': 'text/html' } });
          });
        });
      })
    );
    return;
  }
  // Non-navigation
  // For API requests prefer network-first and do not cache API responses to avoid stale data.
  if (req.url.includes('/api/')){
    event.respondWith(
      fetch(req).then(networkResp => {
        return networkResp;
      }).catch(()=>{
        // if network fails try to match cache fallback (if any)
        return caches.match(req).then(cached => cached || new Response('Service Unavailable', { status: 503 }));
      })
    );
    return;
  }

  // Non-API: cache-first with network fallback and selective caching
  event.respondWith(
    caches.match(req).then(cached => {
      if (cached) return cached;
      return fetch(req).then(networkResp => {
        try{
          if (req.method === 'GET' && networkResp && networkResp.status === 200){
            const url = req.url || '';
            // Avoid caching large media
            if (!url.match(/\.(mp4|webm|mov|m4v)(\?.*)?$/i)){
              const copy = networkResp.clone();
              caches.open(CACHE_NAME).then(c => c.put(req, copy)).catch(()=>{});
            }
          }
        }catch(e){}
        return networkResp;
      }).catch(()=>{
        return new Response('Service Unavailable', { status: 503, statusText: 'Service Unavailable' });
      });
    })
  );
});

// Escuchar mensajes desde la página para cachear recursos específicos (sin videos)
self.addEventListener('message', (event) => {
  const data = event.data || {};
  if (data && data.action === 'cache-resources' && Array.isArray(data.resources)){
    caches.open(CACHE_NAME).then(cache => {
      data.resources.forEach(url => {
        try{
          if (typeof url === 'string' && !url.match(/\.(mp4|webm|mov|m4v)(\?.*)?$/i)){
            fetch(url).then(resp => { if (resp && resp.ok) cache.put(url, resp.clone()); }).catch(()=>{});
          }
        }catch(e){}
      });
    });
  }

  // Delete specific resources from cache when requested
  if (data && data.action === 'delete-resources' && Array.isArray(data.resources)){
    console.log('[SW] delete-resources request received, resources=', data.resources);
    caches.open(CACHE_NAME).then(cache => {
      data.resources.forEach(url => {
        try{
          if (typeof url === 'string'){
            cache.delete(url).then(deleted => { console.log('[SW] deleted from cache', url, deleted); }).catch(err=>{ console.warn('[SW] delete cache failed', url, err); });
          }
        }catch(e){ console.warn('[SW] delete-resources loop error', e); }
      });
    }).catch(e=>{ console.warn('[SW] open cache failed for delete-resources', e); });
  }
  // Allow pages to ask the SW to activate immediately
  if (data && data.type === 'SKIP_WAITING'){
    console.log('[SW] Received SKIP_WAITING message from page, calling skipWaiting()');
    try{ self.skipWaiting(); }catch(e){ console.warn('[SW] skipWaiting failed', e); }
  }
  
  // Allow pages to request the SW to self-unregister and clear caches.
  if (data && (data.action === 'self-unregister' || data.action === 'unregister-self')){
    (async function(){
      try{
        console.log('[SW] Received self-unregister request from page');
        // delete caches related to the app
        try{
          const keys = await caches.keys();
          for(const k of (keys||[])){
            try{
              const kn = String(k).toLowerCase();
              if(kn.includes('microcursos') || kn.includes('micro') || kn.includes('pwa')){
                await caches.delete(k).catch(()=>{});
                console.log('[SW] deleted cache', k);
              }
            }catch(e){}
          }
        }catch(e){ console.warn('[SW] error deleting caches before unregister', e); }
        // notify clients that we're about to unregister
        try{
          const clientsList = await clients.matchAll({ includeUncontrolled: true });
          clientsList.forEach(c => { try{ c.postMessage({ type: 'sw-unregister-started' }); }catch(_){}});
        }catch(e){}
        // unregister this service worker registration
        try{
          const ok = await self.registration.unregister();
          console.log('[SW] self.unregister returned', ok);
        }catch(e){ console.warn('[SW] self.unregister failed', e); }
        // inform clients that unregister attempt finished
        try{
          const clientsList2 = await clients.matchAll({ includeUncontrolled: true });
          clientsList2.forEach(c => { try{ c.postMessage({ type: 'sw-unregistered' }); }catch(_){}});
        }catch(e){}
      }catch(e){ console.warn('[SW] self-unregister flow failed', e); }
    })();
  }
});
