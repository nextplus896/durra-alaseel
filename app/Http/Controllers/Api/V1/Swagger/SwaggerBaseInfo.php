<?php

namespace App\Http\Controllers\Api\V1\Swagger;

/**
 * @OA\Info(
 *     title="Dorra Alaseel – Car Rental API",
 *     version="1.0.0",
 *     description="RESTful JSON API for the Dorra Alaseel car rental platform. Response envelope: message.success[] or message.error[], data, type=success|error.",
 *     @OA\Contact(name="Support", email="support@dorraalaseel.com"),
 *     @OA\License(name="Proprietary")
 * )
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="API Server")
 * @OA\SecurityScheme(securityScheme="sanctumAuth", type="http", scheme="bearer", bearerFormat="JWT")
 * @OA\Tag(name="Authentication", description="Register, login, logout, OTP")
 * @OA\Tag(name="Users",          description="User profile management")
 * @OA\Tag(name="Cars",           description="Public car listing and search")
 * @OA\Tag(name="Bookings",       description="Car booking lifecycle")
 * @OA\Tag(name="Wallet",         description="Wallet and balance management")
 * @OA\Tag(name="Payments",       description="Payment operations and refunds")
 */
abstract class SwaggerBaseInfo
{
    /**
     * @OA\Schema(
     *     schema="SuccessResponse",
     *     @OA\Property(property="message", type="object",
     *         @OA\Property(property="success", type="array", @OA\Items(type="string", example="Operation completed successfully."))
     *     ),
     *     @OA\Property(property="data", type="object", nullable=true),
     *     @OA\Property(property="type", type="string", example="success")
     * )
     */
    public static function schemaSuccess(): void {}

    /**
     * @OA\Schema(
     *     schema="ErrorResponse",
     *     @OA\Property(property="message", type="object",
     *         @OA\Property(property="error", type="array", @OA\Items(type="string", example="An error occurred."))
     *     ),
     *     @OA\Property(property="data", type="array", @OA\Items()),
     *     @OA\Property(property="type", type="string", example="error")
     * )
     */
    public static function schemaError(): void {}

    /**
     * @OA\Schema(
     *     schema="ValidationErrorResponse",
     *     @OA\Property(property="message", type="object",
     *         @OA\Property(property="error", type="array", @OA\Items(type="string", example="The credentials field is required."))
     *     ),
     *     @OA\Property(property="data", type="array", @OA\Items()),
     *     @OA\Property(property="type", type="string", example="error")
     * )
     */
    public static function schemaValidationError(): void {}

    /**
     * @OA\Schema(
     *     schema="UnauthenticatedResponse",
     *     @OA\Property(property="message", type="object",
     *         @OA\Property(property="error", type="array", @OA\Items(type="string", example="Unauthenticated."))
     *     ),
     *     @OA\Property(property="data", type="array", @OA\Items()),
     *     @OA\Property(property="type", type="string", example="error")
     * )
     */
    public static function schemaUnauthenticated(): void {}

    /**
     * @OA\Schema(
     *     schema="PaginationMeta",
     *     @OA\Property(property="current_page", type="integer", example=1),
     *     @OA\Property(property="from",         type="integer", nullable=true, example=1),
     *     @OA\Property(property="last_page",    type="integer", example=10),
     *     @OA\Property(property="per_page",     type="integer", example=15),
     *     @OA\Property(property="to",           type="integer", nullable=true, example=15),
     *     @OA\Property(property="total",        type="integer", example=148)
     * )
     */
    public static function schemaPaginationMeta(): void {}

    /**
     * @OA\Schema(
     *     schema="PaginationLinks",
     *     @OA\Property(property="first", type="string", nullable=true, example="https://api.example.com/api/v1/cars?page=1"),
     *     @OA\Property(property="last",  type="string", nullable=true, example="https://api.example.com/api/v1/cars?page=10"),
     *     @OA\Property(property="prev",  type="string", nullable=true, example=null),
     *     @OA\Property(property="next",  type="string", nullable=true, example="https://api.example.com/api/v1/cars?page=2")
     * )
     */
    public static function schemaPaginationLinks(): void {}

    /**
     * @OA\Schema(
     *     schema="UserProfile",
     *     @OA\Property(property="id",                  type="integer", example=42),
     *     @OA\Property(property="firstname",           type="string",  example="Ahmad"),
     *     @OA\Property(property="lastname",            type="string",  example="Al-Rashidi"),
     *     @OA\Property(property="fullname",            type="string",  example="Ahmad Al-Rashidi"),
     *     @OA\Property(property="username",            type="string",  example="ahmad_alrashidi"),
     *     @OA\Property(property="email",               type="string",  format="email", example="ahmad@example.com"),
     *     @OA\Property(property="mobile_code",         type="string",  example="+966"),
     *     @OA\Property(property="mobile",              type="string",  example="501234567"),
     *     @OA\Property(property="full_mobile",         type="string",  example="+966501234567"),
     *     @OA\Property(property="email_verified",      type="boolean", example=false),
     *     @OA\Property(property="kyc_verified",        type="integer", example=0, description="0=Default, 1=Approved, 2=Pending, 3=Rejected"),
     *     @OA\Property(property="two_factor_verified", type="boolean", example=false),
     *     @OA\Property(property="two_factor_status",   type="integer", example=0),
     *     @OA\Property(property="two_factor_secret",   type="string",  nullable=true, example=null)
     * )
     */
    public static function schemaUserProfile(): void {}

