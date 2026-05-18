<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../classes/Livreur.php';
require_once __DIR__ . '/../classes/ZoneLivraison.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/NotificationService.php';
require_once __DIR__ . '/../classes/FedaPay.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/index.php');
}

// ── AUTO-CREATE USER FOR GUEST CHECKOUT ──────────────────────────
$wasGuest = false;
if (!isLoggedIn()) {
    $guestEmail = securiser($_POST['email'] ?? '');
    $guestNomComplet = securiser($_POST['nom_complet'] ?? '');
    $guestTelephone = securiser($_POST['telephone'] ?? '');

    if (empty($guestEmail) || empty($guestNomComplet)) {
        $_SESSION['error'] = 'Veuillez fournir votre email et votre nom.';
        redirect(BASE_URL . '/pages/checkout.php');
    }

    // Parse nom_complet into prenom + nom
    $parts = explode(' ', $guestNomComplet, 2);
    $prenom = $parts[0];
    $nom = $parts[1] ?? '';

    require_once __DIR__ . '/../classes/Utilisateur.php';
    $utilisateur = new Utilisateur();

    // Check if email already exists
    $existing = $utilisateur->getByEmail($guestEmail);
    if ($existing) {
        // Existing user: log them in directly
        $_SESSION['user_id'] = $existing['id'];
        $_SESSION['user_email'] = $existing['email'];
        $_SESSION['user_prenom'] = $existing['prenom'];
        $_SESSION['user_nom'] = $existing['prenom'] . ' ' . $existing['nom'];
        $_SESSION['user_role'] = $existing['role'];
    } else {
        // Create new user with random password (inscrire sets session vars too)
        $randomPassword = bin2hex(random_bytes(8));
        $resultatInscription = $utilisateur->inscrire($nom, $prenom, $guestEmail, $randomPassword, $guestTelephone);
        if (!$resultatInscription['success']) {
            $_SESSION['error'] = $resultatInscription['message'];
            redirect(BASE_URL . '/pages/checkout.php');
        }
    }
    $wasGuest = true;
    $_SESSION['guest_converted'] = true;
    $_SESSION['guest_created_at'] = time();
}

$modeRetraitRaw = securiser($_POST['mode_retrait'] ?? 'livraison');
$modes = ['Livraison' => 'livraison', 'Retrait en boutique' => 'retrait_magasin'];
$modeRetrait = $modes[$modeRetraitRaw] ?? 'livraison';

$adresseTexte = securiser($_POST['adresse'] ?? '');
$ville = securiser($_POST['ville'] ?? '');
$nomComplet = securiser($_POST['nom_complet'] ?? '');
$telephoneClient = securiser($_POST['telephone'] ?? '');
$zoneId = intval($_POST['zone_id'] ?? 0);
$modePaiementRaw = $_POST['mode_paiement'] ?? 'MTN Mobile Money';
$modesPaiement = ['MTN MoMo' => 'MTN Mobile Money', 'Moov Money' => 'Moov Money'];
$modePaiement = $modesPaiement[$modePaiementRaw] ?? 'MTN Mobile Money';
$instructions = securiser($_POST['instructions'] ?? '');
$telephonePaiement = securiser($_POST['telephone_paiement'] ?? $telephoneClient);
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$latitude = $latitude !== '' ? floatval($latitude) : null;
$longitude = $longitude !== '' ? floatval($longitude) : null;

$panier = new Panier();

// Transfer guest cart to DB if needed
if ($wasGuest) {
    $panier->transferGuestToDb($_SESSION['user_id']);
}

$panierId = $panier->getPanierActif($_SESSION['user_id']);
$lignes = $panier->getLignes($panierId);

if (empty($lignes)) {
    redirect(BASE_URL . '/pages/panier.php');
}

$montantTotal = $panier->calculerTotal($panierId);
$fraisLivraison = 0;
$zoneNom = '';

if ($modeRetrait === 'livraison') {
    if ($zoneId > 0) {
        $zoneObj = new ZoneLivraison();
        $zone = $zoneObj->getById($zoneId);
        if ($zone) {
            $fraisLivraison = (float)$zone['tarif'];
            $zoneNom = $zone['nom'];
            $montantTotal += $fraisLivraison;
        }
    } elseif ($latitude !== null && $longitude !== null) {
        $zoneObj = new ZoneLivraison();
        $zoneProche = $zoneObj->getProches($latitude, $longitude);
        if ($zoneProche) {
            $zoneId = $zoneProche['id'];
            $zoneNom = $zoneProche['nom'];
            $fraisLivraison = (float)$zoneProche['tarif'];
            $montantTotal += $fraisLivraison;
        }
    }
}

