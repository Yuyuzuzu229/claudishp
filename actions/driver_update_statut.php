<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Livreur.php';

if (!isset($_SESSION['driver_id'])) {
    redirect(BASE_URL . '/driver/connexion.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['livraison_id']) || !isset($_POST['statut'])) {
    redirect(BASE_URL . '/driver/dashboard.php');
}

$livraisonId = intval($_POST['livraison_id']);
$nouveauStatut = $_POST['statut'];
$driverId = intval($_SESSION['driver_id']);

$livraisonObj = new Livraison();
$livraison = $livraisonObj->getById($livraisonId);

if (!$livraison || intval($livraison['livreur_id']) !== $driverId) {
    $_SESSION['error'] = 'Livraison introuvable ou non assignée à vous.';
    redirect(BASE_URL . '/driver/dashboard.php');
}

$statutsAutorises = ['En route', 'En cours', 'Livrée'];

if (!in_array($nouveauStatut, $statutsAutorises)) {
    $_SESSION['error'] = 'Statut non autorisé.';
    redirect(BASE_URL . '/driver/dashboard.php');
}

$livraisonObj->updateStatut($livraisonId, $nouveauStatut);

if ($nouveauStatut === 'Livrée') {
    $livraisonObj->confirmerReception($livraisonId);
    $livreurObj = new Livreur();
    $livreurObj->changerStatut($driverId, 'Disponible');
    $livraisonObj->assignerAutomatique();
}

$_SESSION['success'] = 'Statut mis à jour avec succès.';
redirect(BASE_URL . '/driver/dashboard.php');
