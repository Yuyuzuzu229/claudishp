<?php
// Inclut le fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe Livraison : gère les livraisons des commandes, l'assignation des livreurs et le suivi
class Livraison {
    // Instance de la connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère toutes les livraisons avec les détails de la commande, de l'utilisateur, de la zone et du livreur
    public function getAll() {
        // Jointure complexe entre livraison, commande, utilisateur, zone_livraison et livreur
        return $this->db->query("SELECT l.*, co.id as commande_id_ref, u.nom, u.prenom, z.nom as nom_zone, lv.nom as livreur_nom, lv.telephone as livreur_telephone FROM livraison l JOIN commande co ON l.commande_id = co.id JOIN utilisateur u ON co.utilisateur_id = u.id LEFT JOIN zone_livraison z ON l.zone_id = z.id LEFT JOIN livreur lv ON l.livreur_id = lv.id ORDER BY l.date_livraison DESC")->fetchAll();
    }

    // Récupère une livraison spécifique par son ID avec toutes les informations associées
    public function getById($id) {
        // Prépare la requête avec jointures complètes (commande, utilisateur, zone, livreur)
        $stmt = $this->db->prepare("SELECT l.*, co.id as commande_id_ref, u.nom, u.prenom, z.nom as nom_zone, z.tarif as zone_frais, lv.nom as livreur_nom, lv.telephone as livreur_telephone FROM livraison l JOIN commande co ON l.commande_id = co.id JOIN utilisateur u ON co.utilisateur_id = u.id LEFT JOIN zone_livraison z ON l.zone_id = z.id LEFT JOIN livreur lv ON l.livreur_id = lv.id WHERE l.id = ?");
        // Exécute avec l'ID de la livraison
        $stmt->execute([$id]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Récupère la livraison associée à une commande spécifique
    public function getByCommande($commandeId) {
        // Prépare la requête avec les informations du livreur pour une commande donnée
        $stmt = $this->db->prepare("SELECT l.*, lv.nom as livreur_nom, lv.telephone as livreur_telephone, lv.email as livreur_email, lv.photo as livreur_photo, lv.whatsapp as livreur_whatsapp FROM livraison l LEFT JOIN livreur lv ON l.livreur_id = lv.id WHERE l.commande_id = ?");
        // Exécute avec l'ID de la commande
        $stmt->execute([$commandeId]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Récupère les informations de suivi en direct pour une commande (statut, position du livreur)
    public function getSuiviParCommande($commandeId) {
        // Prépare la requête pour le suivi en temps réel du livreur
        $stmt = $this->db->prepare("
            SELECT l.statut, l.latitude_livreur, l.longitude_livreur, l.derniere_position,
                   lv.nom as livreur_nom, lv.telephone as livreur_telephone,
                   lv.email as livreur_email
            FROM livraison l
            LEFT JOIN livreur lv ON l.livreur_id = lv.id
            WHERE l.commande_id = ?
        ");
        // Exécute avec l'ID de la commande
        $stmt->execute([$commandeId]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Met à jour la position GPS en direct d'un livreur pour une livraison
    public function updatePosition($id, $lat, $lng) {
        // Met à jour la latitude, longitude et l'horodatage de la dernière position
        $stmt = $this->db->prepare("UPDATE livraison SET latitude_livreur = ?, longitude_livreur = ?, derniere_position = NOW() WHERE id = ?");
        // Exécute avec les coordonnées
        return $stmt->execute([$lat, $lng, $id]);
    }

    // Récupère toutes les livraisons d'un utilisateur spécifique
    public function getByUtilisateur($userId) {
        // Prépare la requête avec les détails de la commande pour un utilisateur donné
        $stmt = $this->db->prepare("SELECT l.*, co.id as commande_id_ref, co.montant_total, co.date_commande, z.nom as nom_zone FROM livraison l JOIN commande co ON l.commande_id = co.id LEFT JOIN zone_livraison z ON l.zone_id = z.id WHERE co.utilisateur_id = ? ORDER BY co.date_commande DESC");
        // Exécute avec l'ID utilisateur
        $stmt->execute([$userId]);
        // Retourne toutes les lignes
        return $stmt->fetchAll();
    }

    // Retourne le nombre total de livraisons
    public function getNombreTotal() {
        return $this->db->query("SELECT COUNT(*) FROM livraison")->fetchColumn();
    }

    // Retourne le nombre de livraisons en cours (En attente, Prêt à expédier, En cours)
    public function getEnCours() {
        // Compte les livraisons dont le statut indique qu'elles sont en cours
        return $this->db->query("SELECT COUNT(*) FROM livraison WHERE statut IN ('En attente','Prêt à expédier','En cours')")->fetchColumn();
    }

    // Retourne le nombre de livraisons effectuées (statut 'Livrée')
    public function getLivrees() {
        // Compte les livraisons marquées comme livrées
        return $this->db->query("SELECT COUNT(*) FROM livraison WHERE statut = 'Livrée'")->fetchColumn();
    }

    // Crée une nouvelle livraison pour une commande
    public function creer($commandeId, $zoneId, $frais, $adresse, $datePrevue = null, $distanceKm = null) {
        // Insère un enregistrement de livraison avec les informations de base
        $stmt = $this->db->prepare("INSERT INTO livraison (commande_id, zone_id, frais, adresse, date_prevue, distance_km) VALUES (?, ?, ?, ?, ?, ?)");
        // Exécute avec les paramètres fournis
        return $stmt->execute([$commandeId, $zoneId, $frais, $adresse, $datePrevue, $distanceKm]);
    }

    // Assigne un livreur à une livraison et génère un token d'accès sécurisé
    public function assignerLivreur($id, $livreurId) {
        // Génère un token aléatoire sécurisé de 64 caractères hexadécimaux
        $token = bin2hex(random_bytes(32));
        // Met à jour la livraison avec l'ID livreur, le statut et le token
        $stmt = $this->db->prepare("UPDATE livraison SET livreur_id = ?, statut = 'Prêt à expédier', token_acces = ? WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$livreurId, $token, $id]);
    }

    // Récupère le token d'accès d'une livraison
    public function getTokenAcces($id) {
        // Sélectionne uniquement la colonne token_acces
        $stmt = $this->db->prepare("SELECT token_acces FROM livraison WHERE id = ?");
        // Exécute avec l'ID
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        // Retourne le token ou null si non trouvé
        return $r ? $r['token_acces'] : null;
    }

    // Récupère une livraison par son token d'accès (pour la page livreur)
    public function getByToken($token) {
        // Prépare une requête complète avec les informations client et livreur
        $stmt = $this->db->prepare("
            SELECT l.*, co.nom_complet, co.telephone as client_telephone,
                   co.adresse_livraison, co.latitude_client, co.longitude_client,
                   lv.nom as livreur_nom
            FROM livraison l
            JOIN commande co ON l.commande_id = co.id
            LEFT JOIN livreur lv ON l.livreur_id = lv.id
            WHERE l.token_acces = ?
        ");
        // Exécute avec le token
        $stmt->execute([$token]);
        // Retourne une seule ligne
        return $stmt->fetch();
    }

    // Met à jour le statut d'une livraison
    public function updateStatut($id, $statut) {
        // Prépare la mise à jour du statut
        $stmt = $this->db->prepare("UPDATE livraison SET statut = ? WHERE id = ?");
        // Exécute et retourne le résultat
        return $stmt->execute([$statut, $id]);
    }

    /**
     * Assigne automatiquement un livreur disponible aux livraisons en attente
     */
    public function assignerAutomatique() {
        // Récupère tous les IDs des livraisons sans livreur et en attente
        $stmt = $this->db->query("SELECT l.id FROM livraison l WHERE l.livreur_id IS NULL AND l.statut = 'En attente'");
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        // Si aucune livraison en attente, retourne 0
        if (empty($ids)) return 0;

        // Inclut le service de notification pour informer les livreurs
        require_once __DIR__ . '/NotificationService.php';
        $notifSvc = new NotificationService();

        // Compteur de livraisons assignées
        $nb = 0;
        // Parcourt chaque livraison en attente
        foreach ($ids as $livraisonId) {
            // Cherche un livreur actif et disponible aléatoirement
            $stmt2 = $this->db->query("SELECT id, nom, telephone, email FROM livreur WHERE est_actif = 1 AND id NOT IN (SELECT livreur_id FROM livraison WHERE livreur_id IS NOT NULL AND statut NOT IN ('Livrée','Annulée','Échouée')) ORDER BY RAND() LIMIT 1");
            $driver = $stmt2->fetch();
            // Si aucun livreur disponible, arrête le processus
            if (!$driver) break;

            // Assigne le livreur à la livraison
            $this->assignerLivreur($livraisonId, $driver['id']);
            // Met à jour le statut du livreur en 'En livraison'
            $this->db->prepare("UPDATE livreur SET statut = 'En livraison' WHERE id = ?")->execute([$driver['id']]);

            // Charge les informations complètes de la commande pour la notification
            $cmd = $this->db->prepare("SELECT co.id, co.nom_complet, co.telephone, co.adresse_livraison, co.latitude_client, co.longitude_client, co.montant_total, z.nom as nom_zone, l.frais, l.distance_km FROM livraison l JOIN commande co ON l.commande_id = co.id LEFT JOIN zone_livraison z ON l.zone_id = z.id WHERE l.id = ?");
            $cmd->execute([$livraisonId]);
            $commande = $cmd->fetch();
            // Si la commande existe, prépare et envoie les notifications
            if ($commande) {
                // Récupère le token d'accès pour le livreur
                $tokenAcces = $this->getTokenAcces($livraisonId);

                // Construit le texte de position (adresse, GPS et zone)
                $positionTexte = $commande['adresse_livraison'] ? "Adresse : {$commande['adresse_livraison']}\n" : '';
                // Si des coordonnées GPS sont disponibles, ajoute un lien OpenStreetMap
                if ($commande['latitude_client'] && $commande['longitude_client']) {
                    $positionTexte .= "Position GPS : https://www.openstreetmap.org/?mlat={$commande['latitude_client']}&mlon={$commande['longitude_client']}&zoom=15\n";
                }
                // Si une zone est définie, l'ajoute
                if ($commande['nom_zone']) {
                    $positionTexte .= "Zone : {$commande['nom_zone']}\n";
                }

                // Prépare le lien WhatsApp du client
                $waClient = formatWhatsApp($commande['telephone']);
                $msgWA = rawurlencode("Bonjour {$commande['nom_complet']}, je suis votre livreur ClaudiShop ! Contactez-moi sur WhatsApp, je partagerai ma position en direct pour que vous puissiez me suivre.");

                // URL du tableau de bord livreur
                $driverDashboardUrl = BASE_URL . "/driver/dashboard.php";
                // Construit le message court de notification
                $messageCourt = "Livraison ClaudiShop #CMD-" . str_pad($commande['id'], 6, '0', STR_PAD_LEFT)
                    . "\nClient: {$commande['nom_complet']} ({$commande['telephone']})\n"
                    . "{$positionTexte}"
                    . "Total: " . number_format($commande['montant_total'], 0, ',', ' ') . " FCFA\n"
                    . "📱 Contactez le client : https://wa.me/{$waClient}?text={$msgWA}\n"
                    . "📋 Gérer le statut : {$driverDashboardUrl}";

                // Tente d'envoyer les notifications au livreur
                try {
                    // Envoie une notification WhatsApp au livreur
                    $notifSvc->envoyerWhatsApp($driver['telephone'], 'Livraison #CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT), $messageCourt);

                    // Si le livreur a un email, envoie une notification email HTML
                    if (!empty($driver['email'])) {
                        $sujetMail = 'Nouvelle livraison ClaudiShop #CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
                        // Prépare les données de la commande pour le template email
                        $commandeData = [
                            'id' => $commande['id'],
                            'nom_complet' => $commande['nom_complet'],
                            'telephone' => $commande['telephone'],
                            'adresse_livraison' => $commande['adresse_livraison'],
                            'latitude_client' => $commande['latitude_client'],
                            'longitude_client' => $commande['longitude_client'],
                            'nom_zone' => $commande['nom_zone'],
                            'montant_total' => $commande['montant_total'],
                            'frais' => $commande['frais'],
                            'distance_km' => $commande['distance_km'],
                        ];
                        // Construit et envoie l'email HTML
                        $messageHtml = $notifSvc->construireEmailLivraisonHtml($commandeData, $driver, $tokenAcces);
                        $notifSvc->envoyerEmail($driver['email'], $sujetMail, $messageHtml, true);
                    }
                } catch (Exception $e) {
                    // Empêche qu'une erreur de notification bloque les assignations suivantes
                    error_log("Notification error for livraison #{$livraisonId}: " . $e->getMessage());
                }
            }
            // Incrémente le compteur d'assignations
            $nb++;
        }
        // Retourne le nombre de livraisons assignées
        return $nb;
    }

    // Retourne la distance (actuellement retourne 0 - à implémenter avec une API de carte)
    public function getDistance($id) {
        return 0;
    }

    // Libère le livreur associé à une commande et tente d'assigner automatiquement les suivantes
    public function libererLivreurParCommande($commandeId) {
        // Récupère le livreur assigné à cette commande
        $stmt = $this->db->prepare("SELECT livreur_id FROM livraison WHERE commande_id = ? AND livreur_id IS NOT NULL");
        $stmt->execute([$commandeId]);
        $livreur = $stmt->fetch();
        // Si un livreur est trouvé, remet son statut à 'Disponible'
        if ($livreur && $livreur['livreur_id']) {
            $this->db->prepare("UPDATE livreur SET statut = 'Disponible' WHERE id = ?")->execute([$livreur['livreur_id']]);
        }
        // Déclenche l'assignation automatique pour les livraisons en attente
        $this->assignerAutomatique();
    }

    public function getTauxLivraison() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM livraison");
        $total = (int)$stmt->fetchColumn();
        if ($total === 0) return 0;
        $stmt = $this->db->query("SELECT COUNT(*) FROM livraison WHERE statut = 'Livrée'");
        $livrees = (int)$stmt->fetchColumn();
        return round($livrees / $total * 100, 1);
    }

    // Confirme la réception d'une livraison et met à jour le statut de la commande
    public function confirmerReception($id) {
        // Marque la livraison comme 'Livrée' avec la date actuelle
        $stmt = $this->db->prepare("UPDATE livraison SET statut = 'Livrée', date_livraison = NOW() WHERE id = ?");
        $result = $stmt->execute([$id]);
        // Si la mise à jour a réussi, met également à jour le statut de la commande
        if ($result) {
            $cmd = $this->db->prepare("UPDATE commande SET statut = 'Livrée' WHERE id = (SELECT commande_id FROM livraison WHERE id = ?)");
            $cmd->execute([$id]);
        }
        // Retourne un tableau avec le succès et un message
        return ['success' => $result, 'message' => $result ? 'Livraison confirmée.' : 'Erreur lors de la confirmation.'];
    }
}
