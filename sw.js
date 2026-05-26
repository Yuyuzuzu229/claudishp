// Nom du cache pour le service worker de ClaudiShop livreur
var CACHE_NAME = 'claudishop-livreur-v1';

// Liste des fichiers à mettre en cache pour le mode hors ligne (offline)
var URLS_TO_CACHE = [
  'driver/dashboard.php',
  'driver/connexion.php',
  'driver/live_position.php',
  'assets/images/brand/favicon.svg',
  'assets/images/brand/logo.svg',
  'assets/images/brand/logo-light.svg'
];

// Événement d'installation : pré-cache des fichiers essentiels
self.addEventListener('install', function(event) {
  // Attend que le cache soit ouvert et que les fichiers soient ajoutés
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      // Ajout de tous les fichiers de la liste au cache
      return cache.addAll(URLS_TO_CACHE);
    })
  );
  // Activation immédiate du service worker sans attendre la fin des anciens
  self.skipWaiting();
});

// Événement d'activation : nettoyage des anciens caches
self.addEventListener('activate', function(event) {
  event.waitUntil(
    // Récupération de tous les noms de caches existants
    caches.keys().then(function(names) {
      // Suppression de tous les caches dont le nom ne correspond pas au cache actuel
      return Promise.all(
        names.filter(function(n) { return n !== CACHE_NAME; }).map(function(n) { return caches.delete(n); })
      );
    })
  );
  // Prise de contrôle immédiate de toutes les pages clientes
  self.clients.claim();
});

// Interception des requêtes réseau (fetch)
self.addEventListener('fetch', function(event) {
  // Ne pas intercepter les appels vers l'API ou les requêtes externes (hors domaine)
  if (event.request.url.includes('/api/') || !event.request.url.includes(location.origin)) {
    return;
  }

  // Réponse à la requête interceptée
  event.respondWith(
    // Tentative de correspondance avec le cache
    caches.match(event.request).then(function(cached) {
      // Stratégie cache-first pour les ressources statiques
      if (cached) return cached;

      // Stratégie network-first pour les autres ressources
      return fetch(event.request).then(function(response) {
        // Si la réponse est valide (statut 200) et de type basic (même origine)
        if (response && response.status === 200 && response.type === 'basic') {
          // Clonage de la réponse pour la mettre en cache
          var clone = response.clone();
          // Ouverture du cache et stockage de la réponse clonée
          caches.open(CACHE_NAME).then(function(cache) { cache.put(event.request, clone); });
        }
        // Retour de la réponse originale
        return response;
      }).catch(function() {
        // En cas d'échec réseau, retour de la page dashboard en cache
        return caches.match('driver/dashboard.php');
      });
    })
  );
});
