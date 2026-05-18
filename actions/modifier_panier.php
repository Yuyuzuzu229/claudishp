<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/index.php');

$panier = new Panier();

if (isLoggedIn()) {
    $ligneId = intval($_POST['ligne_id']);
    $quantite = intval($_POST['quantite']);
    $panier->modifierQuantite($ligneId, $quantite);
} else {
    $index = intval($_POST['ligne_id']);
    $quantite = intval($_POST['quantite']);
    $panier->guestModifierQuantite($index, $quantite);
}

redirect(BASE_URL . '/pages/panier.php');
