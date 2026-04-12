<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\BalanceTransaction;
use App\Models\UserWallet;
use App\Http\Controllers\Controller;
use App\Models\Admin\BasicSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PayTabsService;

class BalanceController extends Controller
{
    /**
     * Get user's current balance
     */
    public function getBalance()
    {
        $user = Auth::guard('api')->user();

        return Response::success(
            [__('Balance fetched successfully')],
            [
                'balance'  => (float) round($user->balance, 2),
                'currency' => get_default_currency_code(),
            ],
            200
        );
    }

    /**
     * Get user's balance transaction history
     */
    public function getTransactionHistory(Request $request)
    {
        $user = Auth::guard('api')->user();

        $transactions = BalanceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return Response::success(
            [__('Transaction history fetched successfully')],
            [
                'transactions' => $transactions,
                'currency' => get_default_currency_code(),
            ],
            200
        );
    }

    /**
     * Initialize balance recharge with PayTabs
     */
    public function initiateRecharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $user = Auth::guard('api')->user();
        $amount = $request->amount;

        try {
            $payTabsService = new PayTabsService();

            $paymentData = $payTabsService->createPaymentPage([
                'amount' => $amount,
                'currency' => get_default_currency_code(),
                'customer_name' => $user->firstname . ' ' . $user->lastname,
                'customer_email' => $user->email,
                'customer_phone' => $user->mobile ?? '',
                'order_id' => 'RECHARGE-' . $user->id . '-' . time(),
                'description' => __('Balance Recharge', [], 'ar'),
                'callback_url' => route('api.user.balance.recharge.callback'),
                'return_url' => route('api.user.balance.recharge.return'),
                'user_id' => $user->id,
            ]);

            if (isset($paymentData['redirect_url'])) {
                return Response::success(
                    [__('Payment page created successfully')],
                    [
                        'redirect_url' => $paymentData['redirect_url'],
                        'transaction_ref' => $paymentData['tran_ref'] ?? null,
                    ],
                    200
                );
            }

            return Response::error([__('Failed to create payment page')], [], 500);
        } catch (Exception $e) {
            return Response::error([$e->getMessage()], [], 500);
        }
    }

    /**
     * Handle PayTabs callback (server-to-server)
     */
    public function rechargeCallback(Request $request)
    {
        try {
            $payTabsService = new PayTabsService();
            $result = $payTabsService->handleCallback($request->all());

            if ($result['success']) {
                // Extract user_id from cart_id or order reference
                $orderId = $result['order_id'] ?? '';
                preg_match('/RECHARGE-(\d+)-/', $orderId, $matches);
                $userId = $matches[1] ?? null;

                if ($userId) {
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $amount = $result['amount'];

                        // Source of truth: update user_wallets balance
                        $wallet = UserWallet::where('user_id', $user->id)
                            ->whereHas('currency', fn($q) => $q->where('default', true))
                            ->first();

                        $balanceBefore = $wallet ? (float) $wallet->balance : 0;
                        $balanceAfter  = $balanceBefore + $amount;

                        DB::beginTransaction();
                        try {
                            if ($wallet) {
                                $wallet->update(['balance' => $balanceAfter]);
                            }

                            // Create balance transaction record
                            BalanceTransaction::create([
                                'user_id'        => $user->id,
                                'type'           => 'recharge',
                                'amount'         => $amount,
                                'balance_before' => $balanceBefore,
                                'balance_after'  => $balanceAfter,
                                'description'    => __('Balance recharge via PayTabs', [], 'ar'),
                                'payment_gateway' => 'paytabs',
                                'reference'      => $result['tran_ref'] ?? null,
                            ]);

                            DB::commit();
                            return response()->json(['status' => 'success'], 200);
                        } catch (Exception $e) {
                            DB::rollBack();
                            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                        }
                    }
                }
            }

            return response()->json(['status' => 'failed'], 400);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle PayTabs return (user redirect)
     */
    public function rechargeReturn(Request $request)
    {
        // This endpoint is for user redirect after payment
        // The actual balance update is handled in the callback
        $respStatus = $request->respStatus ?? '';

        if (strtolower($respStatus) === 'a') {
            return Response::success([__('Payment completed successfully. Your balance has been updated.')], [], 200);
        } else {
            return Response::error([__('Payment was not completed. Please try again.')], [], 400);
        }
    }

    /**
     * Get current tax settings
     */
    public function getTaxSettings()
    {
        $basicSettings = BasicSettings::first();
        $isActive = $basicSettings && $basicSettings->tax_status;

        return Response::success(
            [__('Tax settings fetched successfully')],
            [
                'tax_percentage' => $basicSettings ? (float) $basicSettings->tax_percentage : 15.00,
                'is_active'      => $isActive,
            ],
            200
        );
    }

    /**
     * Calculate total with tax
     */
    public function calculateWithTax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $amount = $request->amount;
        $basicSettings = BasicSettings::first();
        $taxPercentage = $basicSettings ? (float) $basicSettings->tax_percentage : 15.00;

        $taxAmount = ($amount * $taxPercentage) / 100;
        $totalAmount = $amount + $taxAmount;

        return Response::success(
            [__('Calculation completed')],
            [
                'subtotal' => number_format($amount, 2),
                'tax_percentage' => $taxPercentage,
                'tax_amount' => number_format($taxAmount, 2),
                'total' => number_format($totalAmount, 2),
                'currency' => get_default_currency_code(),
            ],
            200
        );
    }

    /**
     * Check if user has sufficient balance for a booking
     */
    public function checkBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'include_tax' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $user = Auth::guard('api')->user();
        $amount = $request->amount;

        // Include tax calculation if requested
        if ($request->include_tax) {
            $basicSettings = BasicSettings::first();
            $taxPercentage = $basicSettings ? (float) $basicSettings->tax_percentage : 15.00;
            $taxAmount = ($amount * $taxPercentage) / 100;
            $amount += $taxAmount;
        }

        $hasSufficientBalance = $user->balance >= $amount;

        return Response::success(
            [__('Balance check completed')],
            [
                'current_balance'       => (float) round($user->balance, 2),
                'required_amount'       => (float) round($amount, 2),
                'has_sufficient_balance' => $hasSufficientBalance,
                'shortfall'             => $hasSufficientBalance ? 0.0 : (float) round($amount - $user->balance, 2),
                'currency'              => get_default_currency_code(),
            ],
            200
        );
    }
}
