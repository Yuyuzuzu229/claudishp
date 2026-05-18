<?php
class FedaPay {
    private $apiKey;
    private $apiSecret;
    private $environment;
    private $baseUrl;

    public function __construct() {
        $this->environment = 'sandbox';
        $this->apiKey = 'fdp_sandbox_claudishop_key';
        $this->apiSecret = 'fdp_sandbox_claudishop_secret';
        $this->baseUrl = $this->environment === 'sandbox'
            ? 'https://sandbox-api.fedapay.com/v1'
            : 'https://api.fedapay.com/v1';
    }

    private function aDesClesValides() {
        return $this->apiKey !== 'fdp_sandbox_claudishop_key'
            && $this->apiSecret !== 'fdp_sandbox_claudishop_secret'
            && strlen($this->apiKey) > 20
            && strlen($this->apiSecret) > 20;
    }

    // ─── FEDA CHECKOUT (API réelle) ─────────────────────────────────
    public function creerCheckout($montant, $reference, $description, $telephone, $callbackUrl) {
        if (!$this->aDesClesValides()) {
            return $this->fallbackSimulation($montant, $reference);
        }

        $data = [
            'amount' => intval($montant),
            'description' => mb_substr($description, 0, 255),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'currency' => ['iso' => 'XOF'],
        ];

        if (!empty($telephone)) {
            $data['customer'] = [
                'phone_number' => ['value' => $telephone, 'country' => 'bj']
            ];
        }

        $response = $this->apiPost('/checkouts', $data);
        if ($response && ($response['checkout']['token'] ?? null)) {
            return [
                'success' => true,
                'token' => $response['checkout']['token'],
                'url' => $response['checkout']['url'],
                'reference' => $reference,
                'message' => 'Redirection vers FedaPay...'
            ];
        }

        // Fallback si l'API a échoué
        return $this->fallbackSimulation($montant, $reference);
    }

    public function verifierCheckout($token) {
        if (!$this->aDesClesValides()) {
            return [
                'success' => true,
                'statut' => 'approved',
                'reference_transaction' => 'FDP-' . date('Ymd') . '-' . strtoupper(substr(md5($token), 0, 10)),
                'montant' => 0,
                'message' => 'Paiement simulé (clés API non configurées)'
            ];
        }

        $response = $this->apiGet('/checkouts/' . $token);
        if (!$response) {
            return ['success' => false, 'message' => 'Impossible de vérifier le paiement.'];
        }

        $checkout = $response['checkout'] ?? [];
        $transaction = $checkout['transaction'] ?? [];
        $statut = $checkout['status'] ?? 'pending';

        return [
            'success' => $statut === 'approved',
            'statut' => $statut,
            'reference_transaction' => $transaction['reference'] ?? $checkout['reference'] ?? ('FDP-' . strtoupper(substr(md5($token), 0, 10))),
            'montant' => $checkout['amount'] ?? 0,
            'telephone' => $checkout['customer']['phone_number']['value'] ?? '',
            'message' => $statut === 'approved' ? 'Paiement confirmé.' : 'Paiement ' . $statut
        ];
    }

    // ─── MÉTHODES API INTERNES ──────────────────────────────────────
    private function apiPost($endpoint, $data) {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiSecret,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return null;
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($raw, true);
        }
        return null;
    }

    private function apiGet($endpoint) {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiSecret,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return null;
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($raw, true);
        }
        return null;
    }

    // ─── SIMULATION LOCALE (fallback quand clés API non configurées) ─
    private function fallbackSimulation($montant, $reference) {
        $token = 'fdp_' . bin2hex(random_bytes(16));
        $_SESSION['fedapay_token'] = $token;
        $_SESSION['fedapay_montant'] = $montant;
        $_SESSION['fedapay_reference'] = $reference;

        return [
            'success' => true,
            'token' => $token,
            'url' => PUBLIC_URL . '/pages/paiement.php?token=' . $token,
            'reference' => $reference,
            'message' => 'Mode simulation — redirection vers la page de paiement.'
        ];
    }

    // ─── MÉTHODES EXISTANTES (conservées pour compatibilité) ────────
    public function initierPaiement($montant, $reference, $modePaiement, $telephone = null) {
        $callbackUrl = PUBLIC_URL . '/actions/paiement_callback.php';
        $description = 'Commande ' . $reference . ' — ' . $modePaiement;
        return $this->creerCheckout($montant, $reference, $description, $telephone, $callbackUrl);
    }

    public function simulerPaiement($token) {
        $tokenSession = $_SESSION['fedapay_token'] ?? null;
        if ($tokenSession && $tokenSession === $token) {
            return [
                'success' => true,
                'reference_transaction' => 'FDP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                'message' => 'Paiement simulé effectué avec succès.',
                'statut' => 'CONFIRME'
            ];
        }
        // Sinon, tenter via API
        return $this->verifierCheckout($token);
    }

    public function verifierPaiement($token) {
        return $this->verifierCheckout($token);
    }

    public function annulerPaiement($token) {
        unset(
            $_SESSION['fedapay_token'], $_SESSION['fedapay_montant'],
            $_SESSION['fedapay_reference'], $_SESSION['fedapay_mode'],
            $_SESSION['fedapay_telephone']
        );
        return ['success' => true];
    }

    public function genererReference() {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
    }

    public function getEnvironment() {
        return $this->environment;
    }

    public function estModeReel() {
        return $this->aDesClesValides();
    }
}
