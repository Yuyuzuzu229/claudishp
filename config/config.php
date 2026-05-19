<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = str_replace('\\', '/', dirname(__DIR__));
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? 'C:\\wamp64\\www'), '/');
$relativePath = str_ireplace($docRoot, '', $basePath);

// ── SESSION ISOLATION ────────────────────────────────────────────
// Garantit que les cookies de session sont strictement limités
// au chemin de CE projet. Sans cela, deux projets sur le même
// localhost (ex: WAMP/XAMPP) partagent leurs cookies de session
// et se connectent automatiquement au même compte.
$_sessionPath = ($relativePath !== '') ? rtrim($relativePath, '/') . '/' : '/';
session_set_cookie_params([
    'path'     => $_sessionPath,
    'httponly' => true,
    'samesite' => 'Lax',
]);
// Le nom de session inclut le chemin du projet pour l'isoler
// des autres projets tournant sur le même serveur local.
$_sessionName = 'CLAUDISHOP_' . md5($relativePath ?: '/');
session_name($_sessionName);
session_start();
define('BASE_URL', $protocol . '://' . $host . $relativePath);
define('PUBLIC_URL', BASE_URL);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('UPLOADS_DIR', __DIR__ . '/../uploads');

// Boutique — localisation (Wologede, Mairie, Cotonou)
define('SHOP_LAT', 6.3650);
define('SHOP_LNG', 2.4330);
define('SHOP_ADDRESS', 'Wologede, Mairie, Cotonou');
define('DELIVERY_PRICE_PER_KM', 200);   // FCFA par km
define('DELIVERY_FREE_MIN', 500000);     // livraison gratuite au-dessus
define('DELIVERY_MAX_KM', 50);          // rayon max de livraison

function distanceKm($lat1, $lng1, $lat2, $lng2) {
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    return $r * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

function calculerFraisLivraison($latClient, $lngClient) {
    $dist = distanceKm(SHOP_LAT, SHOP_LNG, $latClient, $lngClient);
    if ($dist > DELIVERY_MAX_KM) return null; // hors zone
    return round($dist * DELIVERY_PRICE_PER_KM);
}

function securiser($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function formatPrix($montant) {
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

function renderPrix($prix, $soldePrix = null) {
    if (!empty($soldePrix) && $soldePrix > 0 && $soldePrix < $prix) {
        return '<span class="prix-solde">' . formatPrix($soldePrix) . '</span> <span class="prix-barre">' . formatPrix($prix) . '</span>';
    }
    return '<span class="prix-normal">' . formatPrix($prix) . '</span>';
}

/**
 * Formate un numéro de téléphone pour WhatsApp (wa.me)
 * Gère les formats locaux du Bénin (+229) et du Togo (+228)
 */
function normaliserTelephone($tel) {
    if (empty($tel)) return '';
    $digits = preg_replace('/[^0-9]/', '', $tel);
    if (substr($digits, 0, 3) === '229' || substr($digits, 0, 3) === '228') {
        $digits = substr($digits, 3);
    }
    if (strlen($digits) > 8 && $digits[0] === '0') {
        $digits = substr($digits, 1);
    }
    $digits = substr($digits, -8);
    return '+229 01 ' . substr($digits, 0, 2) . ' ' . substr($digits, 2, 2) . ' ' . substr($digits, 4, 2) . ' ' . substr($digits, 6, 2);
}

function formatWhatsApp($tel) {
    $clean = normaliserTelephone($tel);
    $digits = preg_replace('/[^0-9]/', '', $clean);
    return $digits;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifie que l'utilisateur connecté existe encore en DB
function verifierUtilisateurExiste() {
    if (!isset($_SESSION['user_id'])) return;
    static $_checked = false;
    if ($_checked) return;
    $_checked = true;
    require_once __DIR__ . '/../config/database.php';
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        // L'utilisateur n'existe plus → détruire la session
        $_SESSION = [];
        session_destroy();
    }
}
verifierUtilisateurExiste();

// ── NETTOYAGE COMPTE INVITÉ ──────────────────────────────────────
// Si l'invité navigue ailleurs que sur les pages autorisées sans
// avoir défini son mot de passe → compte anonymisé
function nettoyerInvite() {
    if (empty($_SESSION['user_id']) || empty($_SESSION['guest_converted'])) {
        return;
    }
    if (!empty($_SESSION['guest_password_set'])) {
        return;
    }
    // Période de grâce : pas de nettoyage pendant 24h après création du compte invité
    $createdAt = $_SESSION['guest_created_at'] ?? 0;
    if ($createdAt > 0 && time() - $createdAt < 86400) {
        return;
    }
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $autorisees = ['profil.php', 'deconnexion.php', 'paiement.php',
                    'confirmer_paiement.php', 'paiement_callback.php',
                    'detail_commande.php', 'commander.php', 'annuler_paiement.php'];
    if (in_array($page, $autorisees)) {
        return;
    }
    require_once __DIR__ . '/../config/database.php';
    $pdo = getPdo();
    $emailAnonyme = 'invite_' . $_SESSION['user_id'] . '_' . time() . '@anonyme.local';
    $stmt = $pdo->prepare("UPDATE utilisateur SET email = ?, est_actif = 0, mot_de_passe = '' WHERE id = ?");
    $stmt->execute([$emailAnonyme, $_SESSION['user_id']]);
    session_destroy();
    redirect(BASE_URL . '/index.php');
}
nettoyerInvite();

// Synchronisation automatique du statut des livreurs
// Un livreur sans livraison active repasse en 'Disponible'
require_once __DIR__ . '/../classes/Livreur.php';
$__syncLivreur = new Livreur();
$__syncLivreur->syncStatuts();
unset($__syncLivreur);

function isAdmin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'gestionnaire']);
}

