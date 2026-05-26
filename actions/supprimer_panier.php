<?php
// Inclusion des fichiers de configuration et de la classe Panier
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';

// Instancie le panier
$panier = new Panier();

// Si l'utilisateur est connecté, supprime la ligne du panier en base
if (isLoggedIn()) {
    $ligneId = isset($_GET['ligne_id']) ? intval($_GET['ligne_id']) : 0;
    if ($ligneId) $panier->supprimerProduit($ligneId);
} else {
    // Sinon, supprime la ligne du panier invité (session)
    $index = isset($_GET['ligne_id']) ? intval($_GET['ligne_id']) : -1;
    if ($index >= 0) $panier->guestSupprimerLigne($index);
}

// Redirige vers la page du panier
redirect(BASE_URL . '/pages/panier.php');
