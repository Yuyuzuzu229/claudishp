<?php
// Inclusion des fichiers de configuration et de base de données
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Récupère l'ID de l'utilisateur connecté
$userId = $_SESSION['user_id'] ?? null;
// Si aucun utilisateur n'est connecté, on ne fait rien
if (!$userId) {
    http_response_code(200);
    exit;
}

$pdo = getPdo();

// Remplace l'email par une valeur anonyme + désactive le compte
// (on ne supprime pas pour ne pas casser la FK commande.utilisateur_id)
$emailAnonyme = 'invite_' . $userId . '_' . time() . '@deleted.local';
$stmt = $pdo->prepare("UPDATE utilisateur SET email = ?, est_actif = 0, mot_de_passe = '' WHERE id = ?");
$stmt->execute([$emailAnonyme, $userId]);

// Détruit la session et retourne un code 200
session_destroy();
http_response_code(200);
