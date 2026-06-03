<?php
// ── CONFIGURATION DES ERREURS ──────────────────────────────────────
// Active le rapport d'erreurs tout en masquant les avertissements et notices
// pour ne pas perturber l'affichage du site en production
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
header('Content-Type: text/html; charset=utf-8');

// ── DÉTECTION DE L'ENVIRONNEMENT ───────────────────────────────────
// Détecte si la connexion utilise HTTPS ou HTTP
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
// Récupère le nom d'hôte du serveur (ex: localhost, claudishop.com)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Chemin racine du projet dans le système de fichiers (config/../ = racine)
$projectDir = str_replace('\\', '/', dirname(__DIR__));
// Chemin du script en cours d'exécution (système de fichiers)
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
// Chemin du script en cours d'exécution (URL)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$relativePath = '';

// ── Méthode 1 : via la profondeur du script dans l'arborescence ──
// Compare le chemin fichier du script avec la racine du projet
// pour déterminer la profondeur, puis remonte d'autant dans l'URL
if ($scriptFile !== '' && $scriptName !== '' && stripos($scriptFile, $projectDir) === 0) {
    $subPath = substr($scriptFile, strlen($projectDir));
    $depth = 0;
    $trimmed = trim($subPath, '/');
    if ($trimmed !== '') {
        $depth = substr_count($trimmed, '/');
    }
    // Remonte de (profondeur + 1) niveaux depuis SCRIPT_NAME
    $relativePath = $scriptName;
    for ($i = 0; $i <= $depth; $i++) {
        $relativePath = dirname($relativePath);
    }
}

// ── Méthode 2 : via DOCUMENT_ROOT ──
if ($relativePath === '' || $relativePath === '/' || $relativePath === '\\') {
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot !== '' && stripos($projectDir, $docRoot) === 0) {
        $relativePath = substr($projectDir, strlen($docRoot));
    }
}

// ── Méthode 3 : via REQUEST_URI ──
if ($relativePath === '' || $relativePath === '/' || $relativePath === '\\') {
    $scriptName2 = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if ($scriptName2 !== '' && $requestUri !== '') {
        $uriPath = parse_url($requestUri, PHP_URL_PATH);
        if ($uriPath !== false && $uriPath !== null && strpos($uriPath, dirname($scriptName2)) === 0) {
            $relativePath = dirname($scriptName2);
        }
    }
}

// Nettoie le résultat (/, ., \ → chaîne vide)
$relativePath = ($relativePath === '' || $relativePath === '/' || $relativePath === '.' || $relativePath === '\\') ? '' : $relativePath;
$relativePath = rtrim($relativePath, '/');

// ── SESSION ISOLATION ──────────────────────────────────────────────
// Garantit que les cookies de session sont strictement limités
// au chemin de CE projet. Sans cela, deux projets sur le même
// localhost (ex: WAMP/XAMPP) partagent leurs cookies de session
// et se connectent automatiquement au même compte.

// Définit le chemin du cookie de session en fonction du chemin relatif du projet
// Si le projet est à la racine, le cookie est accessible sur tout le domaine
$_sessionPath = ($relativePath !== '') ? rtrim($relativePath, '/') . '/' : '/';
// Configure les paramètres du cookie de session : chemin, HTTP seulement et SameSite
session_set_cookie_params([
    'path'     => $_sessionPath,
    'httponly' => true,
    'samesite' => 'Lax',
]);
// Génère un nom de session unique basé sur le chemin du projet (via MD5)
// pour éviter les conflits entre plusieurs projets sur le même serveur
$_sessionName = 'CLAUDISHOP_' . md5($relativePath ?: '/');
// Applique le nom de session personnalisé
session_name($_sessionName);
// Démarre la session PHP
session_start();

// ── CONSTANTES D'URL ──────────────────────────────────────────────
// Définit l'URL de base du site construite dynamiquement
define('BASE_URL', $protocol . '://' . $host . $relativePath);
// Alias de BASE_URL pour les fichiers publics
define('PUBLIC_URL', BASE_URL);
// URL complète vers le dossier des assets (CSS, JS, images)
define('ASSETS_URL', BASE_URL . '/assets');
// URL complète vers le dossier des uploads
define('UPLOADS_URL', BASE_URL . '/uploads');
// Chemin physique absolu vers le dossier des uploads sur le serveur
define('UPLOADS_DIR', __DIR__ . '/../uploads');

