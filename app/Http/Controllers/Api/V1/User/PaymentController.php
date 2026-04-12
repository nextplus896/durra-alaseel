<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Models\PaymentTransaction;
use App\Services\MoyasarService;
use App\Services\WalletService;
use App\DTO\WalletTransactionDTO;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected MoyasarService $moyasarService;
    protected WalletService  $walletService;

    public function __construct(MoyasarService $moyasarService, WalletService $walletService)
    {
        $this->moyasarService = $moyasarService;
        $this->walletService  = $walletService;
    }

    /**
     * Refund a Moyasar payment and reverse the wallet credit.
     *
     * POST /api/v1/user/payments/{paymentId}/refund
     */
    public function refund(Request $request, int $paymentId)
    {
        $user = Auth::guard('api')->user();

        $paymentTx = PaymentTransaction::where('id', $paymentId)
            ->where('user_id', $user->id)
            ->first();

        if (!$paymentTx) {
            return Response::error([__('Payment transaction not found.')], [], 404);
        }

        if (!$paymentTx->isPaid()) {
            return Response::error([__('Only paid transactions can be refunded.')], [], 422);
        }

        if ($paymentTx->isRefunded()) {
            return Response::error([__('This payment has already been refunded.')], [], 422);
        }

        $amount = (float) $paymentTx->amount;

        // Guard: user must have enough balance to reverse the credit
        if ((float) $user->balance < $amount) {
            return Response::error([__('Insufficient wallet balance to process refund.')], [], 422);
        }

        try {
            DB::transaction(function () use ($paymentTx, $user, $amount) {
                // Call Moyasar refund API
                if ($paymentTx->payment_id) {
                    $this->moyasarService->refundPayment($paymentTx->payment_id, $amount);
                }

                // Reverse wallet credit (withdraw the refunded amount)
                $dto = WalletTransactionDTO::forRefund(
                    amount: $amount,
                    description: __('Payment refund — Moyasar invoice :id', ['id' => $paymentTx->invoice_id], 'ar'),
                    referenceType: 'App\\Models\\PaymentTransaction',
                    referenceId: $paymentTx->id,
                    idempotencyKey: 'payment-refund-' . $paymentTx->id,
                );

                // For a payment refund, we WITHDRAW from the wallet (money goes back to card)
                $this->walletService->withdraw($user, $amount, new WalletTransactionDTO(
                    type: 'refund',
                    amount: $amount,
                    description: __('Payment refund reversal — Moyasar invoice :id', ['id' => $paymentTx->invoice_id], 'ar'),
                    referenceType: 'App\\Models\\PaymentTransaction',
                    referenceId: $paymentTx->id,
                    paymentMethod: 'moyasar',
                    idempotencyKey: 'payment-refund-' . $paymentTx->id,
                ));

                $paymentTx->markRefunded();
            });

            return Response::success(
                [__('Payment refunded successfully.')],
                [
                    'payment_id' => $paymentTx->id,
                    'amount'     => number_format($amount, 2),
                    'status'     => 'refunded',
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Payment Refund Error', [
                'payment_id' => $paymentId,
                'user_id'    => $user->id,
                'error'      => $e->getMessage(),
            ]);

            return Response::error(
                [__('Failed to process refund. Please try again.')],
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
