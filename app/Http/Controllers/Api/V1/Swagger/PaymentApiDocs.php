<?php

namespace App\Http\Controllers\Api\V1\Swagger;

abstract class PaymentApiDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/payment-gateway/additional-fields",
     *     tags={"Payments"},
     *     summary="List available payment gateways with their additional fields",
     *     operationId="paymentGatewayFields",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="purpose", in="query", required=false, description="Gateway purpose context", @OA\Schema(type="string", enum={"recharge","booking"}, example="booking")),
     *     @OA\Response(
     *         response=200,
     *         description="Payment gateways.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Payment gateways retrieved."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/PaymentGatewayResource")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function paymentGatewayFields(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/payments/{paymentId}/refund",
     *     tags={"Payments"},
     *     summary="Request a refund for a specific payment",
     *     operationId="paymentRefund",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="paymentId", in="path", required=true, description="Payment ID", @OA\Schema(type="integer", example=301)),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", nullable=true, example="Booking cancelled by vendor")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Refund initiated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Refund processed. Amount will be credited within 3-5 business days."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="refund_id",      type="integer", example=401),
     *                 @OA\Property(property="amount",         type="number",  format="float", example=1265.00),
     *                 @OA\Property(property="currency",       type="string",  example="SAR"),
     *                 @OA\Property(property="status",         type="string",  enum={"pending","completed","failed"}, example="pending"),
     *                 @OA\Property(property="created_at",     type="string",  format="date-time")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Payment not found.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Refund not eligible.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function paymentRefund(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/transaction/log",
     *     tags={"Payments"},
     *     summary="Full payment transaction log (paginated)",
     *     operationId="transactionLog",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="page",     in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="status",   in="query", required=false, description="Filter by status", @OA\Schema(type="string", enum={"pending","completed","failed","refunded"})),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction log.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Transaction log retrieved."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id",              type="integer", example=301),
     *                         @OA\Property(property="type",            type="string",  enum={"booking_payment","wallet_recharge","refund"}, example="booking_payment"),
     *                         @OA\Property(property="amount",          type="number",  format="float", example=1265.00),
     *                         @OA\Property(property="currency",        type="string",  example="SAR"),
     *                         @OA\Property(property="gateway",         type="string",  nullable=true, example="PayTabs"),
     *                         @OA\Property(property="status",          type="string",  enum={"pending","completed","failed","refunded"}, example="completed"),
     *                         @OA\Property(property="reference",       type="string",  nullable=true, example="PT-REF-20250520"),
     *                         @OA\Property(property="created_at",      type="string",  format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="meta",  ref="#/components/schemas/PaginationMeta"),
     *                 @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function transactionLog(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/wallet/recharge",
     *     tags={"Payments"},
     *     summary="Initiate a wallet recharge via payment gateway callback",
     *     operationId="walletRechargeCallback",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_ref","status"},
     *             @OA\Property(property="transaction_ref", type="string",  example="TX-20250520-7Y2KL"),
     *             @OA\Property(property="status",          type="string",  enum={"success","failed"}, example="success"),
     *             @OA\Property(property="gateway_ref",     type="string",  nullable=true, example="PT-CALLBACK-9832")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wallet recharged.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Wallet recharged successfully."),
     *             @OA\Property(property="data",    ref="#/components/schemas/WalletResource"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Transaction invalid or already processed.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function walletRechargeCallback(): void {}
}
