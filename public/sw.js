/*
 * Service worker de Achrono. Hace dos cosas, y nada más:
 *  1. Permite instalar la web como aplicación.
 *  2. Si no hay conexión al abrir una página, muestra /offline.html en vez del error del navegador.
 *
 * A propósito NO guarda en caché páginas ni datos: los fichajes, nóminas y paneles son datos
 * privados y siempre deben venir del servidor.
 */
const CACHE = 'achrono-offline-v1';
const OFFLINE = '/offline.html';

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll([OFFLINE, '/images/icon-192.png']))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys()
            .then((claves) => Promise.all(claves.filter((clave) => clave !== CACHE).map((clave) => caches.delete(clave))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (evento) => {
    // Solo se interviene al navegar a una página y fallar la red.
    if (evento.request.mode === 'navigate') {
        evento.respondWith(fetch(evento.request).catch(() => caches.match(OFFLINE)));
    }
});
