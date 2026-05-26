<?php
// Inclusion des fichiers de configuration Firebase et base de données
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../config/database.php';

// Classe gérant l'envoi de notifications push via Firebase Cloud Messaging (FCM)
class NotificationFCM {
    /**
     * Envoie une notification push à un livreur spécifique via FCM
     *
     * @param string $token  Token FCM du livreur
     * @param string $titre  Titre de la notification
     * @param string $corps  Corps du message
     * @param array  $data   Données supplémentaires à transmettre
     * @return array         Tableau avec le statut et les détails de l'envoi
     */
    public function envoyerAuLivreur($token, $titre, $corps, $data = []) {
        // Vérifie que le token n'est pas vide et que FCM est configuré
        if (empty($token) || !fcmEstConfigure()) {
            // Retourne un échec si FCM n'est pas configuré ou si le token est vide
            return ['success' => false, 'message' => 'FCM non configuré ou token vide'];
        }

        // Construction du payload complet pour la requête FCM
        $payload = [
            // Token destinataire
            'to' => $token,
            // Partie notification visible
            'notification' => [
                'title' => $titre,
                'body' => $corps,
                'sound' => 'default',
                'badge' => '1',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            // Données personnalisées fusionnées avec les valeurs par défaut
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default',
            ], $data),
            // Priorité élevée pour une livraison immédiate
            'priority' => 'high',
        ];

        // Appel de la méthode privée d'envoi HTTP
        return $this->envoyer($payload);
    }

    /**
     * Envoie une notification push à tous les livreurs actifs disposant d'un token FCM
     *
     * @param string $titre Titre de la notification
     * @param string $corps Corps du message
     * @param array  $data  Données supplémentaires
     * @return array        Tableau récapitulatif des envois
     */
    public function envoyerATous($titre, $corps, $data = []) {
        // Vérifie d'abord que FCM est bien configuré
        if (!fcmEstConfigure()) {
            // Retourne un échec si FCM n'est pas configuré
            return ['success' => false, 'message' => 'FCM non configuré'];
        }

        // Connexion à la base de données pour récupérer les tokens des livreurs actifs
        $pdo = Database::getInstance()->getConnection();
        // Requête : sélectionne tous les tokens FCM des livreurs actifs
        $stmt = $pdo->query("SELECT fcm_token FROM livreur WHERE fcm_token IS NOT NULL AND fcm_token != '' AND est_actif = 1");
        // Tableau qui stockera les résultats de chaque envoi
        $resultats = [];

        // Boucle sur chaque livreur trouvé pour lui envoyer la notification individuellement
        while ($row = $stmt->fetch()) {
            // Envoi de la notification au livreur courant
            $r = $this->envoyerAuLivreur($row['fcm_token'], $titre, $corps, $data);
            // Ajout du résultat de l'envoi au tableau récapitulatif
            $resultats[] = $r;
        }

        // Retourne un succès global avec le détail de chaque envoi
        return ['success' => true, 'details' => $resultats];
    }

    /**
     * Méthode privée qui effectue l'appel HTTP vers l'API FCM de Google
     *
     * @param array $payload Données formatées pour FCM
     * @return array         Résultat de l'appel HTTP (succès ou échec)
     */
    private function envoyer($payload) {
        // Initialisation d'une session cURL vers l'endpoint FCM
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        // Configuration des options cURL
        curl_setopt_array($ch, [
            CURLOPT_POST => true,                                    // Méthode POST
            CURLOPT_POSTFIELDS => json_encode($payload),             // Corps JSON
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . FCM_SERVER_KEY,              // Clé serveur FCM
                'Content-Type: application/json',                    // Type de contenu
            ],
            CURLOPT_RETURNTRANSFER => true,                          // Retourne la réponse
            CURLOPT_TIMEOUT => 10,                                   // Délai d'attente max
            CURLOPT_SSL_VERIFYPEER => true,                          // Vérification SSL
        ]);

        // Exécution de la requête et récupération de la réponse brute
        $raw = curl_exec($ch);
        // Récupération du code HTTP de la réponse
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Fermeture de la session cURL
        curl_close($ch);

        // Vérification du code HTTP (succès si entre 200 et 299)
        if ($httpCode >= 200 && $httpCode < 300) {
            // Décodage de la réponse JSON
            $response = json_decode($raw, true);
            // Retourne le succès basé sur le champ 'success' de la réponse FCM
            return [
                'success' => ($response['success'] ?? 0) > 0,
                'response' => $response,
            ];
        }

        // En cas d'échec HTTP, retourne les détails de l'erreur
        return ['success' => false, 'error' => "HTTP $httpCode", 'raw' => $raw];
    }
}