// ── CONSTANTES DE LA BOUTIQUE (LOCALISATION) ──────────────────────
// Coordonnées GPS de la boutique située à Wologede, Mairie, Cotonou
define('SHOP_LAT', 6.3650);
define('SHOP_LNG', 2.4330);
// Adresse texte de la boutique
define('SHOP_ADDRESS', 'Wologede, Mairie, Cotonou');
// Tarif de livraison par kilomètre en FCFA
define('DELIVERY_PRICE_PER_KM', 200);
// Montant minimum de commande pour bénéficier de la livraison gratuite (en FCFA)
define('DELIVERY_FREE_MIN', 500000);
// Rayon maximal de livraison en kilomètres
define('DELIVERY_MAX_KM', 50);

// ── FONCTIONS DYNAMIQUES DE LOCALISATION ─────────────────────────
// Lit la position de la boutique depuis la base de données (si disponible),
// sinon utilise les constantes par défaut. Permet à l'admin de modifier
// la position sans toucher au code.

function getShopLat() {
    static $val = null;
    if ($val !== null) return $val;
    try {
        if (!class_exists('Database', false)) { require_once __DIR__ . '/database.php'; }
        $row = getPdo()->query("SELECT latitude FROM configuration_boutique WHERE id = 1")->fetch();
        if ($row && isset($row['latitude'])) { $val = (float)$row['latitude']; return $val; }
    } catch (\Throwable $e) {}
    return SHOP_LAT;
}

function getShopLng() {
    static $val = null;
    if ($val !== null) return $val;
    try {
        if (!class_exists('Database', false)) { require_once __DIR__ . '/database.php'; }
        $row = getPdo()->query("SELECT longitude FROM configuration_boutique WHERE id = 1")->fetch();
        if ($row && isset($row['longitude'])) { $val = (float)$row['longitude']; return $val; }
    } catch (\Throwable $e) {}
    return SHOP_LNG;
}

function getShopAddress() {
    static $val = null;
    if ($val !== null) return $val;
    try {
        if (!class_exists('Database', false)) { require_once __DIR__ . '/database.php'; }
        $row = getPdo()->query("SELECT adresse FROM configuration_boutique WHERE id = 1")->fetch();
        if ($row && !empty($row['adresse'])) { $val = $row['adresse']; return $val; }
    } catch (\Throwable $e) {}
    return SHOP_ADDRESS;
}

