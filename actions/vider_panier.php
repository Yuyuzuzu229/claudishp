<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';

$panier = new Panier();

if (isLoggedIn()) {
    $panierId = isset($_GET['panier_id']) ? intval($_GET['panier_id']) : 0;
    if ($panierId) $panier->viderPanier($panierId);
} else {
    $panier->guestVider();
}

redirect(BASE_URL . '/pages/panier.php');
