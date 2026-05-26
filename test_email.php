<?php
// Inclusion de la configuration et du service de notification
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/NotificationService.php';

// Variables de résultat
$result = null;
$error = null;
$debug = [];

// Traitement du formulaire soumis en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $mode = $_POST['mode'] ?? 'html';
    
    // Vérification que l'email n'est pas vide
    if (!empty($email)) {
        // Test de connexion SMTP direct (pas-à-pas)

        // Débogage : informations de configuration SMTP
        $debug['smtp_host'] = SMTP_HOST;
        $debug['smtp_port'] = SMTP_PORT;
        $debug['smtp_user'] = SMTP_USER;
        $debug['smtp_pass_length'] = strlen(SMTP_PASS ?? '');
        
        // Test 1 : connexion socket au serveur SMTP
        $debug['step1_connect'] = false;
        $socket = @stream_socket_client("tcp://" . SMTP_HOST . ":" . SMTP_PORT, $errno, $errstr, 10);
        // Si la connexion socket réussit
        if ($socket) {
            $debug['step1_connect'] = true;
            // Lecture du greeting du serveur
            $greeting = @fgets($socket, 512);
            $debug['step1_greeting'] = $greeting ? trim($greeting) : 'pas de réponse';
            
            // Test 2 : envoi de la commande EHLO
            @fwrite($socket, "EHLO claudishop\r\n");
            usleep(200000);
            $ehloResp = '';
            // Lecture des réponses jusqu'à la fin de la réponse EHLO
            while ($line = @fgets($socket, 512)) {
                $ehloResp .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            $debug['step2_ehlo'] = trim($ehloResp);
            
            // Test 3 : demande de STARTTLS (passage en TLS)
            @fwrite($socket, "STARTTLS\r\n");
            usleep(200000);
            $starttlsResp = @fgets($socket, 512);
            $debug['step3_starttls'] = $starttlsResp ? trim($starttlsResp) : 'pas de réponse';
            
            // Si STARTTLS est accepté (code 220)
            if ($starttlsResp && strpos($starttlsResp, '220') === 0) {
                // Activation du chiffrement TLS
                $tlsOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $debug['step4_tls'] = $tlsOk ? 'OK' : 'ÉCHEC';
                
                // Si TLS est activé avec succès
                if ($tlsOk) {
                    // Ré-envoi de EHLO après l'établissement de TLS
                    @fwrite($socket, "EHLO claudishop\r\n");
                    usleep(200000);
                    $ehlo2Resp = '';
                    while ($line = @fgets($socket, 512)) {
                        $ehlo2Resp .= $line;
                        if (isset($line[3]) && $line[3] === ' ') break;
                    }
                    $debug['step5_ehlo2'] = trim($ehlo2Resp);
                    
                    // Test 4 : authentification AUTH LOGIN
                    @fwrite($socket, "AUTH LOGIN\r\n");
                    usleep(200000);
                    $authResp = @fgets($socket, 512);
                    $debug['step6_auth_login'] = $authResp ? trim($authResp) : 'pas de réponse';
                    
                    // Si AUTH LOGIN est accepté (code 334)
                    if ($authResp && strpos($authResp, '334') === 0) {
                        // Envoi du nom d'utilisateur (encodé en base64)
                        @fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
                        usleep(200000);
                        $userResp = @fgets($socket, 512);
                        $debug['step7_auth_user'] = $userResp ? trim($userResp) : 'pas de réponse';
                        
                        // Si l'utilisateur est accepté (code 334)
                        if ($userResp && strpos($userResp, '334') === 0) {
                            // Envoi du mot de passe (encodé en base64)
                            @fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
                            usleep(200000);
                            $passResp = '';
                            while ($line = @fgets($socket, 512)) {
                                $passResp .= $line;
                                if (isset($line[3]) && $line[3] === ' ') break;
                            }
                            $debug['step8_auth_pass'] = trim($passResp);
                        } else {
                            $debug['step7_auth_user'] .= ' (CODE INATTENDU)';
                        }
                    } else {
                        $debug['step6_auth_login'] .= ' (CODE INATTENDU)';
                    }
                }
            } else {
                $debug['step4_tls'] = 'NON (STARTTLS refusé)';
            }
            
            // Fermeture de la connexion SMTP
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
        } else {
            // Erreur de connexion socket
            $debug['step1_error'] = "Erreur socket: [$errno] $errstr";
        }
        
        // Envoi de l'email via NotificationService
        $notifSvc = new NotificationService();
        // Si le mode est HTML (email riche)
        if ($mode === 'html') {
            // Données de commande fictives pour le test
            $commandeData = [
                'id' => 999,
                'nom_complet' => 'Client Test',
                'telephone' => '+22990123456',
                'adresse_livraison' => 'Wologede, Cotonou',
                'latitude_client' => 6.3650,
                'longitude_client' => 2.4330,
                'nom_zone' => 'Cotonou Zone A',
                'montant_total' => 25000,
                'frais' => 2500,
                'distance_km' => 5.2,
                'mode_paiement' => 'MTN Mobile Money',
            ];
            $livreur = ['nom' => 'Livreur Test'];
            $token = bin2hex(random_bytes(32));
            $lignes = [
                ['nom' => 'Robe Wax fleurie', 'quantite' => 2, 'prix_unitaire' => 18500],
                ['nom' => 'Sac à main cuir', 'quantite' => 1, 'prix_unitaire' => 22000],
            ];
            // Construction et envoi de l'email HTML de test
            $html = $notifSvc->construireEmailLivraisonHtml($commandeData, $livreur, $token, $lignes);
            $r = $notifSvc->envoyerEmail($email, '[TEST] Nouvelle livraison ClaudiShop #CMD-000999', $html, true);
        } else {
            // Envoi d'un email texte brut de test
            $msg = "TEST LIVRAISON\nClient: Client Test (+22990123456)\nAdresse: Wologede, Cotonou\nPosition: https://www.openstreetmap.org/?mlat=6.3650&mlon=2.4330&zoom=15\nTotal: 25 000 FCFA";
            $r = $notifSvc->envoyerEmail($email, '[TEST] Livraison ClaudiShop #CMD-000999', $msg, false);
        }
        $result = $r;
    } else {
        $error = 'Email destinataire requis';
    }
}