function saveShopPosition($latitude, $longitude, $adresse) {
    try {
        if (!class_exists('Database', false)) { require_once __DIR__ . '/database.php'; }
        $pdo = getPdo();
        $stmt = $pdo->prepare("INSERT INTO configuration_boutique (id, latitude, longitude, adresse) VALUES (1, ?, ?, ?) ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), adresse = VALUES(adresse)");
        $stmt->execute([$latitude, $longitude, $adresse]);
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

// ── CONSTANTES FEDAPAY ────────────────────────────────────────────
// Clés API FedaPay pour le mode Sandbox (test)
// Pour activer le mode réel, remplacez ces clés par vos clés live FedaPay
// Obtenez vos clés sur https://dashboard.fedapay.com dans Développeurs > Clés API
// Mode test : décommente les lignes ci-dessous pour activer FedaPay réel
// Le mode simulation (OTP local) est utilisé automatiquement quand la clé est absente
// define('FEDAPAY_API_KEY', 'sk_sandbox_0J1YLx2t8AGkRUHmOmszwSFe');
// define('FEDAPAY_API_SECRET', 'sk_sandbox_0J1YLx2t8AGkRUHmOmszwSFe');

// ── KKIAPAY (Moyen de paiement principal) ──────────────────────────
// Clés API Kkiapay. Laissez vides pour utiliser le mode simulation.
// Inscription : https://app.kkiapay.me
define('KKIAPAY_PUBLIC_KEY', 'dc8deab0544911f1a188116b14abc7f4');
define('KKIAPAY_PRIVATE_KEY', 'tpk_dc8e11c0544911f1a188116b14abc7f4');
define('KKIAPAY_SECRET', 'tsk_dc8e11c1544911f1a188116b14abc7f4');
// Modes de paiement Mobile Money supportés
define('FEDAPAY_MODE_MTN', 'MTN Mobile Money');
define('FEDAPAY_MODE_MOOV', 'Moov Money');

// ── FONCTION : distanceKm ─────────────────────────────────────────
// Calcule la distance en kilomètres entre deux points GPS
// avec la formule de Haversine (grande cercle)
function distanceKm($lat1, $lng1, $lat2, $lng2) {
    // Rayon moyen de la Terre en kilomètres
    $r = 6371;
    // Convertit la différence de latitude en radians
    $dLat = deg2rad($lat2 - $lat1);
    // Convertit la différence de longitude en radians
    $dLng = deg2rad($lng2 - $lng1);
    // Calcule la moitié de la formule de Haversine : a = sin²(Δlat/2) + cos(lat1)·cos(lat2)·sin²(Δlng/2)
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    // Retourne la distance = R × c, où c = 2 × atan2(√a, √(1-a))
    return $r * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

// ── FONCTION : calculerFraisLivraison ─────────────────────────────
// Calcule les frais de livraison pour un client en fonction de sa position géographique
function calculerFraisLivraison($latClient, $lngClient) {
    // Calcule la distance entre la boutique et le client
    $dist = distanceKm(getShopLat(), getShopLng(), $latClient, $lngClient);
    // Vérifie si le client est hors de la zone de livraison autorisée
    if ($dist > DELIVERY_MAX_KM) {
        // Retourne null pour indiquer que la livraison est impossible (hors zone)
        return null;
    }
    // Calcule et retourne le montant des frais (distance × tarif au km)
    return round($dist * DELIVERY_PRICE_PER_KM);
}

// ── FONCTION : securiser ──────────────────────────────────────────
// Nettoie et sécurise une chaîne de caractères pour l'affichage HTML
function securiser($data) {
    // Convertit les caractères spéciaux HTML en entités et supprime les espaces inutiles
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ── FONCTION : redirect ───────────────────────────────────────────
// Effectue une redirection HTTP vers une URL donnée
function redirect($url) {
    // Envoie l'en-tête HTTP de redirection
    header("Location: $url");
    // Arrête l'exécution du script après la redirection
    exit;
}

// ── FONCTION : formatPrix ─────────────────────────────────────────
// Formate un montant numérique en monnaie locale (FCFA)
function formatPrix($montant) {
    // Formate le nombre avec séparateur de milliers (espace) et suffixe FCFA
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

// ── FONCTION : renderPrix ─────────────────────────────────────────
// Génère le HTML d'affichage d'un prix avec éventuel prix soldé
function renderPrix($prix, $soldePrix = null) {
    // Vérifie si un prix soldé valide est fourni (non vide, supérieur à zéro et inférieur au prix normal)
    if (!empty($soldePrix) && $soldePrix > 0 && $soldePrix < $prix) {
        // Affiche le prix soldé en gras et le prix original barré
        return '<span class="prix-solde">' . formatPrix($soldePrix) . '</span> <span class="prix-barre">' . formatPrix($prix) . '</span>';
    }
    // Affiche uniquement le prix normal (pas de solde)
    return '<span class="prix-normal">' . formatPrix($prix) . '</span>';
}

// ── FONCTION : renderModePaiement ─────────────────────────────────
// Affiche le mode de paiement de façon lisible
function renderModePaiement($mode) {
    if (empty($mode)) return '—';
    $modes = [
        'mtn-benin' => 'MTN Mobile Money',
        'moov-benin' => 'Moov Money',
        'wave-benin' => 'Wave',
        'Kkiapay' => 'Kkiapay',
    ];
    return $modes[strtolower($mode)] ?? $mode;
}

// ── FONCTION : normaliserTelephone ────────────────────────────────
// Formate un numéro de téléphone pour WhatsApp (wa.me)
// Gère les formats locaux du Bénin (+229) et du Togo (+228)
function normaliserTelephone($tel) {
    // Si le numéro est vide, retourne une chaîne vide
    if (empty($tel)) return '';
    // Supprime tous les caractères non numériques du numéro
    $digits = preg_replace('/[^0-9]/', '', $tel);
    // Vérifie si le préfixe correspond au Bénin (+229) ou au Togo (+228)
    if (substr($digits, 0, 3) === '229' || substr($digits, 0, 3) === '228') {
        // Supprime l'indicatif pays pour ne garder que le numéro local
        $digits = substr($digits, 3);
    }
    // Si le numéro fait plus de 8 chiffres et commence par 0, supprime le zéro initial
    if (strlen($digits) > 8 && $digits[0] === '0') {
        $digits = substr($digits, 1);
    }
    // Garde uniquement les 8 derniers chiffres du numéro
    $digits = substr($digits, -8);
    // Formate le numéro au standard +229 01 XX XX XX XX
    return '+229 01 ' . substr($digits, 0, 2) . ' ' . substr($digits, 2, 2) . ' ' . substr($digits, 4, 2) . ' ' . substr($digits, 6, 2);
}

// ── FONCTION : formatWhatsApp ─────────────────────────────────────
// Extrait uniquement les chiffres d'un numéro formaté pour WhatsApp
function formatWhatsApp($tel) {
    // Normalise d'abord le numéro de téléphone
    $clean = normaliserTelephone($tel);
    // Supprime tous les caractères non numériques
    $digits = preg_replace('/[^0-9]/', '', $clean);
    // Retourne uniquement les chiffres
    return $digits;
}

// ── FONCTION : isLoggedIn ─────────────────────────────────────────
// Vérifie si un utilisateur est connecté (session active)
function isLoggedIn() {
    // Retourne vrai si l'ID utilisateur est présent dans la session
    return isset($_SESSION['user_id']);
}

// ── FONCTION : verifierUtilisateurExiste ──────────────────────────
// Vérifie que l'utilisateur connecté existe toujours dans la base de données
function verifierUtilisateurExiste() {
    // Si aucun utilisateur n'est connecté, on sort immédiatement
    if (!isset($_SESSION['user_id'])) return;
    // Variable statique pour éviter de vérifier plusieurs fois par requête
    static $_checked = false;
    // Si déjà vérifié dans cette requête, on ne vérifie pas à nouveau
    if ($_checked) return;
    // Marque la vérification comme effectuée
    $_checked = true;
    // Inclut le fichier de connexion à la base de données
    require_once __DIR__ . '/../config/database.php';
    // Obtient l'instance PDO
    $pdo = getPdo();
    // Prépare une requête pour rechercher l'utilisateur par son ID
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE id = ?");
    // Exécute la requête avec l'ID de l'utilisateur connecté
    $stmt->execute([$_SESSION['user_id']]);
    // Vérifie si l'utilisateur a été trouvé dans la base de données
    if (!$stmt->fetch()) {
        // L'utilisateur n'existe plus → détruire la session
        $_SESSION = [];
        session_destroy();
    }
}
// Exécute la vérification à chaque chargement de page
verifierUtilisateurExiste();

// ── NETTOYAGE COMPTE INVITÉ ───────────────────────────────────────
// Si l'invité navigue ailleurs que sur les pages autorisées sans
// avoir défini son mot de passe → compte anonymisé

// ── FONCTION : nettoyerInvite ─────────────────────────────────────
// Nettoie les comptes invités qui n'ont pas été finalisés (mot de passe non défini)
function nettoyerInvite() {
    // Vérifie qu'un utilisateur est connecté et que le compte a été converti en invité
    if (empty($_SESSION['user_id']) || empty($_SESSION['guest_converted'])) {
        return;
    }
    // Si l'invité a déjà défini son mot de passe, on ne fait rien
    if (!empty($_SESSION['guest_password_set'])) {
        return;
    }
    // Récupère le nom du script actuellement exécuté
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    // Liste des pages autorisées pour les invités non finalisés
    $autorisees = ['profil.php', 'deconnexion.php', 'paiement_kkiapay.php',
                    'confirmer_paiement_simple.php', 'verifier.php',
                    'detail_commande.php', 'commander.php', 'annuler_paiement.php',
                    'update_profil.php', 'callback_kkiapay.php'];
    // Si la page actuelle est autorisée, on laisse passer
    if (in_array($page, $autorisees)) {
        return;
    }
    // Inclut le fichier de connexion à la base de données
    require_once __DIR__ . '/../config/database.php';
    // Obtient l'instance PDO
    $pdo = getPdo();
    // Génère un email anonyme pour remplacer l'email original du compte invité
    $emailAnonyme = 'invite_' . $_SESSION['user_id'] . '_' . time() . '@anonyme.local';
    // Prépare la requête de mise à jour pour anonymiser le compte
    $stmt = $pdo->prepare("UPDATE utilisateur SET email = ?, est_actif = 0, mot_de_passe = '' WHERE id = ?");
    // Exécute l'anonymisation du compte (suppression email, désactivation, effacement mot de passe)
    $stmt->execute([$emailAnonyme, $_SESSION['user_id']]);
    // Détruit la session de l'utilisateur
    session_destroy();
    // Redirige vers la page d'accueil
    redirect(BASE_URL . '/index.php');
}
// Exécute le nettoyage des comptes invités à chaque chargement de page
nettoyerInvite();

// ── SYNCHRONISATION DES STATUTS LIVREURS ──────────────────────────
// Un livreur sans livraison active repasse automatiquement en 'Disponible'
// Inclut la classe Livreur
require_once __DIR__ . '/../classes/Livreur.php';
// Crée une instance temporaire de Livreur
$__syncLivreur = new Livreur();
// Lance la synchronisation des statuts des livreurs
$__syncLivreur->syncStatuts();
// Détruit l'instance temporaire pour libérer la mémoire
unset($__syncLivreur);

// ── ASSIGNATION AUTOMATIQUE LIVREURS ─────────────────────────────
// Attribue automatiquement les livraisons en attente à un livreur disponible
require_once __DIR__ . '/../classes/Livraison.php';
$__assignLiv = new Livraison();
$__assignLiv->assignerAutomatique();
unset($__assignLiv);

// ── FONCTION : isAdmin ────────────────────────────────────────────
// Vérifie si l'utilisateur connecté est un administrateur ou gestionnaire
function isAdmin() {
    // Retourne vrai si le rôle est 'admin' ou 'gestionnaire'
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'gestionnaire']);
}

// ── FONCTION : getStatutBadge ─────────────────────────────────────
// Génère un badge HTML formaté pour afficher un statut (couleurs Bootstrap)
function getStatutBadge($statut) {
    // Tableau associatif des badges avec leurs classes CSS respectives
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
        'Simulation'       => '<span class="badge badge-warning">Simulation</span>',
    ];
    // Retourne le badge correspondant au statut, ou un badge générique avec le statut échappé si non trouvé
    return $badges[$statut] ?? '<span class="badge badge-secondary">' . htmlspecialchars($statut, ENT_QUOTES, 'UTF-8') . '</span>';
}

// ── FONCTION : formatTelephone ────────────────────────────────────
// Formate un numéro de téléphone en utilisant la fonction normaliserTelephone
function formatTelephone($tel) {
    // Si le numéro est vide, retourne une chaîne vide
    if (empty($tel)) return '';
    // Délègue le formatage à la fonction normaliserTelephone
    return normaliserTelephone($tel);
}

// ── FONCTION : strftime_fr ────────────────────────────────────────
// Retourne la date du jour formatée en français (ex: Lundi 15 janvier 2026)
function strftime_fr() {
    // Tableau des jours de la semaine en français (index 0 = Dimanche)
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    // Tableau des mois de l'année en français (index 0 = janvier)
    $mois  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    // Crée un objet DateTime représentant la date et l'heure actuelles
    $d = new DateTime();
    // Retourne la chaîne formatée : jour semaine + jour numérique + mois + année
    return $jours[$d->format('w')] . ' ' . $d->format('j') . ' ' . $mois[$d->format('n')-1] . ' ' . $d->format('Y');
}
