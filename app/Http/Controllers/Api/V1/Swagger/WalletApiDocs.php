<?php

namespace App\Http\Controllers\Api\V1\Swagger;

abstract class WalletApiDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/balance",
     *     tags={"Wallet"},
     *     summary="Get the authenticated user's current balance",
     *     operationId="userBalance",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Current balance.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Balance retrieved."),
     *             @OA\Property(property="data",    ref="#/components/schemas/WalletResource"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function userBalance(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/balance/history",
     *     tags={"Wallet"},
     *     summary="List balance transaction history (paginated)",
     *     operationId="balanceHistory",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="page",     in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="type",     in="query", required=false, description="Filter by transaction type", @OA\Schema(type="string", enum={"recharge","deduction","refund","adjustment"})),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction history.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="History retrieved."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data",  type="array",  @OA\Items(ref="#/components/schemas/BalanceTransactionResource")),
     *                 @OA\Property(property="meta",  ref="#/components/schemas/PaginationMeta"),
     *                 @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function balanceHistory(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/balance/recharge",
     *     tags={"Wallet"},
     *     summary="Top up the user's wallet balance",
     *     operationId="balanceRecharge",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount","payment_gateway_id"},
     *             @OA\Property(property="amount",              type="number",  format="float", minimum=10, example=200.00),
     *             @OA\Property(property="payment_gateway_id",  type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recharge initiated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success",  type="boolean", example=true),
     *             @OA\Property(property="message",  type="string",  example="Recharge initiated. Redirecting to payment gateway."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_url",        type="string", format="uri", example="https://payment.paytabs.com/pay/123"),
     *                 @OA\Property(property="transaction_ref",    type="string", example="TX-20250520-7Y2KL")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function balanceRecharge(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/balance/tax-settings",
     *     tags={"Wallet"},
     *     summary="Get the current tax settings for wallet operations",
     *     operationId="balanceTaxSettings",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tax settings.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Tax settings retrieved."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="vat_rate",        type="number",  format="float", example=15.0),
     *                 @OA\Property(property="vat_included",    type="boolean", example=false),
     *                 @OA\Property(property="currency",        type="string",  example="SAR")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function balanceTaxSettings(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/balance/calculate-tax",
     *     tags={"Wallet"},
     *     summary="Calculate the tax on a given amount",
     *     operationId="balanceCalculateTax",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=200.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tax calculation result.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Tax calculated."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="amount",         type="number", format="float", example=200.00),
     *                 @OA\Property(property="tax_rate",       type="number", format="float", example=15.0),
     *                 @OA\Property(property="tax_amount",     type="number", format="float", example=30.00),
     *                 @OA\Property(property="total",          type="number", format="float", example=230.00),
     *                 @OA\Property(property="currency",       type="string", example="SAR")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function balanceCalculateTax(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/balance/check",
     *     tags={"Wallet"},
     *     summary="Check if user's balance is sufficient for a given amount",
     *     operationId="balanceCheck",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=1265.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Balance check result.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Balance sufficient."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="sufficient",       type="boolean", example=true),
     *                 @OA\Property(property="current_balance",  type="number",  format="float", example=1500.00),
     *                 @OA\Property(property="required_amount",  type="number",  format="float", example=1265.00),
     *                 @OA\Property(property="deficit",          type="number",  format="float", nullable=true, example=null)
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function balanceCheck(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/wallet",
     *     tags={"Wallet"},
     *     summary="Get full wallet information",
     *     operationId="walletGet",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wallet information.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Wallet retrieved."),
     *             @OA\Property(property="data",    ref="#/components/schemas/WalletResource"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function walletGet(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/wallet/transactions",
     *     tags={"Wallet"},
     *     summary="List wallet transaction log (paginated)",
     *     operationId="walletTransactions",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="page",     in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Wallet transactions.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Transactions retrieved."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data",  type="array",  @OA\Items(ref="#/components/schemas/BalanceTransactionResource")),
     *                 @OA\Property(property="meta",  ref="#/components/schemas/PaginationMeta"),
     *                 @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function walletTransactions(): void {}
}
