<?php
class Kkiapay {
    private $publicKey;
    private $privateKey;
    private $secret;
    private $sandbox;
    private $baseUrl;

    public function __construct() {
        $this->publicKey = defined('KKIAPAY_PUBLIC_KEY') ? KKIAPAY_PUBLIC_KEY : '';
        $this->privateKey = defined('KKIAPAY_PRIVATE_KEY') ? KKIAPAY_PRIVATE_KEY : '';
        $this->secret = defined('KKIAPAY_SECRET') ? KKIAPAY_SECRET : '';
        $this->sandbox = true;
        $this->baseUrl = 'https://api-sandbox.kkiapay.me';
    }

    public function estConfigure() {
        return !empty($this->publicKey) && !empty($this->privateKey) && !empty($this->secret);
    }

    public function getPublicKey() {
        return $this->publicKey;
    }

    public function isSandbox() {
        return $this->sandbox;
    }

    public function verifierPaiement($transactionId) {
        if (!$this->estConfigure()) {
            return [
                'success' => true,
                'status' => 'SUCCESS',
                'message' => 'Paiement simulé (clés Kkiapay non configurées)'
            ];
        }

        $response = $this->apiPost('/api/v1/transactions/status', [
            'transactionId' => $transactionId
        ]);

        if (!$response) {
            return ['success' => false, 'message' => 'Impossible de vérifier la transaction Kkiapay.'];
        }

        $status = $response['status'] ?? '';
        $montant = $response['amount'] ?? 0;

        return [
            'success' => $status === 'SUCCESS',
            'status' => $status,
            'montant' => $montant,
            'telephone' => $response['account'] ?? '',
            'reference' => $response['transactionId'] ?? $transactionId,
            'message' => $status === 'SUCCESS' ? 'Paiement Kkiapay confirmé.' : 'Paiement Kkiapay ' . $status
        ];
    }

    private function apiPost($endpoint, $data) {
        if (!function_exists('curl_init')) return null;

        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $this->publicKey,
                'X-PRIVATE-KEY: ' . $this->privateKey,
                'X-SECRET-KEY: ' . $this->secret,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
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

    public function genererReference() {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
    }
}
