<?php
require_once __DIR__ . '/../config/database.php';

class Livraison {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT l.*, co.id as commande_id_ref, u.nom, u.prenom, z.nom as nom_zone, lv.nom as livreur_nom, lv.telephone as livreur_telephone FROM livraison l JOIN commande co ON l.commande_id = co.id JOIN utilisateur u ON co.utilisateur_id = u.id LEFT JOIN zone_livraison z ON l.zone_id = z.id LEFT JOIN livreur lv ON l.livreur_id = lv.id ORDER BY l.date_livraison DESC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT l.*, co.id as commande_id_ref, u.nom, u.prenom, z.nom as nom_zone, z.tarif as zone_frais, lv.nom as livreur_nom, lv.telephone as livreur_telephone FROM livraison l JOIN commande co ON l.commande_id = co.id JOIN utilisateur u ON co.utilisateur_id = u.id LEFT JOIN zone_livraison z ON l.zone_id = z.id LEFT JOIN livreur lv ON l.livreur_id = lv.id WHERE l.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCommande($commandeId) {
        $stmt = $this->db->prepare("SELECT l.*, lv.nom as livreur_nom, lv.telephone as livreur_telephone, lv.email as livreur_email, lv.photo as livreur_photo, lv.whatsapp as livreur_whatsapp FROM livraison l LEFT JOIN livreur lv ON l.livreur_id = lv.id WHERE l.commande_id = ?");
        $stmt->execute([$commandeId]);
        return $stmt->fetch();
    }