// Lecture des dernières lignes du fichier de log des emails
$logFile = __DIR__ . '/logs/notifications/email_' . date('Y-m-d') . '.log';
$lastLog = '';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLog = implode('', array_slice($lines, -10));
}

// Lecture des dernières lignes du fichier de debug SMTP
$debugLogFile = __DIR__ . '/logs/notifications/smtp_debug_' . date('Y-m-d') . '.log';
$smtpDebugLog = '';
if (file_exists($debugLogFile)) {
    $lines = file($debugLogFile);
    $smtpDebugLog = implode('', array_slice($lines, -30));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Test Email Livraison</title>
<style>
  body { font-family: Arial; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
  .card { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  h1 { margin: 0 0 20px; font-size: 22px; color: #111; }
  label { display: block; margin: 10px 0 5px; font-weight: 600; color: #333; }
  input, select, button { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
  button { background: #111827; color: #fff; border: none; cursor: pointer; font-weight: 600; margin-top: 15px; }
  button:hover { background: #374151; }
  .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; margin: 10px 0; }
  .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin: 10px 0; }
  pre { background: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 8px; font-size: 12px; overflow-x: auto; white-space: pre-wrap; }
  .info { background: #dbeafe; color: #1e40af; padding: 12px; border-radius: 6px; margin: 10px 0; font-size: 13px; }
  .warn { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 6px; margin: 10px 0; font-size: 13px; }
  .row { display: flex; gap: 10px; }
  .row > * { flex: 1; }
  .step-ok { color: #10b981; }
  .step-fail { color: #ef4444; }
</style>
</head>
<body>
<div class="card">
  <h1>📧 Test d'envoi d'email livraison</h1>
  
  <!-- Formulaire de test -->
  <form method="post">
    <label>Email destinataire</label>
    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="ex: moussa.t@claudishop.com" required>
    
    <div class="row">
      <div>
        <label>Mode</label>
        <select name="mode">
          <option value="html" <?= ($_POST['mode'] ?? 'html') === 'html' ? 'selected' : '' ?>>HTML (riche)</option>
          <option value="text" <?= ($_POST['mode'] ?? '') === 'text' ? 'selected' : '' ?>>Texte brut</option>
        </select>
      </div>
    </div>
    
    <button type="submit">🚀 Envoyer l'email de test</button>
  </form>
  
  <!-- Affichage des erreurs -->
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  
  <!-- Affichage du résultat de l'envoi -->
  <?php if ($result !== null): ?>
    <div class="<?= $result['success'] ? 'success' : 'error' ?>">
      <strong><?= $result['success'] ? '✅ ENVOYÉ' : '❌ ÉCHEC' ?></strong><br>
      SMTP: <?= $result['details']['smtp'] ? 'Succès' : 'Échec' ?>
    </div>
  <?php endif; ?>
  
  <!-- Informations de configuration SMTP -->
  <div class="info">
    <strong>Config SMTP :</strong><br>
    Hôte: <?= SMTP_HOST ?>:<?= SMTP_PORT ?><br>
    Utilisateur: <?= SMTP_USER ?><br>
    Expéditeur: <?= SMTP_FROM ?> (<?= SMTP_FROM_NAME ?>)
  </div>

  <!-- Affichage du débogage SMTP pas-à-pas -->
  <?php if (!empty($debug)): ?>
  <h3>🔍 Debug SMTP pas-à-pas</h3>
  <pre>=== Connexion socket ===
<?= $debug['step1_connect'] ? "✅ Connexion réussie" : "❌ Connexion échouée" ?>
<?= isset($debug['step1_error']) ? "   Erreur: " . $debug['step1_error'] : '' ?>
<?= isset($debug['step1_greeting']) ? "   Greeting: " . htmlspecialchars($debug['step1_greeting']) : '' ?>

=== EHLO ===
<?= htmlspecialchars($debug['step2_ehlo'] ?? 'N/A') ?>

=== STARTTLS ===
<?= htmlspecialchars($debug['step3_starttls'] ?? 'N/A') ?>

=== TLS ===
<?= $debug['step4_tls'] ?? 'N/A' ?>

=== EHLO après TLS ===
<?= htmlspecialchars($debug['step5_ehlo2'] ?? 'N/A') ?>

=== AUTH LOGIN ===
<?= htmlspecialchars($debug['step6_auth_login'] ?? 'N/A') ?>

=== USERNAME ===
<?= htmlspecialchars($debug['step7_auth_user'] ?? 'N/A') ?>

=== PASSWORD ===
<?= htmlspecialchars($debug['step8_auth_pass'] ?? 'N/A') ?>
</pre>
  <?php endif; ?>
</div>

<!-- Dernières lignes du log email -->
<div class="card">
  <h2>📋 Dernier log email</h2>
  <pre><?= htmlspecialchars($lastLog ?: 'Aucun log pour aujourd\'hui') ?></pre>
</div>

<!-- Debug SMTP détaillé si disponible -->
<?php if ($smtpDebugLog): ?>
<div class="card">
  <h2>🔧 Debug SMTP détaillé (NotificationService)</h2>
  <pre><?= htmlspecialchars($smtpDebugLog) ?></pre>
</div>
<?php endif; ?>
</body>
</html>
