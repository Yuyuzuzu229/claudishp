<?php
// Inclusion des fichiers de configuration et de toutes les classes nécessaires à la commande
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
require_once __DIR__ . '/../classes/Kkiapay.php';
require_once __DIR__ . '/../config/database.php';

// Vérifie si la méthode HTTP est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/index.php');
}

// ── AUTO-CREATE USER FOR GUEST CHECKOUT ──────────────────────────
// Drapeau indiquant si l'utilisateur était invité
$wasGuest = false;
// Si l'utilisateur n'est pas connecté, on crée un compte automatiquement
if (!isLoggedIn()) {
    $guestEmail = securiser($_POST['email'] ?? '');
    $guestNomComplet = securiser($_POST['nom_complet'] ?? '');
    $guestTelephone = securiser($_POST['telephone'] ?? '');

    // Vérifie que l'email et le nom sont fournis
    if (empty($guestEmail) || empty($guestNomComplet)) {
        $_SESSION['error'] = 'Veuillez fournir votre email et votre nom.';
        redirect(BASE_URL . '/pages/checkout.php');
    }

    // Sépare le nom complet en prénom et nom
    $parts = explode(' ', $guestNomComplet, 2);
    $prenom = $parts[0];
    $nom = $parts[1] ?? '';

    require_once __DIR__ . '/../classes/Utilisateur.php';
    $utilisateur = new Utilisateur();

    // Vérifie si l'email existe déjà en base
    $existing = $utilisateur->getByEmail($guestEmail);
    if ($existing) {
        // Si l'utilisateur existe déjà, on le connecte directement
        $_SESSION['user_id'] = $existing['id'];
        $_SESSION['user_email'] = $existing['email'];
        $_SESSION['user_prenom'] = $existing['prenom'];
        $_SESSION['user_nom'] = $existing['prenom'] . ' ' . $existing['nom'];
        $_SESSION['user_role'] = $existing['role'];
    } else {
        // Sinon, crée un nouvel utilisateur avec un mot de passe aléatoire
        $randomPassword = bin2hex(random_bytes(8));
        $resultatInscription = $utilisateur->inscrire($nom, $prenom, $guestEmail, $randomPassword, $guestTelephone);
        // Si l'inscription échoue, redirige avec le message d'erreur
        if (!$resultatInscription['success']) {
            $_SESSION['error'] = $resultatInscription['message'];
            redirect(BASE_URL . '/pages/checkout.php');
        }
    }
    $wasGuest = true;
    $_SESSION['guest_converted'] = true;
    $_SESSION['guest_created_at'] = time();
}

// Récupération et sécurisation de toutes les données du formulaire de commande
$modeRetraitRaw = securiser($_POST['mode_retrait'] ?? 'livraison');
$modes = ['Livraison' => 'livraison', 'Retrait en boutique' => 'retrait_magasin'];
$modeRetrait = $modes[$modeRetraitRaw] ?? 'livraison';

$adresseTexte = securiser($_POST['adresse'] ?? '');
$ville = securiser($_POST['ville'] ?? '');
$nomComplet = securiser($_POST['nom_complet'] ?? '');
$telephoneClient = securiser($_POST['telephone'] ?? '');
$zoneId = !empty($_POST['zone_id']) ? intval($_POST['zone_id']) : null;
$modePaiement = securiser($_POST['mode_paiement'] ?? 'Kkiapay');
$instructions = securiser($_POST['instructions'] ?? '');
$telephonePaiement = securiser($_POST['telephone_paiement'] ?? $telephoneClient);
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
// Conversion des coordonnées GPS en float si présentes
$latitude = $latitude !== '' ? floatval($latitude) : null;
$longitude = $longitude !== '' ? floatval($longitude) : null;

$panier = new Panier();

// Si l'utilisateur était invité, transfère le panier de la session vers la base
if ($wasGuest) {
    $panier->transferGuestToDb($_SESSION['user_id']);
}

// Récupère le panier actif et ses lignes
$panierId = $panier->getPanierActif($_SESSION['user_id']);
$lignes = $panier->getLignes($panierId);

// Si le panier est vide, redirige vers le panier
if (empty($lignes)) {
    redirect(BASE_URL . '/pages/panier.php');
}

// Calcule le total du panier
$montantTotal = $panier->calculerTotal($panierId);
$fraisLivraison = 0;
$zoneNom = '';