    public function getSuiviParCommande($commandeId) {
        $stmt = $this->db->prepare("
            SELECT l.statut, l.latitude_livreur, l.longitude_livreur, l.derniere_position,
                   lv.nom as livreur_nom, lv.telephone as livreur_telephone,
                   lv.email as livreur_email
            FROM livraison l
            LEFT JOIN livreur lv ON l.livreur_id = lv.id
            WHERE l.commande_id = ?
        ");
        $stmt->execute([$commandeId]);
        return $stmt->fetch();
    }

    public function updatePosition($id, $lat, $lng) {
        $stmt = $this->db->prepare("UPDATE livraison SET latitude_livreur = ?, longitude_livreur = ?, derniere_position = NOW() WHERE id = ?");
        return $stmt->execute([$lat, $lng, $id]);
    }

    public function getByUtilisateur($userId) {
        $stmt = $this->db->prepare("SELECT l.*, co.id as commande_id_ref, co.montant_total, co.date_commande, z.nom as nom_zone FROM livraison l JOIN commande co ON l.commande_id = co.id LEFT JOIN zone_livraison z ON l.zone_id = z.id WHERE co.utilisateur_id = ? ORDER BY co.date_commande DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getNombreTotal() {
        return $this->db->query("SELECT COUNT(*) FROM livraison")->fetchColumn();
    }

    public function getEnCours() {
        return $this->db->query("SELECT COUNT(*) FROM livraison WHERE statut IN ('En attente','Prêt à expédier','En cours')")->fetchColumn();
    }

    public function getLivrees() {
        return $this->db->query("SELECT COUNT(*) FROM livraison WHERE statut = 'Livrée'")->fetchColumn();
    }

    public function creer($commandeId, $zoneId, $frais, $adresse, $datePrevue = null, $distanceKm = null) {
        $stmt = $this->db->prepare("INSERT INTO livraison (commande_id, zone_id, frais, adresse, date_prevue, distance_km) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$commandeId, $zoneId, $frais, $adresse, $datePrevue, $distanceKm]);
    }

    public function assignerLivreur($id, $livreurId) {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("UPDATE livraison SET livreur_id = ?, statut = 'Prêt à expédier', token_acces = ? WHERE id = ?");
        return $stmt->execute([$livreurId, $token, $id]);
    }

    public function getTokenAcces($id) {
        $stmt = $this->db->prepare("SELECT token_acces FROM livraison WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        return $r ? $r['token_acces'] : null;
    }

    public function getByToken($token) {
        $stmt = $this->db->prepare("
            SELECT l.*, co.nom_complet, co.telephone as client_telephone,
                   co.adresse_livraison, co.latitude_client, co.longitude_client,
                   lv.nom as livreur_nom
            FROM livraison l
            JOIN commande co ON l.commande_id = co.id
            LEFT JOIN livreur lv ON l.livreur_id = lv.id
            WHERE l.token_acces = ?
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function updateStatut($id, $statut) {
        $stmt = $this->db->prepare("UPDATE livraison SET statut = ? WHERE id = ?");
        return $stmt->execute([$statut, $id]);
    }

    /**
     * Assigne automatiquement un livreur disponible aux livraisons en attente
     */
    public function assignerAutomatique() {
        $stmt = $this->db->query("SELECT l.id FROM livraison l WHERE l.livreur_id IS NULL AND l.statut = 'En attente'");
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($ids)) return 0;

        require_once __DIR__ . '/NotificationService.php';
        $notifSvc = new NotificationService();

        $nb = 0;
        foreach ($ids as $livraisonId) {
            $stmt2 = $this->db->query("SELECT id, nom, telephone, email FROM livreur WHERE est_actif = 1 AND id NOT IN (SELECT livreur_id FROM livraison WHERE livreur_id IS NOT NULL AND statut NOT IN ('Livrée','Annulée','Échouée')) ORDER BY RAND() LIMIT 1");
            $driver = $stmt2->fetch();
            if (!$driver) break;

            $this->assignerLivreur($livraisonId, $driver['id']);
            $this->db->prepare("UPDATE livreur SET statut = 'En livraison' WHERE id = ?")->execute([$driver['id']]);

            // Charger les infos de la commande pour la notification
            $cmd = $this->db->prepare("SELECT co.id, co.nom_complet, co.telephone, co.adresse_livraison, co.latitude_client, co.longitude_client, co.montant_total, z.nom as nom_zone, l.frais, l.distance_km FROM livraison l JOIN commande co ON l.commande_id = co.id LEFT JOIN zone_livraison z ON l.zone_id = z.id WHERE l.id = ?");
            $cmd->execute([$livraisonId]);
            $commande = $cmd->fetch();
            if ($commande) {
                $tokenAcces = $this->getTokenAcces($livraisonId);

                $positionTexte = $commande['adresse_livraison'] ? "Adresse : {$commande['adresse_livraison']}\n" : '';
                if ($commande['latitude_client'] && $commande['longitude_client']) {
                    $positionTexte .= "Position GPS : https://www.openstreetmap.org/?mlat={$commande['latitude_client']}&mlon={$commande['longitude_client']}&zoom=15\n";
                }
                if ($commande['nom_zone']) {
                    $positionTexte .= "Zone : {$commande['nom_zone']}\n";
                }

                $waClient = formatWhatsApp($commande['telephone']);
                $msgWA = rawurlencode("Bonjour {$commande['nom_complet']}, je suis votre livreur ClaudiShop ! Contactez-moi sur WhatsApp, je partagerai ma position en direct pour que vous puissiez me suivre.");

                $driverDashboardUrl = BASE_URL . "/driver/dashboard.php";
                $messageCourt = "Livraison ClaudiShop #CMD-" . str_pad($commande['id'], 6, '0', STR_PAD_LEFT)
                    . "\nClient: {$commande['nom_complet']} ({$commande['telephone']})\n"
                    . "{$positionTexte}"
                    . "Total: " . number_format($commande['montant_total'], 0, ',', ' ') . " FCFA\n"
                    . "📱 Contactez le client : https://wa.me/{$waClient}?text={$msgWA}\n"
                    . "📋 Gérer le statut : {$driverDashboardUrl}";

                try {
                    // Notification WhatsApp
                    $notifSvc->envoyerWhatsApp($driver['telephone'], 'Livraison #CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT), $messageCourt);

                    // Notification email (HTML riche)
                    if (!empty($driver['email'])) {
                        $sujetMail = 'Nouvelle livraison ClaudiShop #CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
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
                        $messageHtml = $notifSvc->construireEmailLivraisonHtml($commandeData, $driver, $tokenAcces);
                        $notifSvc->envoyerEmail($driver['email'], $sujetMail, $messageHtml, true);
                    }
                } catch (Exception $e) {
                    // Ã‰viter qu'une erreur de notification bloque les assignations suivantes
                    error_log("Notification error for livraison #{$livraisonId}: " . $e->getMessage());
                }
            }

            $nb++;
        }
        return $nb;
    }

    public function getDistance($id) {
        return 0;
    }

    public function libererLivreurParCommande($commandeId) {
        $stmt = $this->db->prepare("SELECT livreur_id FROM livraison WHERE commande_id = ? AND livreur_id IS NOT NULL");
        $stmt->execute([$commandeId]);
        $livreur = $stmt->fetch();
        if ($livreur && $livreur['livreur_id']) {
            $this->db->prepare("UPDATE livreur SET statut = 'Disponible' WHERE id = ?")->execute([$livreur['livreur_id']]);
        }
        $this->assignerAutomatique();
    }

    public function confirmerReception($id) {
        $stmt = $this->db->prepare("UPDATE livraison SET statut = 'Livrée', date_livraison = NOW() WHERE id = ?");
        $result = $stmt->execute([$id]);
        if ($result) {
            $cmd = $this->db->prepare("UPDATE commande SET statut = 'Livrée' WHERE id = (SELECT commande_id FROM livraison WHERE id = ?)");
            $cmd->execute([$id]);
        }
        return ['success' => $result, 'message' => $result ? 'Livraison confirmée.' : 'Erreur lors de la confirmation.'];
    }
}


