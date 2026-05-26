<?php
// Inclusion des fichiers de configuration et de la classe Panier
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';

// Vérifie si la requête est POST, sinon redirige vers l'accueil
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/index.php');

// Instancie le panier
$panier = new Panier();

// Si l'utilisateur est connecté, modifie la quantité en base de données
if (isLoggedIn()) {
    $ligneId = intval($_POST['ligne_id']);
    $quantite = intval($_POST['quantite']);
    $panier->modifierQuantite($ligneId, $quantite);
} else {
    // Sinon, modifie la quantité dans le panier invité (session)
    $index = intval($_POST['ligne_id']);
    $quantite = intval($_POST['quantite']);
    $panier->guestModifierQuantite($index, $quantite);
}

// Redirige vers la page du panier
redirect(BASE_URL . '/pages/panier.php');
