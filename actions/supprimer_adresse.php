<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Adresse.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/pages/connexion.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id) {
    $adresse = new Adresse();
    $adresse->supprimer($id);
}

redirect(BASE_URL . '/user/mes_adresses.php');
