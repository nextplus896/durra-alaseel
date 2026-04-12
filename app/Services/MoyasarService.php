<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Moyasar Payment Gateway Service
 *
 * Handles invoice creation and payment verification for wallet recharge
 *
 * @see https://moyasar.com/docs/api/
 */
class MoyasarService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected ?string $webhookSecret;
    protected string $mode;

    public function __construct()
    {
        $this->apiKey = config('moyasar.api_key');
        $this->webhookSecret = config('moyasar.webhook_secret');
        $this->baseUrl = config('moyasar.base_url');
        $this->mode = config('moyasar.mode', 'test');
    }

    /**
     * Check if Moyasar is properly configured
     */
    protected function ensureConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new Exception('Moyasar API key not configured. Please add MOYASAR_API_KEY to .env file.');
        }
    }

    /**
     * Create a Moyasar invoice for wallet recharge
     *
     * @param float $amount Amount to recharge (in SAR)
     * @param int $userId User ID
     * @param string $userEmail User email
     * @param string $userName User name
     * @param array $metadata Additional data to store
     * @return array Invoice data
     * @throws Exception
     */
    public function createInvoice(
        float $amount,
        int $userId,
        string $userEmail,
        string $userName,
        array $metadata = []
    ): array {
        $currency = config('moyasar.invoice.currency', 'SAR');
        $expiresAt = now()->addDays(config('moyasar.invoice.expires_at_days', 7))->toIso8601String();
        $normalizedUserName = trim((string) preg_replace('/\s+/', ' ', $userName));
        if ($normalizedUserName === '') {
            $normalizedUserName = 'User';
        }

        // Moyasar expects amount in the smallest currency unit (halalas for SAR)
        $amountInHalalas = (int) round($amount * 100);

        $payload = [
            'amount'      => $amountInHalalas,
            'currency'    => $currency,
            'description' => "Wallet Top-Up – User: {$normalizedUserName} | ID: {$userId}",
            'expired_at'  => $expiresAt,
            'metadata'    => array_merge([
                'user_id'    => $userId,
                'user_email' => $userEmail,
                'user_name'  => $userName,
                'type'       => 'wallet_recharge',
            ], $metadata),
        ];

        $response = $this->makeRequest('POST', '/invoices', $payload);

        return [
            'invoice_id'  => $response['id'] ?? null,
            'payment_url' => $response['url'] ?? null,
            'status'      => $response['status'] ?? null,
            'amount'      => $amount,
            'currency'    => $currency,
        ];
    }

    /**
     * Verify webhook secret_token from Moyasar
     *
     * Moyasar sends the secret_token as plaintext inside the webhook body.
     * We compare it against the configured MOYASAR_WEBHOOK_SECRET.
     *
     * @param array $payload Decoded webhook body
     * @return bool
     */
    public function verifyWebhookSignature(array $payload): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('Moyasar webhook secret not configured — skipping verification.');
            return false;
        }

        $receivedToken = $payload['secret_token'] ?? '';

        return hash_equals($this->webhookSecret, $receivedToken);
    }

    /**
     * Retrieve invoice details from Moyasar
     *
     * @param string $invoiceId Moyasar invoice ID
     * @return array Invoice data
     * @throws Exception
     */
    public function getInvoice(string $invoiceId): array
    {
        return $this->makeRequest('GET', '/invoices/' . $invoiceId);
    }

    /**
     * Check if invoice is paid
     *
     * @param array $invoice Invoice data from Moyasar
     * @return bool
     */
    public function isInvoicePaid(array $invoice): bool
    {
        return isset($invoice['status']) && $invoice['status'] === 'paid';
    }

    /**
     * Refund a payment via Moyasar
     *
     * @param string $paymentId Moyasar payment ID
     * @param float  $amount    Amount to refund in SAR (null = full refund)
     * @return array Refund response
     * @throws Exception
     */
    public function refundPayment(string $paymentId, ?float $amount = null): array
    {
        $data = [];
        if ($amount !== null) {
            $data['amount'] = (int) round($amount * 100);
        }

        return $this->makeRequest('POST', '/payments/' . $paymentId . '/refunds', $data);
    }

    /**
     * Make HTTP request to Moyasar API
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint (e.g., '/invoices')
     * @param array $data Request payload
     * @return array Response data
     * @throws Exception
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->{strtolower($method)}($this->baseUrl . $endpoint, $data);

            if (!$response->successful()) {
                Log::error('Moyasar API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Moyasar API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Moyasar Request Exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
