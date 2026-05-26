<?php
/**
 * Configuration des envois automatiques
 *
 * Pour activer l'envoi d'emails, configurez les paramètres SMTP ci-dessous.
 * Laissez vide pour utiliser mail() (nécessite un serveur SMTP local).
 *
 * Pour WhatsApp : le système génère automatiquement un lien wa.me
 * qui ouvre WhatsApp avec le message pré-rempli.
 */

// ── SMTP (Email) ───────────────────────────────────────────────────
// Hôte du serveur SMTP pour l'envoi des emails
define('SMTP_HOST', 'smtp.gmail.com');
// Port du serveur SMTP (587 pour TLS)
define('SMTP_PORT', 587);
// Nom d'utilisateur SMTP (adresse email expéditrice)
define('SMTP_USER', 'adminclaudishop@gmail.com');
// Mot de passe d'application SMTP (à générer dans les paramètres de sécurité Google)
define('SMTP_PASS', 'ypzc kabw qcog xbqz');
// Adresse email de l'expéditeur
define('SMTP_FROM', 'adminclaudishop@gmail.com');
// Nom affiché de l'expéditeur
define('SMTP_FROM_NAME', 'ClaudiShop');

// ── Google OAuth (Connexion avec Google) ──────────────────────────
// Créez un projet sur https://console.cloud.google.com/apis/credentials
// puis renseignez votre Client ID ci-dessous.
// Identifiant client Google OAuth pour la connexion via Google
define('GOOGLE_CLIENT_ID', '756205726898-ii5v6d7ugvj49krl0vo78q169vbtmhh8.apps.googleusercontent.com');
