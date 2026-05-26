<?php
// Inclusion des fichiers de configuration et de la classe Panier
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';

// Instancie le panier
$panier = new Panier();

// Si l'utilisateur est connecté, vide le panier en base de données
if (isLoggedIn()) {
    $panierId = isset($_GET['panier_id']) ? intval($_GET['panier_id']) : 0;
    if ($panierId) $panier->viderPanier($panierId);
} else {
    // Sinon, vide le panier invité (session)
    $panier->guestVider();
}

// Redirige vers la page du panier
redirect(BASE_URL . '/pages/panier.php');