// Si le mode de retrait est la livraison, calcule les frais de livraison
if ($modeRetrait === 'livraison') {
    // Si une zone a été sélectionnée, récupère son tarif
    if ($zoneId > 0) {
        $zoneObj = new ZoneLivraison();
        $zone = $zoneObj->getById($zoneId);
        if ($zone) {
            $fraisLivraison = (float)$zone['tarif'];
            $zoneNom = $zone['nom'];
            $montantTotal += $fraisLivraison;
        }
    } elseif ($latitude !== null && $longitude !== null) {
        // Sinon, recherche la zone la plus proche via les coordonnées GPS
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

// Si le mode est retrait en boutique, on vide les champs de livraison
if ($modeRetrait === 'retrait_magasin') {
    $adresseTexte = '';
    $ville = '';
    $latitude = null;
    $longitude = null;
    $zoneId = null;
    $zoneNom = '';
}

// Concatène l'adresse complète
$adresseComplete = $ville ? $ville . ' - ' . $adresseTexte : $adresseTexte;

// Crée la commande en base de données
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

// Si la création de la commande a échoué, redirige avec une erreur
if (!$commandeId) {
    $_SESSION['error'] = 'Erreur lors de la création de la commande.';
    redirect(BASE_URL . '/pages/checkout.php');
}

// Prépare les données des lignes de commande
$lignesData = [];
foreach ($lignes as $l) {
    $lignesData[] = [
        'produit_id' => $l['produit_id'],
        'quantite' => $l['quantite'],
        'prix_unitaire' => $l['prix_unitaire'],
        'taille' => $l['taille'] ?? null,
    ];
}
// Ajoute les lignes de produits à la commande
$commandeObj->ajouterLignes($commandeId, $lignesData);

// ── PAIEMENT ──
// Génére une référence unique
$reference = 'KKP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));

// Enregistre le paiement en base de données (statut initial : En attente)
$pdo = getPdo();
$stmt = $pdo->prepare("INSERT INTO paiement (commande_id, montant, mode, telephone_paiement, reference_transaction) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$commandeId, $montantTotal, $modePaiement, $telephonePaiement, $reference]);

// Initialise le mode_paiement dans commande (sera mis à jour par le callback Kkiapay)
$pdo->prepare("UPDATE commande SET mode_paiement = ? WHERE id = ?")->execute([$modePaiement, $commandeId]);

// ── CRÉATION LIVRAISON ──
$livraisonId = null;
$distanceLivraison = null;
// Si l'adresse est vide mais les coordonnées GPS sont fournies, les utilise
if (empty($adresseComplete) && $latitude !== null && $longitude !== null) {
    $adresseComplete = 'Position GPS: ' . $latitude . ', ' . $longitude;
}
// Si c'est une livraison, crée l'entrée de livraison
if ($modeRetrait === 'livraison') {
    // Calcule la distance entre la boutique et le client
    if ($latitude && $longitude) {
        $distanceLivraison = distanceKm(getShopLat(), getShopLng(), $latitude, $longitude);
        // Si les frais sont à zéro, calcule les frais basés sur la distance
        if ($fraisLivraison <= 0 && $distanceLivraison !== null) {
            $fraisCalc = calculerFraisLivraison($latitude, $longitude);
            if ($fraisCalc !== null) {
                $fraisLivraison = $fraisCalc;
                $montantTotal += $fraisLivraison;
            }
        }
    }

    // Crée l'enregistrement de livraison
    $livraisonObj = new Livraison();
    $livraisonObj->creer($commandeId, $zoneId ?: null, $fraisLivraison, $adresseComplete, null, $distanceLivraison);
    $livraisonId = $pdo->lastInsertId();

    // ── AUTO-ASSIGNATION DU LIVREUR DISPONIBLE ──
    $livreurObj = new Livreur();
    $driver = $livreurObj->getPremierDisponible();
    // Si un livreur est disponible, on lui assigne la livraison
    if ($driver) {
        // Assigne le livreur à la livraison et change son statut
        $livraisonObj->assignerLivreur($livraisonId, $driver['id']);
        $livreurObj->changerStatut($driver['id'], 'En livraison');

        // Récupère le token d'accès pour le livreur
        $tokenAcces = $livraisonObj->getTokenAcces($livraisonId);

        // Construit la liste des produits pour les notifications
        $listeProduits = '';
        foreach ($lignes as $l) {
            $tailleTxt = !empty($l['taille']) ? " [Taille: {$l['taille']}]" : '';
            $listeProduits .= "  - {$l['nom']}{$tailleTxt} x{$l['quantite']} = " . number_format($l['prix_unitaire'] * $l['quantite'], 0, ',', ' ') . " FCFA\n";
        }

        // Construit le texte de position pour le livreur
        $positionTexte = '';
        if ($latitude && $longitude) {
            $positionTexte = "Position GPS : https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom=15\n";
            $waPosition = "Position : https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom=15";
        } else {
            $waPosition = "Adresse : {$adresseComplete}";
        }
        $positionTexte .= $adresseComplete ? "Adresse : {$adresseComplete}\n" : '';
        $positionTexte .= $zoneNom ? "Zone : {$zoneNom}\n" : '';
        // Ajoute la distance si disponible
        if ($distanceLivraison !== null) {
            $positionTexte .= "Distance boutique→client : " . round($distanceLivraison, 1) . " km\n";
        }

        // Prépare le lien WhatsApp pour le contact client
        $waClientTel = formatWhatsApp($telephoneClient);
        $msgWA = rawurlencode("Bonjour {$nomComplet}, je suis votre livreur ClaudiShop ! Contactez-moi sur WhatsApp, je partagerai ma position en direct pour que vous puissiez me suivre.");
        $lienWA = "https://wa.me/{$waClientTel}?text={$msgWA}";

        // Message court récapitulatif pour les notifications
        $driverDashboardUrl = BASE_URL . "/driver/dashboard.php";
        $messageCourt = "Livraison ClaudiShop #" . str_pad($commandeId, 4, '0', STR_PAD_LEFT)
            . "\nClient: {$nomComplet} ({$telephoneClient})\n"
            . ($waPosition ?? "Adresse : {$adresseComplete}")
            . "\nTotal: " . number_format($montantTotal, 0, ',', ' ') . " FCFA\n"
            . "📱 Contactez le client : {$lienWA}\n"
            . "📋 Gérer le statut : {$driverDashboardUrl}";

        // ── NOTIFICATION PUSH FCM ──
        // Envoie une notification push Firebase au livreur si son token FCM est défini
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
            // Notification in-app pour le livreur
            $notifSvc->envoyerInApp(0, 'Nouvelle livraison assignée',
                "Livraison #" . str_pad($commandeId, 4, '0', STR_PAD_LEFT) . " — {$nomComplet} — {$telephoneClient}\n📱 Contactez le client sur WhatsApp : {$lienWA}",
                $commandeId
            );

            // Si le livreur a un email, envoie un email récapitulatif
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
                // Prépare les lignes pour l'email
                $lignesMail = [];
                foreach ($lignes as $l) {
                    $lignesMail[] = [
                        'nom' => $l['nom'],
                        'quantite' => $l['quantite'],
                        'prix_unitaire' => $l['prix_unitaire'],
                        'taille' => $l['taille'] ?? null,
                    ];
                }
                // Construit et envoie l'email HTML
                $messageHtml = $notifSvc->construireEmailLivraisonHtml($commandeData, $driver, $tokenAcces, $lignesMail);
                $notifSvc->envoyerEmail($driver['email'], $sujetMail, $messageHtml, true);
            }

            // Envoie une notification WhatsApp au livreur
            $notifSvc->envoyerWhatsApp(
                $driver['telephone'],
                'Livraison #' . str_pad($commandeId, 4, '0', STR_PAD_LEFT),
                $messageCourt
            );
        } catch (Exception $e) {
            // En cas d'erreur de notification, logge l'erreur sans bloquer le processus
            error_log("Notification error for commande #{$commandeId}: " . $e->getMessage());
        }
    }

    // Rattrapage : si l'affectation inline a échoué, tente une assignation automatique
    $livraisonObj->assignerAutomatique();
}

// ── NOTIFICATION POUR L'ADMIN (pour tous les modes : livraison ET retrait boutique) ──
$adminMsg = "Nouvelle commande #" . str_pad($commandeId, 4, '0', STR_PAD_LEFT)
    . " de {$nomComplet} — " . number_format($montantTotal, 0, ',', ' ') . " FCFA"
    . ($modeRetrait === 'retrait_magasin' ? " — Retrait en boutique" : "");
$adminNotif = new NotificationService();
// Récupère tous les admins et gestionnaires
$stmt2 = $pdo->query("SELECT id FROM utilisateur WHERE role IN ('admin','gestionnaire')");
// Envoie une notification in-app à chaque admin
while ($admin = $stmt2->fetch()) {
    $adminNotif->envoyerInApp($admin['id'], 'Nouvelle commande', $adminMsg, $commandeId);
}

// Vide le panier après la création de la commande
$panier->vider($panierId);

// Redirige vers la page de paiement Kkiapay
redirect(BASE_URL . '/pages/paiement_kkiapay.php?commande_id=' . $commandeId);
