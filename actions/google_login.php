<?php
// Inclusion des fichiers de configuration, base de données et mail
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

// Récupère le credential token Google envoyé par le formulaire
$credential = $_POST['credential'] ?? '';
// Si le token est vide, redirige avec une erreur
if (empty($credential)) {
    $_SESSION['error'] = 'Token Google manquant.';
    redirect(BASE_URL . '/pages/connexion.php');
}

// Vérifie que le Google Client ID est configuré
$clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
if (empty($clientId)) {
    $_SESSION['error'] = 'Google Client ID non configuré.';
    redirect(BASE_URL . '/pages/connexion.php');
}

// URL de vérification du token Google
$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);

$response = false;
// Tente de vérifier le token via cURL si disponible
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $err1 = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Si la vérification SSL échoue, retente sans vérification
    if ($httpCode !== 200 || !$response) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
    }
    $err2 = curl_error($ch);
    curl_close($ch);
} elseif (ini_get('allow_url_fopen')) {
    // Sinon, utilise file_get_contents comme fallback
    $ctx = stream_context_create(['http' => ['timeout' => 15], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $response = @file_get_contents($url, false, $ctx);
}

// Si la réponse est vide, retourne une erreur détaillée
if (!$response) {
    $msg = 'Échec connexion Google API.';
    if (!empty($err1)) $msg .= ' (1: ' . $err1 . ')';
    if (!empty($err2)) $msg .= ' (2: ' . $err2 . ')';
    $_SESSION['error'] = $msg;
    redirect(BASE_URL . '/pages/connexion.php');
}

// Décode la réponse JSON du token
$payload = json_decode($response, true);
// Vérifie la validité du payload, de l'audience et de l'email
if (!$payload || ($payload['aud'] ?? '') !== $clientId || empty($payload['email'])) {
    $_SESSION['error'] = 'Token Google invalide.';
    redirect(BASE_URL . '/pages/connexion.php');
}

// Extrait les informations du profil Google
$googleId = $payload['sub'];
$email = $payload['email'];
$nom = $payload['family_name'] ?? '';
$prenom = $payload['given_name'] ?? '';

$pdo = getPdo();

// Cherche si un utilisateur existe déjà avec ce google_id
$stmt = $pdo->prepare("SELECT id, est_actif FROM utilisateur WHERE google_id = ?");
$stmt->execute([$googleId]);
$user = $stmt->fetch();

// Si l'utilisateur existe déjà via Google
if ($user) {
    // Vérifie si le compte est actif
    if (!$user['est_actif']) {
        $_SESSION['error'] = 'Compte désactivé.';
        redirect(BASE_URL . '/pages/connexion.php');
    }
    // Connecte l'utilisateur
    $_SESSION['user_id'] = intval($user['id']);
    $pdo->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = ?")->execute([$user['id']]);
    // Récupère et définit les variables de session
    $stmtU = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
    $stmtU->execute([$user['id']]);
    $u = $stmtU->fetch();
    $_SESSION['user_nom'] = $u['nom'];
    $_SESSION['user_prenom'] = $u['prenom'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_role'] = $u['role'];
    redirect(BASE_URL . '/user/dashboard.php');
}

// Cherche si un utilisateur existe avec cet email (sans google_id)
$stmt = $pdo->prepare("SELECT id, est_actif FROM utilisateur WHERE email = ?");
$stmt->execute([$email]);
$existing = $stmt->fetch();

// Si un compte existe avec cet email, on lie le google_id
if ($existing) {
    if (!$existing['est_actif']) {
        $_SESSION['error'] = 'Compte désactivé.';
        redirect(BASE_URL . '/pages/connexion.php');
    }
    // Lie le google_id au compte existant et connecte
    $pdo->prepare("UPDATE utilisateur SET google_id = ?, derniere_connexion = NOW() WHERE id = ?")->execute([$googleId, $existing['id']]);
    $_SESSION['user_id'] = intval($existing['id']);
    $stmtU = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
    $stmtU->execute([$existing['id']]);
    $u = $stmtU->fetch();
    $_SESSION['user_nom'] = $u['nom'];
    $_SESSION['user_prenom'] = $u['prenom'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_role'] = $u['role'];
    redirect(BASE_URL . '/user/dashboard.php');
}

// Si aucun compte n'existe, crée un nouvel utilisateur avec un mot de passe aléatoire
$passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role, google_id, est_actif, derniere_connexion) VALUES (?, ?, ?, ?, '', 'user', ?, 1, NOW())");
$stmt->execute([$nom, $prenom, $email, $passwordHash, $googleId]);
$userId = intval($pdo->lastInsertId());
// Définit les variables de session pour le nouvel utilisateur
$_SESSION['user_id'] = $userId;
$_SESSION['user_nom'] = $nom;
$_SESSION['user_prenom'] = $prenom;
$_SESSION['user_email'] = $email;
$_SESSION['user_role'] = 'user';

// Redirige vers le dashboard
redirect(BASE_URL . '/user/dashboard.php');
