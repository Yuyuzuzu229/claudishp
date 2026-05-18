<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(200);
    exit;
}

$pdo = getPdo();

// Remplacer l'email par une valeur anonyme + désactiver le compte
// (on ne supprime pas pour ne pas casser la FK commande.utilisateur_id)
$emailAnonyme = 'invite_' . $userId . '_' . time() . '@deleted.local';
$stmt = $pdo->prepare("UPDATE utilisateur SET email = ?, est_actif = 0, mot_de_passe = '' WHERE id = ?");
$stmt->execute([$emailAnonyme, $userId]);

session_destroy();
http_response_code(200);
