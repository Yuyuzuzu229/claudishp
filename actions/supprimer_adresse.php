<?php
// Inclusion des fichiers de configuration et de la classe Adresse
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Adresse.php';

// Vérifie si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère l'ID de l'adresse depuis l'URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si l'ID est valide, supprime l'adresse
if ($id) {
    $adresse = new Adresse();
    $adresse->supprimer($id);
}

// Redirige vers la page des adresses
redirect(BASE_URL . '/user/mes_adresses.php');
