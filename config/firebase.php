<?php
/**
 * Configuration Firebase Cloud Messaging
 *
 * Pour activer les notifications push :
 * 1. Allez sur https://console.firebase.google.com
 * 2. Créez un projet (ou utilisez un existant)
 * 3. Ajoutez une application Web
 * 4. Copiez la config Firebase ci-dessous
 * 5. Dans Cloud Messaging > onglet, récupérez la "Server Key" (clé serveur)
 * 6. Collez la clé dans FCM_SERVER_KEY
 * 7. Collez la config Firebase dans FIREBASE_CONFIG
 */

// ── CLÉ SERVEUR FCM ───────────────────────────────────────────────
// Clé serveur Firebase Cloud Messaging (à remplacer par votre vraie clé)
define('FCM_SERVER_KEY', '');

// ── CONFIGURATION FIREBASE (NAVIGATEUR) ───────────────────────────
// Configuration Firebase pour le navigateur (à remplacer par vos vraies valeurs)
// Encodée en JSON pour être utilisée directement en JavaScript côté client
define('FIREBASE_CONFIG', json_encode([
    'apiKey' => '',
    'authDomain' => '',
    'projectId' => '',
    'storageBucket' => '',
    'messagingSenderId' => '',
    'appId' => '',
]));

// ── CLÉ VAPID ─────────────────────────────────────────────────────
// Clé VAPID pour Web Push (récupérable dans Firebase Console > Project Settings > Cloud Messaging > Web Push certificates)
define('FCM_VAPID_KEY', '');

// ── FONCTION : fcmEstConfigure ────────────────────────────────────
// Fonction utilitaire pour vérifier si Firebase Cloud Messaging est configuré
function fcmEstConfigure() {
    // Vérifie si la constante FCM_SERVER_KEY n'est pas vide
    return !empty(FCM_SERVER_KEY);
}