    /**
     * @OA\Schema(
     *     schema="AuthTokenData",
     *     @OA\Property(property="token",         type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *     @OA\Property(property="user_info",     ref="#/components/schemas/UserProfile"),
     *     @OA\Property(property="authorization", type="object",
     *         @OA\Property(property="status", type="boolean", example=false, description="True when email verification is required before the account can be used."),
     *         @OA\Property(property="token",  type="string",  example="",    description="Short-lived token used for the email-verification step; empty when status is false.")
     *     )
     * )
     */
    public static function schemaAuthTokenData(): void {}

    /**
     * @OA\Schema(
     *     schema="CarResource",
     *     @OA\Property(property="id",                 type="integer", example=101),
     *     @OA\Property(property="name",               type="string",  example="Toyota Camry 2024"),
     *     @OA\Property(property="car_type",           type="string",  example="Sedan"),
     *     @OA\Property(property="daily_price",        type="number",  format="float", example=150.00),
     *     @OA\Property(property="weekly_price",       type="number",  format="float", example=900.00),
     *     @OA\Property(property="monthly_price",      type="number",  format="float", example=3200.00),
     *     @OA\Property(property="currency",           type="string",  example="SAR"),
     *     @OA\Property(property="branch_id",          type="integer", example=3),
     *     @OA\Property(property="branch_name",        type="string",  example="Riyadh – King Fahd Branch"),
     *     @OA\Property(property="vendor_id",          type="integer", example=7),
     *     @OA\Property(property="seats",              type="integer", example=5),
     *     @OA\Property(property="transmission",       type="string",  enum={"automatic","manual"}, example="automatic"),
     *     @OA\Property(property="fuel_type",          type="string",  enum={"petrol","diesel","electric","hybrid"}, example="petrol"),
     *     @OA\Property(property="delivery_available", type="boolean", example=true),
     *     @OA\Property(property="delivery_price",     type="number",  format="float", nullable=true, example=50.00),
     *     @OA\Property(property="images",             type="array",   @OA\Items(type="string", format="uri")),
     *     @OA\Property(property="status",             type="string",  enum={"active","inactive"}, example="active")
     * )
     */
    public static function schemaCarResource(): void {}

    /**
     * @OA\Schema(
     *     schema="CarBookingResource",
     *     @OA\Property(property="id",           type="integer", example=201),
     *     @OA\Property(property="booking_id",   type="string",  example="5G7H2K"),
     *     @OA\Property(property="car",          ref="#/components/schemas/CarResource"),
     *     @OA\Property(property="pickup_date",  type="string",  format="date",  example="2025-06-01"),
     *     @OA\Property(property="return_date",  type="string",  format="date",  example="2025-06-08"),
     *     @OA\Property(property="rental_days",  type="integer", example=7),
     *     @OA\Property(property="rental_fees",  type="number",  format="float", example=1050.00),
     *     @OA\Property(property="delivery_fee", type="number",  format="float", example=50.00),
     *     @OA\Property(property="tax_amount",   type="number",  format="float", example=165.00),
     *     @OA\Property(property="total_amount", type="number",  format="float", example=1265.00),
     *     @OA\Property(property="currency",     type="string",  example="SAR"),
     *     @OA\Property(property="status",       type="string",  enum={"pending","booked","ongoing","completed","rejected","cancelled"}, example="booked"),
     *     @OA\Property(property="created_at",   type="string",  format="date-time", example="2025-05-20T09:00:00Z")
     * )
     */
    public static function schemaCarBookingResource(): void {}

    /**
     * @OA\Schema(
     *     schema="BalanceTransactionResource",
     *     @OA\Property(property="id",             type="integer", example=501),
     *     @OA\Property(property="type",           type="string",  enum={"recharge","deduction","refund","adjustment"}, example="recharge"),
     *     @OA\Property(property="amount",         type="number",  format="float", example=200.00),
     *     @OA\Property(property="balance_before", type="number",  format="float", example=50.00),
     *     @OA\Property(property="balance_after",  type="number",  format="float", example=250.00),
     *     @OA\Property(property="description",    type="string",  nullable=true,  example="Wallet top-up via PayTabs"),
     *     @OA\Property(property="created_at",     type="string",  format="date-time", example="2025-05-18T14:22:00Z")
     * )
     */
    public static function schemaBalanceTransaction(): void {}

    /**
     * @OA\Schema(
     *     schema="WalletResource",
     *     @OA\Property(property="balance",      type="number", format="float", example=250.00),
     *     @OA\Property(property="currency",     type="string", example="SAR"),
     *     @OA\Property(property="last_updated", type="string", format="date-time", example="2025-05-20T10:00:00Z")
     * )
     */
    public static function schemaWalletResource(): void {}

    /**
     * @OA\Schema(
     *     schema="PaymentGatewayResource",
     *     @OA\Property(property="id",        type="integer", example=1),
     *     @OA\Property(property="name",      type="string",  example="PayTabs"),
     *     @OA\Property(property="alias",     type="string",  example="paytabs"),
     *     @OA\Property(property="image",     type="string",  format="uri", nullable=true),
     *     @OA\Property(property="min_limit", type="number",  format="float", example=10.00),
     *     @OA\Property(property="max_limit", type="number",  format="float", example=50000.00),
     *     @OA\Property(property="currency",  type="string",  example="SAR"),
     *     @OA\Property(property="type",      type="string",  enum={"automatic","manual"}, example="automatic")
     * )
     */
    public static function schemaPaymentGateway(): void {}
}
