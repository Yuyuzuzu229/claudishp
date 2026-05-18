<?php
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../config/database.php';

class NotificationFCM {
    /**
     * Envoie une notification push à un livreur via FCM
     */
    public function envoyerAuLivreur($token, $titre, $corps, $data = []) {
        if (empty($token) || !fcmEstConfigure()) {
            return ['success' => false, 'message' => 'FCM non configuré ou token vide'];
        }

        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $titre,
                'body' => $corps,
                'sound' => 'default',
                'badge' => '1',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default',
            ], $data),
            'priority' => 'high',
        ];

        return $this->envoyer($payload);
    }

    /**
     * Envoie la notification à tous les livreurs disponibles
     */
    public function envoyerATous($titre, $corps, $data = []) {
        if (!fcmEstConfigure()) {
            return ['success' => false, 'message' => 'FCM non configuré'];
        }

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT fcm_token FROM livreur WHERE fcm_token IS NOT NULL AND fcm_token != '' AND est_actif = 1");
        $resultats = [];

        while ($row = $stmt->fetch()) {
            $r = $this->envoyerAuLivreur($row['fcm_token'], $titre, $corps, $data);
            $resultats[] = $r;
        }

        return ['success' => true, 'details' => $resultats];
    }

    private function envoyer($payload) {
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . FCM_SERVER_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $response = json_decode($raw, true);
            return [
                'success' => ($response['success'] ?? 0) > 0,
                'response' => $response,
            ];
        }

        return ['success' => false, 'error' => "HTTP $httpCode", 'raw' => $raw];
    }
}
