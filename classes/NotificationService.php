<?php
// Inclusion des fichiers de configuration base de données et mail
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

// Classe de service centralisant l'envoi de notifications (WhatsApp, Email, In-app) et la journalisation
class NotificationService {
    // Instance de connexion PDO à la base de données
    private $db;
    // Chemin du répertoire de logs pour les notifications
    private $logDir;

    // Constructeur : initialise la connexion DB et crée le dossier de logs si nécessaire
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logDir = __DIR__ . '/../logs/notifications';
        // Vérifie si le dossier de logs existe, sinon le crée avec les droits 0777
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0777, true);
        }
    }

    /**
     * Envoie une notification WhatsApp via wa.me (ouverture automatique)
     * et stocke l'enregistrement en base.
     *
     * @param string $telephone Numéro de téléphone du destinataire
     * @param string $titre     Titre de la notification
     * @param string $message   Corps du message
     * @return array            Tableau contenant le statut, l'URL WhatsApp et un message
     */
    public function envoyerWhatsApp($telephone, $titre, $message) {
        // Nettoie le numéro : ne garde que les chiffres
        $digits = preg_replace('/[^0-9]/', '', $telephone);
        // Si le numéro nettoyé est vide, retourne une erreur
        if (strlen($digits) <= 0) return ['success' => false, 'message' => 'Numéro invalide'];
        // Si le numéro commence par 0 mais n'est pas 228 ou 229, on préfixe avec 229
        if (!in_array(substr($digits, 0, 3), ['228','229']) && $digits[0] === '0') {
            $digits = '229' . $digits;
        // Sinon, si les 3 premiers chiffres ne sont ni 228 ni 229, on préfixe aussi avec 229
        } elseif (!in_array(substr($digits, 0, 3), ['228','229'])) {
            $digits = '229' . $digits;
        }
        // Construction du lien wa.me avec le message encodé en URL
        $waUrl = 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);

        // Stocker la notification en base (pour historique)
        $stmt = $this->db->prepare("INSERT INTO notification (utilisateur_id, titre, message, canal, commande_id, date_envoi) VALUES (NULL, ?, ?, 'WhatsApp', NULL, NOW())");
        $stmt->execute([$titre, "Cliquez pour ouvrir WhatsApp : " . $waUrl]);

        // Journaliser l'envoi WhatsApp
        $this->log('whatsapp', $digits, $message, $waUrl);

        // Retourne le succès avec l'URL générée
        return [
            'success' => true,
            'url' => $waUrl,
            'message' => 'Lien WhatsApp généré'
        ];
    }

    /**
     * Envoie un email via SMTP.
     * Supporte le texte brut et le HTML (multipart/alternative).
     *
     * @param string $destinataire Adresse email du destinataire
     * @param string $sujet        Sujet de l'email
     * @param string $messageTexte Corps du message (texte brut ou HTML selon $isHtml)
     * @param bool   $isHtml       Indique si $messageTexte est du HTML
     * @return array               Tableau contenant le statut et les détails de l'envoi
     */
    public function envoyerEmail($destinataire, $sujet, $messageTexte, $isHtml = false) {
        // Vérification rapide : SMTP joignable ? (test de connexion socket)
        $sock = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 3);
        // Si la connexion échoue, on journalise et on retourne une erreur
        if (!$sock) {
            $this->log('email', $destinataire, "SMTP unreachable: $errstr", 'SKIPPED');
            return ['success' => false, 'message' => 'SMTP unreachable'];
        }
        // Fermeture du socket de test
        fclose($sock);

        // Tableau des résultats pour chaque méthode d'envoi
        $resultats = [];
        // Expéditeur par défaut si la constante SMTP_FROM n'est pas définie
        $fromEmail = SMTP_FROM ?: 'notification@claudishop.com';

        // Si le message est au format HTML, on génère aussi une version texte brut
        if ($isHtml) {
            // Sauvegarde du HTML original
            $messageHtml = $messageTexte;
            // Conversion du HTML en texte brut (remplacement des balises par des retours à la ligne)
            $messageTexte = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>'], "\n", $messageHtml));
            // Nettoyage des lignes vides consécutives
            $messageTexte = preg_replace('/\n\s*\n\s*\n/', "\n\n", $messageTexte);
            // Appel de la méthode d'envoi multipart (texte + HTML)
            $r = $this->envoyerSmtpMultipart($destinataire, $sujet, $messageTexte, $messageHtml);
        } else {
            // Envoi simple en texte brut
            $r = $this->envoyerSmtpDirect($destinataire, $sujet, $messageTexte);
        }
        // Stockage du résultat SMTP
        $resultats['smtp'] = $r;

        // Journalisation de l'envoi
        $this->log('email', $destinataire, strip_tags($messageTexte), $r ? 'SENT' : 'FAILED');

        // Retourne le résultat global
        return [
            'success' => $r,
            'details' => $resultats
        ];
    }

    /**
     * Construit le corps HTML d'une notification de livraison pour le livreur
     *
     * @param array $commandes Données de la commande
     * @param array $livreur   Informations du livreur
     * @param mixed $tokenAcces Token d'accès (non utilisé directement dans la méthode)
     * @param array $lignes    Lignes de la commande (produits)
     * @return string          Code HTML complet de l'email
     */
    public function construireEmailLivraisonHtml($commandes, $livreur, $tokenAcces, $lignes = []) {
        // Extraction et formatage des données de la commande
        $commandeId = $commandes['id'];
        // Référence de commande formatée (CMD-XXXXXX)
        $refCmd = 'CMD-' . str_pad($commandeId, 6, '0', STR_PAD_LEFT);
        // Nom du client (sécurisé contre les attaques XSS)
        $nomClient = htmlspecialchars($commandes['nom_complet'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        // Téléphone du client
        $telClient = htmlspecialchars($commandes['telephone'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        // Numéro WhatsApp formaté
        $waClient = formatWhatsApp($telClient);
        // Adresse de livraison
        $adresse = htmlspecialchars($commandes['adresse_livraison'] ?? '', ENT_QUOTES, 'UTF-8');
        // Nom de la zone
        $zoneNom = htmlspecialchars($commandes['nom_zone'] ?? '', ENT_QUOTES, 'UTF-8');
        // Coordonnées GPS du client
        $latitude = $commandes['latitude_client'] ?? null;
        $longitude = $commandes['longitude_client'] ?? null;
        // Montant total formaté avec séparateur de milliers
        $montantTotal = number_format($commandes['montant_total'] ?? 0, 0, ',', ' ');
        // Frais de livraison
        $frais = number_format($commandes['frais'] ?? 0, 0, ',', ' ');
        // Distance boutique -> client
        $distance = $commandes['distance_km'] ?? null;

        // Construction du bloc HTML pour la position du client
        $positionHtml = '';
        // Si les coordonnées GPS sont disponibles, on ajoute un lien OpenStreetMap
        if ($latitude && $longitude) {
            $osmLink = "https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}&zoom=15";
            $positionHtml .= "<p><strong>Position GPS :</strong> <a href=\"{$osmLink}\" style=\"color: #2563eb;\">Voir sur la carte</a></p>";
        }
        // Si une adresse est fournie, on l'affiche
        if ($adresse) {
            $positionHtml .= "<p><strong>Adresse :</strong> {$adresse}</p>";
        }
        // Si un nom de zone est fourni, on l'affiche
        if ($zoneNom) {
            $positionHtml .= "<p><strong>Zone :</strong> {$zoneNom}</p>";
        }
        // Si la distance est disponible, on l'affiche
        if ($distance !== null) {
            $positionHtml .= "<p><strong>Distance boutique → client :</strong> " . round($distance, 1) . " km</p>";
        }

        // Construction du tableau HTML des produits commandés
        $produitsHtml = '';
        // Si des lignes de commande existent, on génère le tableau
        if (!empty($lignes)) {
            $produitsHtml = '<table style="width:100%;border-collapse:collapse;margin-top:10px;">';
            $produitsHtml .= '<tr style="background:#f3f4f6;"><th style="padding:8px;text-align:left;border-bottom:2px solid #d1d5db;">Produit</th><th style="padding:8px;text-align:center;border-bottom:2px solid #d1d5db;">Taille</th><th style="padding:8px;text-align:center;border-bottom:2px solid #d1d5db;">Qté</th><th style="padding:8px;text-align:right;border-bottom:2px solid #d1d5db;">Prix</th></tr>';
            // Boucle sur chaque produit de la commande
            foreach ($lignes as $l) {
                $nomP = htmlspecialchars($l['nom'] ?? $l['produit_nom'] ?? 'Produit', ENT_QUOTES, 'UTF-8');
                $tailleP = !empty($l['taille']) ? htmlspecialchars($l['taille'], ENT_QUOTES, 'UTF-8') : '—';
                $qte = $l['quantite'] ?? 1;
                $prix = number_format(($l['prix_unitaire'] ?? 0) * $qte, 0, ',', ' ');
                $produitsHtml .= "<tr><td style=\"padding:8px;border-bottom:1px solid #e5e7eb;\">{$nomP}</td><td style=\"padding:8px;text-align:center;border-bottom:1px solid #e5e7eb;\">{$tailleP}</td><td style=\"padding:8px;text-align:center;border-bottom:1px solid #e5e7eb;\">{$qte}</td><td style=\"padding:8px;text-align:right;border-bottom:1px solid #e5e7eb;\">{$prix} FCFA</td></tr>";
            }
            $produitsHtml .= '</table>';
        }

        // Assemblage complet du template HTML de l'email
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
      <p style="color:#6b7280;font-size:11px;margin:8px 0 0 0;">' . getShopAddress() . '</p>
    </td>
  </tr>
</table>
</body>
</html>';
        // Retourne le code HTML complet de l'email
        return $email;
    }

    /**
     * Tente un envoi SMTP direct sur le serveur configuré (sans authentification)
     *
     * @param string $to      Destinataire
     * @param string $subject Sujet
     * @param string $body    Corps du message en texte brut
     * @return bool           True si l'envoi a réussi, false sinon
     */
    private function envoyerSmtpDirect($to, $subject, $body) {
        // Récupération des paramètres SMTP avec valeurs par défaut
        $host = SMTP_HOST ?: 'localhost';
        $port = SMTP_PORT ?: 25;
        $user = SMTP_USER ?: '';
        $pass = SMTP_PASS ?: '';
        $from = SMTP_FROM ?: 'notification@claudishop.com';

        try {
            // Si des identifiants sont fournis, on utilise la méthode avec authentification
            if (!empty($user) && !empty($pass)) {
                return $this->envoyerSmtpAuth($host, $port, $user, $pass, $from, $to, $subject, $body, false);
            }

            // Connexion socket au serveur SMTP
            $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 3);
            // Si la connexion échoue, retourne false
            if (!$socket) return false;
            // Définit un timeout de 3 secondes sur le socket
            stream_set_timeout($socket, 3);

            // Séquence SMTP : attente du greeting (code 220)
            if (!$this->smtpCheck($socket, 220)) { @fclose($socket); return false; }
            // Envoi de EHLO (code 250 attendu)
            if (!$this->smtpCommande($socket, "EHLO claudishop", 250)) { @fclose($socket); return false; }
            // Envoi de MAIL FROM (code 250 attendu)
            if (!$this->smtpCommande($socket, "MAIL FROM:<$from>", 250)) { @fclose($socket); return false; }
            // Envoi de RCPT TO (code 250 attendu)
            if (!$this->smtpCommande($socket, "RCPT TO:<$to>", 250)) { @fclose($socket); return false; }
            // Envoi de DATA (code 354 attendu)
            if (!$this->smtpCommande($socket, "DATA", 354)) { @fclose($socket); return false; }
            // Construction des en-têtes et du corps
            $headers = "Subject: $subject\r\nFrom: ClaudiShop <$from>\r\nTo: <$to>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n";
            // Envoi du message avec terminaison SMTP
            @fwrite($socket, $headers . $body . "\r\n.\r\n");
            // Vérification de l'accusé de réception (code 250)
            if (!$this->smtpCheck($socket, 250)) { @fclose($socket); return false; }
            // Envoi de QUIT (code 221 attendu)
            $this->smtpCommande($socket, "QUIT", 221);
            // Fermeture du socket
            @fclose($socket);
            // Retourne true si tout s'est bien passé
            return true;
        } catch (Exception $e) {
            // En cas d'exception, retourne false
            return false;
        }
    }

    // Méthode privée : envoi SMTP avec authentification (AUTH LOGIN) et support STARTTLS/TLS
    private function envoyerSmtpAuth($host, $port, $user, $pass, $from, $to, $subject, $body, $isHtml = false) {
        // Connexion socket au serveur SMTP
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 3);
        // Si la connexion échoue, on journalise et on retourne false
        if (!$socket) {
            $this->log('smtp_debug', $host, "CONNECT FAIL: [$errno] $errstr", 'FAIL');
            return false;
        }
        // Timeout de 3 secondes sur le socket
        stream_set_timeout($socket, 3);

        // Génération d'un boundary unique pour le multipart
        $boundary = '----=' . md5(uniqid(rand(), true));

        // Attente du greeting SMTP (code 220)
        $ok = $this->smtpCheck($socket, 220);
        $this->log('smtp_debug', 'greeting', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        // Si le greeting échoue, on ferme et on retourne false
        if (!$ok) { @fclose($socket); return false; }

        // Envoi de EHLO (code 250 attendu)
        $ok = $this->smtpCommande($socket, "EHLO claudishop", 250);
        $this->log('smtp_debug', 'ehlo', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Envoi de STARTTLS pour activer le chiffrement (code 220 attendu)
        $ok = $this->smtpCommande($socket, "STARTTLS", 220);
        $this->log('smtp_debug', 'starttls', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Activation du chiffrement TLS sur le socket
        $tls = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $this->log('smtp_debug', 'tls', $tls ? 'OK' : 'FAIL', $tls ? 'OK' : 'FAIL');

        // Nouveau EHLO après TLS (code 250 attendu)
        $ok = $this->smtpCommande($socket, "EHLO claudishop", 250);
        $this->log('smtp_debug', 'ehlo2', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Début de l'authentification AUTH LOGIN (code 334 attendu)
        $ok = $this->smtpCommande($socket, "AUTH LOGIN", 334);
        $this->log('smtp_debug', 'auth_login', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Envoi du nom d'utilisateur en base64 (code 334 attendu)
        $ok = $this->smtpCommande($socket, base64_encode($user), 334);
        $this->log('smtp_debug', 'auth_user', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Envoi du mot de passe en base64 (code 235 attendu = authentification réussie)
        $ok = $this->smtpCommande($socket, base64_encode($pass), 235);
        $this->log('smtp_debug', 'auth_pass', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Envoi de MAIL FROM (code 250 attendu)
        $ok = $this->smtpCommande($socket, "MAIL FROM:<$from>", 250);
        $this->log('smtp_debug', 'mail_from', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Envoi de RCPT TO (code 250 attendu)
        $ok = $this->smtpCommande($socket, "RCPT TO:<$to>", 250);
        $this->log('smtp_debug', 'rcpt_to', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Envoi de DATA (code 354 attendu)
        $ok = $this->smtpCommande($socket, "DATA", 354);
        $this->log('smtp_debug', 'data_cmd', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        if (!$ok) { @fclose($socket); return false; }

        // Si le message est au format HTML, construction du corps multipart/alternative
        if ($isHtml) {
            // Génération de la version texte brut à partir du HTML
            $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>'], "\n", $body));
            $plainBody = preg_replace('/\n\s*\n\s*\n/', "\n\n", $plainBody);
            // Construction du message multipart avec les deux versions
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
            // Construction d'un message simple en texte brut
            $message = "Subject: $subject\r\n"
                . "From: ClaudiShop <$from>\r\n"
                . "To: <$to>\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=utf-8\r\n"
                . "\r\n"
                . $body . "\r\n";
        }

        // Envoi du message complet avec terminaison SMTP (point seul sur une ligne)
        @fwrite($socket, $message . "\r\n.\r\n");
        // Vérification de l'accusé de réception (code 250)
        $ok = $this->smtpCheck($socket, 250);
        $this->log('smtp_debug', 'data_content', $ok ? 'OK' : 'FAIL', $ok ? 'OK' : 'FAIL');
        // Si l'envoi du contenu échoue, on ferme et on retourne false
        if (!$ok) { @fclose($socket); return false; }

        // Envoi de QUIT pour fermer la session SMTP (code 221 attendu)
        $this->smtpCommande($socket, "QUIT", 221);
        // Fermeture du socket
        @fclose($socket);
        // Retourne true si tout s'est bien passé
        return true;
    }

    /**
     * Vérifie que la réponse SMTP commence par le code attendu
     *
     * @param resource $socket        Socket de connexion SMTP
     * @param int      $expectedCode  Code HTTP/SMTP attendu (ex: 250)
     * @return bool                   True si le code correspond, false sinon
     */
    private function smtpCheck($socket, $expectedCode) {
        // Lecture de la première ligne de réponse (max 512 octets)
        $line = @fgets($socket, 512);
        // Si la lecture échoue, retourne false
        if ($line === false) return false;
        // Extraction des 3 premiers caractères comme code numérique
        $code = (int)substr($line, 0, 3);
        // Vérifie que le code correspond à celui attendu
        return $code === $expectedCode;
    }

    /**
     * Envoie un email multipart (HTML + texte) via SMTP avec auth
     *
     * @param string $to       Destinataire
     * @param string $subject  Sujet
     * @param string $plainBody Version texte brut
     * @param string $htmlBody  Version HTML
     * @return bool            True si l'envoi a réussi, false sinon
     */
    private function envoyerSmtpMultipart($to, $subject, $plainBody, $htmlBody) {
        // Récupération des paramètres SMTP
        $host = SMTP_HOST ?: 'localhost';
        $port = SMTP_PORT ?: 25;
        $user = SMTP_USER ?: '';
        $pass = SMTP_PASS ?: '';
        $from = SMTP_FROM ?: 'notification@claudishop.com';

        // Si des identifiants sont fournis, on utilise la méthode avec authentification
        if (!empty($user) && !empty($pass)) {
            return $this->envoyerSmtpAuth($host, $port, $user, $pass, $from, $to, $subject, $htmlBody, true);
        }

        // Connexion socket directe (sans authentification)
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 3);
        // Si la connexion échoue, retourne false
        if (!$socket) return false;
        stream_set_timeout($socket, 3);

        // Génération d'un boundary unique
        $boundary = '----=' . md5(uniqid(rand(), true));

        // Séquence SMTP standard
        if (!$this->smtpCheck($socket, 220)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "EHLO claudishop", 250)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "MAIL FROM:<$from>", 250)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "RCPT TO:<$to>", 250)) { @fclose($socket); return false; }
        if (!$this->smtpCommande($socket, "DATA", 354)) { @fclose($socket); return false; }

        // Construction du message multipart/alternative (texte + HTML)
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

        // Envoi du message
        @fwrite($socket, $message . "\r\n.\r\n");
        // Vérification de l'accusé de réception
        if (!$this->smtpCheck($socket, 250)) { @fclose($socket); return false; }
        // Fermeture propre de la session
        $this->smtpCommande($socket, "QUIT", 221);
        @fclose($socket);
        return true;
    }

    // Méthode privée : envoie une commande SMTP et vérifie optionnellement le code de réponse
    private function smtpCommande($socket, $cmd, $expectedCode = null) {
        // Envoi de la commande SMTP suivie d'un retour à la ligne
        @fwrite($socket, $cmd . "\r\n");
        // Pause de 100ms pour laisser le serveur répondre
        usleep(100000);
        // Initialisation du dernier code de réponse
        $lastCode = null;
        // Boucle de lecture des lignes de réponse (une réponse peut être multi-lignes)
        while ($line = @fgets($socket, 512)) {
            // Extraction du code numérique à 3 chiffres
            $lastCode = (int)substr($line, 0, 3);
            // Si le 4ème caractère est un espace, c'est la dernière ligne de la réponse
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        // Si un code attendu est spécifié, on vérifie la correspondance
        if ($expectedCode !== null) {
            return $lastCode === $expectedCode;
        }
        // Sinon, retourne true par défaut
        return true;
    }

    /**
     * Envoie les notifications In-app en base (avec liens cliquables)
     *
     * @param mixed $userId     Identifiant de l'utilisateur (peut être null)
     * @param string $titre     Titre de la notification
     * @param string $message   Corps du message
     * @param mixed $commandeId Identifiant de commande associé (optionnel)
     * @return bool             True si l'insertion a réussi, false sinon
     */
    public function envoyerInApp($userId, $titre, $message, $commandeId = null) {
        // Insertion d'une notification avec le canal 'In-app'
        $stmt = $this->db->prepare("INSERT INTO notification (utilisateur_id, titre, message, canal, commande_id, date_envoi) VALUES (?, ?, ?, 'In-app', ?, NOW())");
        return $stmt->execute([$userId ?: null, $titre, $message, $commandeId]);
    }

    /**
     * Journalise les tentatives d'envoi dans un fichier de log quotidien
     *
     * @param string $type         Type de notification (whatsapp, email, etc.)
     * @param string $destinataire Destinataire de la notification
     * @param string $message      Message envoyé (tronqué à 100 caractères)
     * @param string $statut       Statut de l'envoi (SENT, FAILED, SKIPPED, etc.)
     */
    private function log($type, $destinataire, $message, $statut) {
        // Construction du chemin du fichier de log (un fichier par jour par type)
        $logFile = $this->logDir . '/' . $type . '_' . date('Y-m-d') . '.log';
        // Format : [date] destinataire | statut | message (tronqué à 100 caractères)
        $ligne = '[' . date('Y-m-d H:i:s') . '] ' . $destinataire . ' | ' . $statut . ' | ' . mb_substr($message, 0, 100) . PHP_EOL;
        // Écriture dans le fichier en mode ajout avec verrouillage exclusif
        @file_put_contents($logFile, $ligne, FILE_APPEND | LOCK_EX);
    }
}
