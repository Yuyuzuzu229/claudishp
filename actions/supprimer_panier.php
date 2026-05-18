<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';

$panier = new Panier();

if (isLoggedIn()) {
    $ligneId = isset($_GET['ligne_id']) ? intval($_GET['ligne_id']) : 0;
    if ($ligneId) $panier->supprimerProduit($ligneId);
} else {
    $index = isset($_GET['ligne_id']) ? intval($_GET['ligne_id']) : -1;
    if ($index >= 0) $panier->guestSupprimerLigne($index);
}

redirect(BASE_URL . '/pages/panier.php');
