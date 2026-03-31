<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Models\BalanceTransaction;
use App\Models\PaymentTransaction;
use App\Services\MoyasarService;
use App\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Wallet Controller
 *
 * Handles wallet balance viewing, recharge requests, and transaction history
 */
class WalletController extends Controller
{
    protected MoyasarService $moyasarService;
    protected WalletService  $walletService;

    public function __construct(MoyasarService $moyasarService, WalletService $walletService)
    {
        $this->moyasarService = $moyasarService;
        $this->walletService  = $walletService;
    }

    /**
     * Get user wallet balance and recent transactions
     *
     * GET /api/v1/user/wallet
     */
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();

            // Get recent transactions (last 10)
            $transactions = BalanceTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return Response::success(
                [__('Wallet data fetched successfully')],
                [
                    'balance'             => (float) round($user->balance, 2),
                    'currency'            => get_default_currency_code(),
                    'recent_transactions' => $transactions,
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Wallet Index Error', [
                'user_id' => Auth::guard('api')->id(),
                'error' => $e->getMessage(),
            ]);

            return Response::error(
                [__('Failed to fetch wallet data')],
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Request wallet recharge via Moyasar invoice
     *
     * POST /api/v1/user/wallet/recharge
     *
     * Body: { "amount": 500 }
     */
    public function recharge(Request $request)
    {
        $minAmount = config('moyasar.recharge.min_amount', 10);
        $maxAmount = config('moyasar.recharge.max_amount', 10000);

        $validator = Validator::make($request->all(), [
            'amount' => "required|numeric|min:{$minAmount}|max:{$maxAmount}",
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $user   = Auth::guard('api')->user();
        $amount = (float) $request->amount;

        try {
            // Generate idempotency key scoped to user + unique request
            $idempotencyKey = 'topup-' . $user->id . '-' . Str::uuid();

            // Check for a pending payment that already exists (re-entrant safety)
            $existingPending = PaymentTransaction::where('user_id', $user->id)
                ->pending()
                ->where('amount', $amount)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->first();

            if ($existingPending && $existingPending->metadata) {
                $meta = $existingPending->metadata;
                if (!empty($meta['payment_url'])) {
                    return Response::success(
                        [__('Existing invoice found. Please complete payment.')],
                        [
                            'invoice_id'  => $existingPending->invoice_id,
                            'payment_url' => $meta['payment_url'],
                            'amount'      => number_format($amount, 2),
                            'currency'    => config('moyasar.invoice.currency', 'SAR'),
                        ],
                        200
                    );
                }
            }

            // Create Moyasar invoice
            $invoice = $this->moyasarService->createInvoice(
                amount: $amount,
                userId: $user->id,
                userEmail: $user->email,
                userName: $user->firstname . ' ' . $user->lastname,
                metadata: ['idempotency_key' => $idempotencyKey],
            );

            // Record pending payment transaction
            PaymentTransaction::create([
                'user_id'         => $user->id,
                'invoice_id'      => $invoice['invoice_id'],
                'amount'          => $amount,
                'status'          => PaymentTransaction::STATUS_PENDING,
                'provider'        => 'moyasar',
                'idempotency_key' => $idempotencyKey,
                'metadata'        => [
                    'payment_url' => $invoice['payment_url'],
                    'currency'    => $invoice['currency'],
                ],
            ]);

            return Response::success(
                [__('Invoice created successfully. Please complete payment.')],
                [
                    'invoice_id'  => $invoice['invoice_id'],
                    'payment_url' => $invoice['payment_url'],
                    'amount'      => number_format($amount, 2),
                    'currency'    => $invoice['currency'] ?? config('moyasar.invoice.currency', 'SAR'),
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Wallet Recharge Error', [
                'user_id' => $user->id,
                'amount'  => $amount,
                'error'   => $e->getMessage(),
            ]);

            return Response::error(
                [__('Failed to create payment invoice. Please try again.')],
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get all wallet transactions with pagination
     *
     * GET /api/v1/user/wallet/transactions
     */
    public function transactions(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $perPage = $request->get('per_page', 15);

            $transactions = BalanceTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return Response::success(
                [__('Transactions fetched successfully')],
                [
                    'transactions' => $transactions->items(),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'total' => $transactions->total(),
                        'per_page' => $transactions->perPage(),
                        'last_page' => $transactions->lastPage(),
                    ],
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Wallet Transactions Error', [
                'user_id' => Auth::guard('api')->id(),
                'error' => $e->getMessage(),
            ]);

            return Response::error(
                [__('Failed to fetch transactions')],
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
