<?php
// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

// Vide toutes les variables de session
$_SESSION = [];

// Détruit complètement la session
session_destroy();

// Redirige vers la page d'accueil
redirect(BASE_URL . '/index.php');
