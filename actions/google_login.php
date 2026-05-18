<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

$credential = $_POST['credential'] ?? '';
if (empty($credential)) {
    $_SESSION['error'] = 'Token Google manquant.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
if (empty($clientId)) {
    $_SESSION['error'] = 'Google Client ID non configuré.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);

$response = false;
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
    if ($httpCode !== 200 || !$response) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
    }
    $err2 = curl_error($ch);
    curl_close($ch);
} elseif (ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 15], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $response = @file_get_contents($url, false, $ctx);
}

if (!$response) {
    $msg = 'Échec connexion Google API.';
    if (!empty($err1)) $msg .= ' (1: ' . $err1 . ')';
    if (!empty($err2)) $msg .= ' (2: ' . $err2 . ')';
    $_SESSION['error'] = $msg;
    redirect(BASE_URL . '/pages/connexion.php');
}

$payload = json_decode($response, true);
if (!$payload || ($payload['aud'] ?? '') !== $clientId || empty($payload['email'])) {
    $_SESSION['error'] = 'Token Google invalide.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$googleId = $payload['sub'];
$email = $payload['email'];
$nom = $payload['family_name'] ?? '';
$prenom = $payload['given_name'] ?? '';

$pdo = getPdo();

$stmt = $pdo->prepare("SELECT id, est_actif FROM utilisateur WHERE google_id = ?");
$stmt->execute([$googleId]);
$user = $stmt->fetch();

if ($user) {
    if (!$user['est_actif']) {
        $_SESSION['error'] = 'Compte désactivé.';
        redirect(BASE_URL . '/pages/connexion.php');
    }
    $_SESSION['user_id'] = intval($user['id']);
    $pdo->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = ?")->execute([$user['id']]);
    $stmtU = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
    $stmtU->execute([$user['id']]);
    $u = $stmtU->fetch();
    $_SESSION['user_nom'] = $u['nom'];
    $_SESSION['user_prenom'] = $u['prenom'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_role'] = $u['role'];
    redirect(BASE_URL . '/user/dashboard.php');
}

$stmt = $pdo->prepare("SELECT id, est_actif FROM utilisateur WHERE email = ?");
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    if (!$existing['est_actif']) {
        $_SESSION['error'] = 'Compte désactivé.';
        redirect(BASE_URL . '/pages/connexion.php');
    }
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

$passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role, google_id, est_actif, derniere_connexion) VALUES (?, ?, ?, ?, '', 'user', ?, 1, NOW())");
$stmt->execute([$nom, $prenom, $email, $passwordHash, $googleId]);
$userId = intval($pdo->lastInsertId());
$_SESSION['user_id'] = $userId;
$_SESSION['user_nom'] = $nom;
$_SESSION['user_prenom'] = $prenom;
$_SESSION['user_email'] = $email;
$_SESSION['user_role'] = 'user';

redirect(BASE_URL . '/user/dashboard.php');