if ($modeRetrait === 'retrait_magasin') {
    $adresseTexte = '';
    $ville = '';
    $latitude = null;
    $longitude = null;
    $zoneId = 0;
    $zoneNom = '';
}

$adresseComplete = $ville ? $ville . ' - ' . $adresseTexte : $adresseTexte;

$commandeObj = new Commande();
$commandeId = $commandeObj->creer(
    $_SESSION['user_id'],
    $montantTotal,
    $modeRetrait,
    $adresseComplete,
    $nomComplet,
    $telephoneClient,
    $instructions,
    $latitude,
    $longitude,
    $zoneId
);

if (!$commandeId) {
    $_SESSION['error'] = 'Erreur lors de la création de la commande.';
    redirect(BASE_URL . '/pages/checkout.php');
}

$lignesData = [];
foreach ($lignes as $l) {
    $lignesData[] = [
        'produit_id' => $l['produit_id'],
        'quantite' => $l['quantite'],
        'prix_unitaire' => $l['prix_unitaire']
    ];
}
$commandeObj->ajouterLignes($commandeId, $lignesData);

// ── PAIEMENT ──
$fedapay = new FedaPay();
$reference = $fedapay->genererReference();
$resultat = $fedapay->initierPaiement($montantTotal, $reference, $modePaiement, $telephonePaiement);

