<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Avis.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$produitId = intval($_POST['produit_id']);
$note = intval($_POST['note']);
$commentaire = securiser($_POST['commentaire'] ?? '');

if ($note < 1 || $note > 5) {
    $note = 5;
}

$avis = new Avis();
$avis->ajouter($produitId, $_SESSION['user_id'], $note, $commentaire);

$_SESSION['success'] = 'Votre avis a été soumis.';
redirect(BASE_URL . '/user/mes_avis.php');
