<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

class NotificationService {
    private $db;
    private $logDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logDir = __DIR__ . '/../logs/notifications';
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0777, true);
        }
    }

    /**
     * Envoie une notification WhatsApp via wa.me (ouverture automatique)
     * et stocke l'enregistrement en base.
     */
    public function envoyerWhatsApp($telephone, $titre, $message) {
        $digits = preg_replace('/[^0-9]/', '', $telephone);
        if (strlen($digits) <= 0) return ['success' => false, 'message' => 'Numéro invalide'];
        if (!in_array(substr($digits, 0, 3), ['228','229']) && $digits[0] === '0') {
            $digits = '229' . $digits;
        } elseif (!in_array(substr($digits, 0, 3), ['228','229'])) {
            $digits = '229' . $digits;
        }
        $waUrl = 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);

        // Stocker la notification en base (pour historique)
        $stmt = $this->db->prepare("INSERT INTO notification (utilisateur_id, titre, message, canal, commande_id) VALUES (NULL, ?, ?, 'WhatsApp', NULL)");
        $stmt->execute([$titre, "Cliquez pour ouvrir WhatsApp : " . $waUrl]);

        // Journaliser
        $this->log('whatsapp', $digits, $message, $waUrl);

        return [
            'success' => true,
            'url' => $waUrl,
            'message' => 'Lien WhatsApp généré'
        ];
    }

    /**
     * Envoie un email via SMTP.
     * Supporte le texte brut et le HTML (multipart/alternative).
     */
    public function envoyerEmail($destinataire, $sujet, $messageTexte, $isHtml = false) {
        // Vérification rapide : SMTP joignable ?
        $sock = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 3);
        if (!$sock) {
            $this->log('email', $destinataire, "SMTP unreachable: $errstr", 'SKIPPED');
            return ['success' => false, 'message' => 'SMTP unreachable'];
        }
        fclose($sock);

        $resultats = [];
        $fromEmail = SMTP_FROM ?: 'notification@claudishop.com';

        if ($isHtml) {
            $messageHtml = $messageTexte;
            $messageTexte = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>'], "\n", $messageHtml));
            $messageTexte = preg_replace('/\n\s*\n\s*\n/', "\n\n", $messageTexte);
            $r = $this->envoyerSmtpMultipart($destinataire, $sujet, $messageTexte, $messageHtml);
        } else {
            $r = $this->envoyerSmtpDirect($destinataire, $sujet, $messageTexte);
        }
        $resultats['smtp'] = $r;

        $this->log('email', $destinataire, strip_tags($messageTexte), $r ? 'SENT' : 'FAILED');

        return [
            'success' => $r,
            'details' => $resultats
        ];
    }

    /**
     * Construit le corps HTML d'une notification de livraison pour le livreur
     */
    public function construireEmailLivraisonHtml($commandes, $livreur, $tokenAcces, $lignes = []) {
        $commandeId = $commandes['id'];
        $refCmd = 'CMD-' . str_pad($commandeId, 6, '0', STR_PAD_LEFT);
        $nomClient = htmlspecialchars($commandes['nom_complet'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $telClient = htmlspecialchars($commandes['telephone'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $waClient = formatWhatsApp($telClient);
        $adresse = htmlspecialchars($commandes['adresse_livraison'] ?? '', ENT_QUOTES, 'UTF-8');
        $zoneNom = htmlspecialchars($commandes['nom_zone'] ?? '', ENT_QUOTES, 'UTF-8');
        $latitude = $commandes['latitude_client'] ?? null;
        $longitude = $commandes['longitude_client'] ?? null;
        $montantTotal = number_format($commandes['montant_total'] ?? 0, 0, ',', ' ');
        $frais = number_format($commandes['frais'] ?? 0, 0, ',', ' ');
        $distance = $commandes['distance_km'] ?? null;

        $positionHtml = '';
        if ($latitude && $longitude) {
            $osmLink = "https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom=15";
            $positionHtml .= "<p><strong>Position GPS :</strong> <a href=\"{$osmLink}\" style=\"color: #2563eb;\">Voir sur la carte</a></p>";
        }
        if ($adresse) {
            $positionHtml .= "<p><strong>Adresse :</strong> {$adresse}</p>";
        }
        if ($zoneNom) {
            $positionHtml .= "<p><strong>Zone :</strong> {$zoneNom}</p>";
        }
        if ($distance !== null) {
            $positionHtml .= "<p><strong>Distance boutique → client :</strong> " . round($distance, 1) . " km</p>";
        }

        $produitsHtml = '';
        if (!empty($lignes)) {
            $produitsHtml = '<table style="width:100%;border-collapse:collapse;margin-top:10px;">';
            $produitsHtml .= '<tr style="background:#f3f4f6;"><th style="padding:8px;text-align:left;border-bottom:2px solid #d1d5db;">Produit</th><th style="padding:8px;text-align:center;border-bottom:2px solid #d1d5db;">Qté</th><th style="padding:8px;text-align:right;border-bottom:2px solid #d1d5db;">Prix</th></tr>';
            foreach ($lignes as $l) {
                $nomP = htmlspecialchars($l['nom'] ?? $l['produit_nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8');
                $qte = $l['quantite'] ?? 1;
                $prix = number_format(($l['prix_unitaire'] ?? 0) * $qte, 0, ',', ' ');
                $produitsHtml .= "<tr><td style=\"padding:8px;border-bottom:1px solid #e5e7eb;\">{$nomP}</td><td style=\"padding:8px;text-align:center;border-bottom:1px solid #e5e7eb;\">{$qte}</td><td style=\"padding:8px;text-align:right;border-bottom:1px solid #e5e7eb;\">{$prix} FCFA</td></tr>";
            }
            $produitsHtml .= '</table>';
        }

        $email = '
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
  <tr>
    <td style="background:linear-gradient(135deg,#1f2937,#374151);padding:30px 25px;text-align:center;">
      <h1 style="color:#ffffff;margin:0;font-size:24px;">🚚 Nouvelle Livraison</h1>
      <p style="color:#9ca3af;margin:8px 0 0 0;font-size:14px;">ClaudiShop — ' . $refCmd . '</p>
    </td>
  </tr>
  <tr>
    <td style="padding:25px;">
      <p style="font-size:16px;color:#111827;margin:0 0 20px 0;">Bonjour <strong>' . htmlspecialchars($livreur['nom'] ?? 'Livreur', ENT_QUOTES, 'UTF-8') . '</strong>,</p>
      <p style="font-size:14px;color:#4b5563;margin:0 0 20px 0;">Une nouvelle livraison vous a été assignée. Voici les détails :</p>

      <table style="width:100%;background:#f9fafb;border-radius:8px;padding:15px;margin-bottom:20px;">
        <tr><td style="padding:4px 0;font-size:14px;color:#374151;"><strong>Client :</strong> ' . $nomClient . '</td></tr>
        <tr><td style="padding:4px 0;font-size:14px;color:#374151;"><strong>Téléphone :</strong> <a href="tel:' . $telClient . '" style="color:#2563eb;text-decoration:none;">' . $telClient . '</a></td></tr>
        <tr><td style="padding:4px 0;font-size:14px;color:#374151;"><strong>Référence :</strong> ' . $refCmd . '</td></tr>
      </table>

      <h3 style="font-size:15px;color:#111827;margin:0 0 10px 0;">📍 Position du client</h3>
      <table style="width:100%;background:#f9fafb;border-radius:8px;padding:15px;margin-bottom:20px;">
        <tr><td style="padding:2px 0;font-size:14px;color:#374151;">' . $positionHtml . '</td></tr>
      </table>

      <h3 style="font-size:15px;color:#111827;margin:0 0 10px 0;">📦 Détails de la commande</h3>
      <table style="width:100%;background:#f9fafb;border-radius:8px;padding:15px;margin-bottom:20px;">
        <tr><td style="padding:4px 0;font-size:14px;color:#374151;"><strong>Total :</strong> ' . $montantTotal . ' FCFA</td></tr>
        <tr><td style="padding:4px 0;font-size:14px;color:#374151;"><strong>Frais livraison :</strong> ' . $frais . ' FCFA</td></tr>
        ' . (!empty($commandes['mode_paiement']) ? '<tr><td style="padding:4px 0;font-size:14px;color:#374151;"><strong>Paiement :</strong> ' . htmlspecialchars($commandes['mode_paiement'], ENT_QUOTES, 'UTF-8') . '</td></tr>' : '') . '
        ' . $produitsHtml . '
      </table>

      <h3 style="font-size:15px;color:#111827;margin:0 0 10px 0;">📞 Contacter le client</h3>
      <table style="width:100%;margin:20px 0;">
        <tr><td style="text-align:center;">
          <a href="https://wa.me/' . $waClient . '?text=' . rawurlencode("Bonjour {$nomClient}, je suis votre livreur ClaudiShop ! Je charge votre commande et arrive dans quelques instants.") . '" target="_blank" style="display:inline-block;padding:14px 28px;background-color:#25D366;color:#ffffff;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;">
            <span style="font-size:20px;vertical-align:middle;">📱</span> Contacter le client via WhatsApp
          </a>
        </td></tr>
      </table>
      <p style="font-size:13px;color:#6b7280;margin:0 0 5px 0;">📞 Ou appelez le <a href="tel:' . $telClient . '" style="color:#2563eb;text-decoration:none;">' . $telClient . '</a></p>
    </td>
  </tr>
  <tr>
    <td style="background:#1f2937;padding:20px 25px;text-align:center;">
      <p style="color:#9ca3af;font-size:12px;margin:0;">ClaudiShop — Livraison rapide et fiable</p>
      <p style="color:#6b7280;font-size:11px;margin:8px 0 0 0;">' . SHOP_ADDRESS . '</p>
    </td>
  </tr>
</table>
</body>
</html>';
        return $email;
    }

    /**
     * Tente un envoi SMTP direct sur le serveur configuré
     */
    private function envoyerSmtpDirect($to, $subject, $body) {
        $host = SMTP_HOST ?: 'localhost';
        $port = SMTP_PORT ?: 25;
        $user = SMTP_USER ?: '';
        $pass = SMTP_PASS ?: '';
        $from = SMTP_FROM ?: 'notification@claudishop.com';

        try {
            if (!empty($user) && !empty($pass)) {
                return $this->envoyerSmtpAuth($host, $port, $user, $pass, $from, $to, $subject, $body, false);
            }

            $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 3);
            if (!$socket) return false;
            stream_set_timeout($socket, 3);

            if (!$this->smtpCheck($socket, 220)) { @fclose($socket); return false; }
            if (!$this->smtpCommande($socket, "EHLO claudishop", 250)) { @fclose($socket); return false; }
            if (!$this->smtpCommande($socket, "MAIL FROM:<$from>", 250)) { @fclose($socket); return false; }
            if (!$this->smtpCommande($socket, "RCPT TO:<$to>", 250)) { @fclose($socket); return false; }
            if (!$this->smtpCommande($socket, "DATA", 354)) { @fclose($socket); return false; }
            $headers = "Subject: $subject\r\nFrom: ClaudiShop <$from>\r\nTo: <$to>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n";
            @fwrite($socket, $headers . $body . "\r\n.\r\n");
            if (!$this->smtpCheck($socket, 250)) { @fclose($socket); return false; }
            $this->smtpCommande($socket, "QUIT", 221);
            @fclose($socket);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function envoyerSmtpAuth($host, $port, $user, $pass, $from, $to, $subject, $body, $isHtml = false) {
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 3);
        if (!$socket) {
            $this->log('smtp_debug', $host, "CONNECT FAIL: [$errno] $errstr", 'FAIL');
            return false;
        }
        stream_set_timeout($socket, 3);

        $boundary = '----=' . md5(uniqid(rand(), true));

        $ok = $this->smtpCheck($socket, 220);
        $this->log('smtp_debug', 'greeting', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, "EHLO claudishop", 250);
        $this->log('smtp_debug', 'ehlo', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, "STARTTLS", 220);
        $this->log('smtp_debug', 'starttls', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $tls = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $this->log('smtp_debug', 'tls', $tls ? 'OK' : 'FAIL', $tls ? 'OK' : 'FAIL');

        $ok = $this->smtpCommande($socket, "EHLO claudishop", 250);
        $this->log('smtp_debug', 'ehlo2', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, "AUTH LOGIN", 334);
        $this->log('smtp_debug', 'auth_login', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, base64_encode($user), 334);
        $this->log('smtp_debug', 'auth_user', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, base64_encode($pass), 235);
        $this->log('smtp_debug', 'auth_pass', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, "MAIL FROM:<$from>", 250);
        $this->log('smtp_debug', 'mail_from', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, "RCPT TO:<$to>", 250);
        $this->log('smtp_debug', 'rcpt_to', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $ok = $this->smtpCommande($socket, "DATA", 354);
        $this->log('smtp_debug', 'data_cmd', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        if ($isHtml) {
            $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>'], "\n", $body));
            $plainBody = preg_replace('/\n\s*\n\s*\n/', "\n\n", $plainBody);
            $message = "Subject: $subject\r\n"
                . "From: " . SMTP_FROM_NAME . " <$from>\r\n"
                . "To: <$to>\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
                . "\r\n"
                . "--$boundary\r\n"
                . "Content-Type: text/plain; charset=utf-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n"
                . "\r\n"
                . $plainBody . "\r\n"
                . "\r\n"
                . "--$boundary\r\n"
                . "Content-Type: text/html; charset=utf-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n"
                . "\r\n"
                . $body . "\r\n"
                . "\r\n"
                . "--$boundary--\r\n";
        } else {
            $message = "Subject: $subject\r\n"
                . "From: ClaudiShop <$from>\r\n"
                . "To: <$to>\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=utf-8\r\n"
                . "\r\n"
                . $body . "\r\n";
        }

        @fwrite($socket, $message . "\r\n.\r\n");
        $ok = $this->smtpCheck($socket, 250);
        $this->log('smtp_debug', 'data_content', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        $this->smtpCommande($socket, "QUIT", 221);
        @fclose($socket);
        return true;
    }

    /**
     * Vérifie que la réponse SMTP commence par le code attendu
     */
    private function smtpCheck($socket, $expectedCode) {
        $line = @fgets($socket, 512);
        if ($line === false) return false;
        $code = (int)substr($line, 0, 3);
        return $code === $expectedCode;
    }

    /**
     * Envoie un email multipart (HTML + texte) via SMTP avec auth
     */
    private function envoyerSmtpMultipart($to, $subject, $plainBody, $htmlBody) {
        $host = SMTP_HOST ?: 'localhost';
        $port = SMTP_PORT ?: 25;
        $user = SMTP_USER ?: '';
        $pass = SMTP_PASS ?: '';
        $from = SMTP_FROM ?: 'notification@claudishop.com';

        if (!empty($user) && !empty($pass)) {
            return $this->envoyerSmtpAuth($host, $port, $user, $pass, $from, $to, $subject, $htmlBody, true);
        }

        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 3);
        if (!$socket) return false;
        stream_set_timeout($socket, 3);

        $boundary = '----=' . md5(uniqid(rand(), true));

        if (!$this->smtpCheck($socket, 220)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "EHLO claudishop", 250)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "MAIL FROM:<$from>", 250)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "RCPT TO:<$to>", 250)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "DATA", 354)) { @fclose($socket); return false; }

        $message = "Subject: $subject\r\n"
            . "From: " . SMTP_FROM_NAME . " <$from>\r\n"
            . "To: <$to>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
            . "\r\n"
            . "--$boundary\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "\r\n"
            . $plainBody . "\r\n"
            . "\r\n"
            . "--$boundary\r\n"
            . "Content-Type: text/html; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "\r\n"
            . $htmlBody . "\r\n"
            . "\r\n"
            . "--$boundary--\r\n";

        @fwrite($socket, $message . "\r\n.\r\n");
        if (!$this->smtpCheck($socket, 250)) { @fclose($socket); return false; }
        $this->smtpCommande($socket, "QUIT", 221);
        @fclose($socket);
        return true;
    }

    private function smtpCommande($socket, $cmd, $expectedCode = null) {
        @fwrite($socket, $cmd . "\r\n");
        usleep(100000);
        $lastCode = null;
        while ($line = @fgets($socket, 512)) {
            $lastCode = (int)substr($line, 0, 3);
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if ($expectedCode !== null) {
            return $lastCode === $expectedCode;
        }
        return true;
    }

    /**
     * Envoie les notifications In-app en base (avec liens cliquables)
     */
    public function envoyerInApp($userId, $titre, $message, $commandeId = null) {
        $stmt = $this->db->prepare("INSERT INTO notification (utilisateur_id, titre, message, canal, commande_id) VALUES (?, ?, ?, 'In-app', ?)");
        return $stmt->execute([$userId ?: null, $titre, $message, $commandeId]);
    }

    /**
     * Journalise les tentatives d'envoi
     */
    private function log($type, $destinataire, $message, $statut) {
        $logFile = $this->logDir . '/' . $type . '_' . date('Y-m-d') . '.log';
        $ligne = '[' . date('Y-m-d H:i:s') . '] ' . $destinataire . ' | ' . $statut . ' | ' . mb_substr($message, 0, 100) . PHP_EOL;
        @file_put_contents($logFile, $ligne, FILE_APPEND | LOCK_EX);
    }
}
