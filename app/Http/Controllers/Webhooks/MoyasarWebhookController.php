<?php

namespace App\Http\Controllers\Webhooks;

use Exception;
use App\Models\User;
use App\Models\PaymentTransaction;
use App\Services\MoyasarService;
use App\Services\WalletService;
use App\DTO\WalletTransactionDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Moyasar Webhook Handler
 *
 * Receives payment notifications from Moyasar and updates wallet balances
 *
 * IMPORTANT: This endpoint must be PUBLIC (no authentication)
 * Moyasar servers call this, not your users
 */
class MoyasarWebhookController extends Controller
{
    protected MoyasarService $moyasarService;
    protected WalletService  $walletService;
    protected \Illuminate\Log\Logger $log;

    public function __construct(MoyasarService $moyasarService, WalletService $walletService)
    {
        $this->moyasarService = $moyasarService;
        $this->walletService  = $walletService;
        $this->log            = Log::channel('moyasar');
    }

    /**
     * Handle incoming webhook from Moyasar
     *
     * POST /api/webhooks/moyasar
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // ── [DEBUG] Log full incoming request ────────────────────
        $this->log->debug('Moyasar Webhook: Raw payload', [
            'headers'    => $request->headers->all(),
            'ip'         => $request->ip(),
            'payload'    => $payload,
        ]);

        $this->log->info('Moyasar Webhook: Received', [
            'event_id'   => $payload['id'] ?? null,
            'type'       => $payload['type'] ?? null,
            'live'       => $payload['live'] ?? null,
            'created_at' => $payload['created_at'] ?? null,
        ]);

        // ── Step 1: Verify secret_token from request body ────────
        $receivedToken = $payload['secret_token'] ?? null;

        $this->log->debug('Moyasar Webhook: Secret token check', [
            'received_token_present' => !empty($receivedToken),
            'received_token_length'  => strlen((string) $receivedToken),
            'expected_token_length'  => strlen((string) config('moyasar.webhook_secret')),
        ]);

        if (!$this->moyasarService->verifyWebhookSignature($payload)) {
            $this->log->warning('Moyasar Webhook: Invalid secret_token', [
                'event_id' => $payload['id'] ?? null,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid secret_token'], 403);
        }

        $this->log->info('Moyasar Webhook: Secret token verified OK');

        // ── Step 2: Only handle payment_paid events ───────────────
        $eventType = $payload['type'] ?? '';

        if ($eventType !== 'payment_paid') {
            $this->log->info('Moyasar Webhook: Ignored event type', ['type' => $eventType]);
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // ── Step 3: Extract payment data ─────────────────────────
        $data      = $payload['data'] ?? [];
        $invoiceId = $data['invoice_id'] ?? null;
        $paymentId = $data['id'] ?? null;
        $status    = $data['status'] ?? null;
        $amount    = $data['amount'] ?? null;      // in halalas
        $currency  = $data['currency'] ?? null;

        $this->log->info('Moyasar Webhook: Payment data extracted', [
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'status'     => $status,
            'amount'     => $amount,
            'currency'   => $currency,
            'source_type' => $data['source']['type'] ?? null,
        ]);

        if (!$invoiceId) {
            $this->log->warning('Moyasar Webhook: Missing data.invoice_id in payload', [
                'data_keys' => array_keys($data),
            ]);
            return response()->json(['error' => 'Missing invoice_id'], 400);
        }

        // ── Step 4: Find matching payment transaction ────────────
        $paymentTx = PaymentTransaction::where('invoice_id', $invoiceId)->first();

        $this->log->debug('Moyasar Webhook: DB lookup result', [
            'invoice_id' => $invoiceId,
            'found'      => $paymentTx !== null,
            'tx_id'      => $paymentTx?->id,
            'tx_status'  => $paymentTx?->status,
            'tx_user_id' => $paymentTx?->user_id,
            'tx_amount'  => $paymentTx?->amount,
        ]);

        if (!$paymentTx) {
            $this->log->warning('Moyasar Webhook: No matching PaymentTransaction', [
                'invoice_id' => $invoiceId,
            ]);
            return response()->json(['message' => 'No matching transaction found'], 200);
        }

        // ── Step 5: Idempotency — already processed ─────────────
        if ($paymentTx->isPaid()) {
            $this->log->info('Moyasar Webhook: Invoice already paid — idempotent skip', [
                'invoice_id' => $invoiceId,
                'tx_id'      => $paymentTx->id,
            ]);
            return response()->json(['message' => 'Already processed'], 200);
        }

        // ── Step 6: Guard — data.status must be 'paid' ───────────
        if ($status !== 'paid') {
            $paymentTx->markFailed();
            $this->log->warning('Moyasar Webhook: Payment not paid — marked failed', [
                'invoice_id' => $invoiceId,
                'status'     => $status,
                'tx_id'      => $paymentTx->id,
            ]);
            return response()->json(['message' => 'Status updated'], 200);
        }

        // ── Step 7: Credit wallet ────────────────────────────────
        $this->log->info('Moyasar Webhook: Starting wallet credit', [
            'invoice_id' => $invoiceId,
            'user_id'    => $paymentTx->user_id,
            'amount_sar' => $paymentTx->amount,
        ]);

        try {
            $user   = User::findOrFail($paymentTx->user_id);
            $amount = (float) $paymentTx->amount;

            $this->log->debug('Moyasar Webhook: User found', [
                'user_id'          => $user->id,
                'balance_before'   => $user->balance,
                'deposit_amount'   => $amount,
                'idempotency_key'  => 'webhook-' . $invoiceId,
            ]);

            $dto = WalletTransactionDTO::forDeposit(
                amount: $amount,
                description: __('Wallet recharge via Moyasar'),
                referenceType: 'App\\Models\\PaymentTransaction',
                referenceId: $paymentTx->id,
                idempotencyKey: 'webhook-' . $invoiceId,
                moyasarInvoiceId: $invoiceId,
            );

            DB::transaction(function () use ($user, $amount, $dto, $paymentTx, $paymentId) {
                $this->walletService->deposit($user, $amount, $dto);
                $paymentTx->markPaid($paymentId ?? '');
            });

            $user->refresh();

            $this->log->info('Moyasar Webhook: Wallet credited successfully', [
                'user_id'       => $user->id,
                'deposited'     => $amount,
                'balance_after' => $user->balance,
                'invoice_id'    => $invoiceId,
                'payment_id'    => $paymentId,
            ]);

            return response()->json(['message' => 'Payment processed successfully'], 200);
        } catch (Exception $e) {
            $this->log->error('Moyasar Webhook: Processing failed', [
                'invoice_id' => $invoiceId,
                'user_id'    => $paymentTx->user_id ?? null,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal processing error'], 500);
        }
    }
}
