<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Adresse.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$quartier = securiser($_POST['quartier']);
$ville = securiser($_POST['ville']);
$pointRepere = securiser($_POST['point_repere'] ?? '');

$adresse = new Adresse();
$adresse->ajouter($quartier, $ville, $pointRepere, $_SESSION['user_id']);

redirect(BASE_URL . '/user/mes_adresses.php');