function getStatutBadge($statut) {
    $badges = [
        'Confirmée'        => '<span class="badge badge-info">Confirmée</span>',
        'En préparation'   => '<span class="badge badge-warning">En préparation</span>',
        'En route'         => '<span class="badge badge-warning">En route</span>',
        'En livraison'     => '<span class="badge badge-warning">En livraison</span>',
        'Livrée'           => '<span class="badge badge-success">Livrée</span>',
        'Annulée'          => '<span class="badge badge-danger">Annulée</span>',
        'En attente'       => '<span class="badge badge-secondary">En attente</span>',
        'Prêt à expédier'  => '<span class="badge badge-info">Prêt à expédier</span>',
        'En cours'         => '<span class="badge badge-warning">En cours</span>',
        'Échouée'          => '<span class="badge badge-danger">Échouée</span>',
        'Publié'           => '<span class="badge badge-success">Publié</span>',
        'En modération'    => '<span class="badge badge-warning">En modération</span>',
        'Refusé'           => '<span class="badge badge-danger">Refusé</span>',
        'Confirmé'         => '<span class="badge badge-success">Confirmé</span>',
        'Réussi'           => '<span class="badge badge-success">Réussi</span>',
        'Échoué'           => '<span class="badge badge-danger">Échoué</span>',
        'Actif'            => '<span class="badge badge-success">Actif</span>',
        'Inactif'          => '<span class="badge badge-dark">Inactif</span>',
        'Disponible'       => '<span class="badge badge-success">Disponible</span>',
        'Indisponible'     => '<span class="badge badge-danger">Indisponible</span>',
        'Non lue'          => '<span class="badge badge-info">Non lue</span>',
        'Lue'              => '<span class="badge badge-secondary">Lue</span>',
        'Envoyé'           => '<span class="badge badge-success">Envoyé</span>',
    ];
    return $badges[$statut] ?? '<span class="badge badge-secondary">' . htmlspecialchars($statut, ENT_QUOTES, 'UTF-8') . '</span>';
}
function formatTelephone($tel) {
    if (empty($tel)) return '';
    return normaliserTelephone($tel);
}
function strftime_fr() {
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $d = new DateTime();
    return $jours[$d->format('w')] . ' ' . $d->format('j') . ' ' . $mois[$d->format('n')-1] . ' ' . $d->format('Y');
}