$pdo = getPdo();
$stmt = $pdo->prepare("INSERT INTO paiement (commande_id, montant, mode, telephone_paiement, token, reference_transaction) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$commandeId, $montantTotal, $modePaiement, $telephonePaiement, $resultat['token'] ?? '', $reference]);

// ── CRÉATION LIVRAISON ──
$livraisonId = null;
$distanceLivraison = null;
if (empty($adresseComplete) && $latitude !== null && $longitude !== null) {
    $adresseComplete = 'Position GPS: ' . $latitude . ', ' . $longitude;
}
if ($modeRetrait === 'livraison' && !empty($adresseComplete)) {
    if ($latitude && $longitude) {
        $distanceLivraison = distanceKm(SHOP_LAT, SHOP_LNG, $latitude, $longitude);
        if ($fraisLivraison <= 0 && $distanceLivraison !== null) {
            $fraisCalc = calculerFraisLivraison($latitude, $longitude);
            if ($fraisCalc !== null) {
                $fraisLivraison = $fraisCalc;
                $montantTotal += $fraisLivraison;
            }
        }
    }

    $livraisonObj = new Livraison();
    $livraisonObj->creer($commandeId, $zoneId ?: null, $fraisLivraison, $adresseComplete, null, $distanceLivraison);
    $livraisonId = $pdo->lastInsertId();

    // ── AUTO-ASSIGNATION DU LIVREUR DISPONIBLE ──
    $livreurObj = new Livreur();
    $driver = $livreurObj->getPremierDisponible();
    if ($driver) {
        $livraisonObj->assignerLivreur($livraisonId, $driver['id']);
        $livreurObj->changerStatut($driver['id'], 'En livraison');

        $tokenAcces = $livraisonObj->getTokenAcces($livraisonId);

        $listeProduits = '';
        foreach ($lignes as $l) {
            $listeProduits .= "  - {$l['nom']} x{$l['quantite']} = " . number_format($l['prix_unitaire'] * $l['quantite'], 0, ',', ' ') . " FCFA\n";
        }

        $positionTexte = '';
        if ($latitude && $longitude) {
            $positionTexte = "Position GPS : https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom=15\n";
            $waPosition = "Position : https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom=15";
        } else {
            $waPosition = "Adresse : {$adresseComplete}";
        }
        $positionTexte .= $adresseComplete ? "Adresse : {$adresseComplete}\n" : '';
        $positionTexte .= $zoneNom ? "Zone : {$zoneNom}\n" : '';
        if ($distanceLivraison !== null) {
            $positionTexte .= "Distance boutique→client : " . round($distanceLivraison, 1) . " km\n";
        }

        $waClientTel = formatWhatsApp($telephoneClient);
        $msgWA = rawurlencode("Bonjour {$nomComplet}, je suis votre livreur ClaudiShop ! Contactez-moi sur WhatsApp, je partagerai ma position en direct pour que vous puissiez me suivre.");
        $lienWA = "https://wa.me/{$waClientTel}?text={$msgWA}";

        $driverDashboardUrl = BASE_URL . "/driver/dashboard.php";
        $messageCourt = "Livraison ClaudiShop #" . str_pad($commandeId, 4, '0', STR_PAD_LEFT)
            . "\nClient: {$nomComplet} ({$telephoneClient})\n"
            . ($waPosition ?? "Adresse : {$adresseComplete}")
            . "\nTotal: " . number_format($montantTotal, 0, ',', ' ') . " FCFA\n"
            . "📱 Contactez le client : {$lienWA}\n"
            . "📋 Gérer le statut : {$driverDashboardUrl}";

        // ── NOTIFICATION PUSH FCM ──
        if (!empty($driver['fcm_token'])) {
            require_once __DIR__ . '/../classes/NotificationFCM.php';
            $fcm = new NotificationFCM();
            $fcm->envoyerAuLivreur(
                $driver['fcm_token'],
                'Nouvelle livraison #' . str_pad($commandeId, 4, '0', STR_PAD_LEFT),
                "Client: {$nomComplet} — " . number_format($montantTotal, 0, ',', ' ') . " FCFA",
                ['commande_id' => (string)$commandeId, 'livraison_id' => (string)$livraisonId]
            );
        }

        // ── NOTIFICATIONS AUTOMATIQUES VIA NotificationService ──
        $notifSvc = new NotificationService();
        try {
            $notifSvc->envoyerInApp(0, 'Nouvelle livraison assignée',
                "Livraison #" . str_pad($commandeId, 4, '0', STR_PAD_LEFT) . " — {$nomComplet} — {$telephoneClient}\n📱 Contactez le client sur WhatsApp : {$lienWA}",
                $commandeId
            );

            if (!empty($driver['email'])) {
                $sujetMail = 'Nouvelle livraison ClaudiShop #' . str_pad($commandeId, 4, '0', STR_PAD_LEFT);
                $commandeData = [
                    'id' => $commandeId,
                    'nom_complet' => $nomComplet,
                    'telephone' => $telephoneClient,
                    'adresse_livraison' => $adresseComplete,
                    'latitude_client' => $latitude,
                    'longitude_client' => $longitude,
                    'nom_zone' => $zoneNom,
                    'montant_total' => $montantTotal,
                    'frais' => $fraisLivraison,
                    'distance_km' => $distanceLivraison,
                    'mode_paiement' => $modePaiement,
                ];
                $lignesMail = [];
                foreach ($lignes as $l) {
                    $lignesMail[] = [
                        'nom' => $l['nom'],
                        'quantite' => $l['quantite'],
                        'prix_unitaire' => $l['prix_unitaire'],
                    ];
                }
                $messageHtml = $notifSvc->construireEmailLivraisonHtml($commandeData, $driver, $tokenAcces, $lignesMail);
                $notifSvc->envoyerEmail($driver['email'], $sujetMail, $messageHtml, true);
            }

            $notifSvc->envoyerWhatsApp(
                $driver['telephone'],
                'Livraison #' . str_pad($commandeId, 4, '0', STR_PAD_LEFT),
                $messageCourt
            );
        } catch (Exception $e) {
            error_log("Notification error for commande #{$commandeId}: " . $e->getMessage());
        }
    }

    // ── NOTIFICATION POUR L'ADMIN ──
    $adminMsg = "Nouvelle commande #" . str_pad($commandeId, 4, '0', STR_PAD_LEFT)
        . " de {$nomComplet} — " . number_format($montantTotal, 0, ',', ' ') . " FCFA";
    $adminNotif = new NotificationService();
    $stmt2 = $pdo->query("SELECT id FROM utilisateur WHERE role IN ('admin','gestionnaire')");
    while ($admin = $stmt2->fetch()) {
        $adminNotif->envoyerInApp($admin['id'], 'Nouvelle commande', $adminMsg, $commandeId);
    }
}

$panier->vider($panierId);

$redirectUrl = !empty($resultat['url']) ? $resultat['url'] : (!empty($resultat['url_paiement']) ? $resultat['url_paiement'] : '');
if ($redirectUrl) {
    redirect($redirectUrl);
} else {
    redirect(BASE_URL . '/user/detail_commande.php?id=' . $commandeId);
}
