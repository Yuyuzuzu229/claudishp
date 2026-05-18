var CACHE_NAME = 'claudishop-livreur-v1';

// Fichiers à mettre en cache pour le mode offline
var URLS_TO_CACHE = [
  'driver/dashboard.php',
  'driver/connexion.php',
  'driver/live_position.php',
  'assets/images/brand/favicon.svg',
  'assets/images/brand/logo.svg',
  'assets/images/brand/logo-light.svg'
];

// Installation : pré-cache des fichiers essentiels
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(URLS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activation : nettoyer les anciens caches
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.filter(function(n) { return n !== CACHE_NAME; }).map(function(n) { return caches.delete(n); })
      );
    })
  );
  self.clients.claim();
});

// Interception des requêtes
self.addEventListener('fetch', function(event) {
  // Ne pas intercepter les API ou les appels externes
  if (event.request.url.includes('/api/') || !event.request.url.includes(location.origin)) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(function(cached) {
      // Cache-first pour les ressources statiques
      if (cached) return cached;

      // Network-first pour le reste
      return fetch(event.request).then(function(response) {
        if (response && response.status === 200 && response.type === 'basic') {
          var clone = response.clone();
          caches.open(CACHE_NAME).then(function(cache) { cache.put(event.request, clone); });
        }
        return response;
      }).catch(function() {
        return caches.match('driver/dashboard.php');
      });
    })
  );
});
