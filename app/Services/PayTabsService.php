<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTabsService
{
    protected $serverKey;
    protected $profileId;
    protected $baseUrl;

    public function __construct()
    {
        $this->serverKey = config('services.paytabs.server_key');
        $this->profileId = config('services.paytabs.profile_id');
        $this->baseUrl = config('services.paytabs.base_url', 'https://secure.paytabs.sa');
    }

    /**
     * Create a payment page for balance recharge
     */
    public function createPaymentPage(array $data)
    {
        $payload = [
            'profile_id' => $this->profileId,
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'cart_id' => $data['order_id'],
            'cart_description' => $data['description'],
            'cart_currency' => $data['currency'],
            'cart_amount' => $data['amount'],
            'callback' => $data['callback_url'],
            'return' => $data['return_url'],
            'customer_details' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone'] ?? '',
                'street1' => '',
                'city' => '',
                'state' => '',
                'country' => 'SA',
                'zip' => '',
            ],
            'hide_shipping' => true,
            'user_defined' => [
                'udf1' => $data['user_id'] ?? '',
                'udf2' => 'balance_recharge',
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payment/request', $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['redirect_url'])) {
                return [
                    'success' => true,
                    'redirect_url' => $responseData['redirect_url'],
                    'tran_ref' => $responseData['tran_ref'] ?? null,
                ];
            }

            Log::error('PayTabs payment page creation failed', [
                'response' => $responseData,
                'payload' => $payload,
            ]);

            throw new Exception($responseData['message'] ?? 'Failed to create payment page');
        } catch (Exception $e) {
            Log::error('PayTabs API error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Handle callback from PayTabs
     */
    public function handleCallback(array $data)
    {
        $tranRef = $data['tran_ref'] ?? null;

        if (!$tranRef) {
            return [
                'success' => false,
                'message' => 'Transaction reference not provided',
            ];
        }

        // Verify the transaction
        $verification = $this->verifyPayment($tranRef);

        if (!$verification['success']) {
            return $verification;
        }

        $transactionData = $verification['data'];
        $respStatus = $transactionData['payment_result']['response_status'] ?? '';

        // A = Authorized/Successful
        if (strtoupper($respStatus) === 'A') {
            return [
                'success' => true,
                'order_id' => $transactionData['cart_id'] ?? '',
                'amount' => $transactionData['cart_amount'] ?? 0,
                'tran_ref' => $tranRef,
                'data' => $transactionData,
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment was not successful',
            'response_status' => $respStatus,
            'data' => $transactionData,
        ];
    }

    /**
     * Verify a payment transaction
     */
    public function verifyPayment($tranRef)
    {
        $payload = [
            'profile_id' => $this->profileId,
            'tran_ref' => $tranRef,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payment/query', $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            Log::error('PayTabs payment verification failed', [
                'response' => $responseData,
                'tran_ref' => $tranRef,
            ]);

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Failed to verify payment',
            ];
        } catch (Exception $e) {
            Log::error('PayTabs verification API error', [
                'error' => $e->getMessage(),
                'tran_ref' => $tranRef,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a refund
     */
    public function refund($tranRef, $amount, $description = 'Refund')
    {
        $payload = [
            'profile_id' => $this->profileId,
            'tran_type' => 'refund',
            'tran_class' => 'ecom',
            'cart_id' => 'REFUND-' . time(),
            'cart_currency' => get_default_currency_code(),
            'cart_amount' => $amount,
            'cart_description' => $description,
            'tran_ref' => $tranRef,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payment/request', $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                $respStatus = $responseData['payment_result']['response_status'] ?? '';

                if (strtoupper($respStatus) === 'A') {
                    return [
                        'success' => true,
                        'data' => $responseData,
                    ];
                }
            }

            Log::error('PayTabs refund failed', [
                'response' => $responseData,
                'tran_ref' => $tranRef,
            ]);

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Failed to process refund',
            ];
        } catch (Exception $e) {
            Log::error('PayTabs refund API error', [
                'error' => $e->getMessage(),
                'tran_ref' => $tranRef,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
