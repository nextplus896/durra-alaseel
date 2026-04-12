<?php

namespace App\Http\Controllers\Api\V1\Swagger;

abstract class BookingApiDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/car-booking/history",
     *     tags={"Bookings"},
     *     summary="List authenticated user's booking history (paginated)",
     *     operationId="bookingHistory",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="page",     in="query", required=false, @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="status",   in="query", required=false, description="Filter by status", @OA\Schema(type="string", enum={"pending","booked","ongoing","completed","rejected","cancelled"})),
     *     @OA\Response(
     *         response=200,
     *         description="Booking history.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Booking history retrieved."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data",  type="array",  @OA\Items(ref="#/components/schemas/CarBookingResource")),
     *                 @OA\Property(property="meta",  ref="#/components/schemas/PaginationMeta"),
     *                 @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *             ),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingHistory(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/car-booking/temp/store",
     *     tags={"Bookings"},
     *     summary="Save a temporary (draft) booking",
     *     operationId="bookingTempStore",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"car_id","pickup_date","return_date"},
     *             @OA\Property(property="car_id",           type="integer", example=101),
     *             @OA\Property(property="pickup_date",      type="string",  format="date", example="2025-06-01"),
     *             @OA\Property(property="return_date",      type="string",  format="date", example="2025-06-08"),
     *             @OA\Property(property="delivery",         type="boolean", example=false),
     *             @OA\Property(property="delivery_address", type="string",  nullable=true, example="123 King Fahd Road, Riyadh"),
     *             @OA\Property(property="delivery_lat",     type="number",  format="float", nullable=true, example=24.7136),
     *             @OA\Property(property="delivery_lng",     type="number",  format="float", nullable=true, example=46.6753)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Temporary booking saved.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success",  type="boolean", example=true),
     *             @OA\Property(property="message",  type="string",  example="Booking draft saved."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="temp_booking_id", type="string", example="TB-20250520-9XKL2")
     *             ),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingTempStore(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/car-booking/preview",
     *     tags={"Bookings"},
     *     summary="Preview booking cost breakdown before confirming",
     *     operationId="bookingPreview",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="temp_booking_id", in="query", required=true, description="Temp booking reference", @OA\Schema(type="string", example="TB-20250520-9XKL2")),
     *     @OA\Response(
     *         response=200,
     *         description="Booking cost preview.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Booking preview ready."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="rental_days",   type="integer", example=7),
     *                 @OA\Property(property="rental_fees",   type="number",  format="float", example=1050.00),
     *                 @OA\Property(property="delivery_fee",  type="number",  format="float", example=50.00),
     *                 @OA\Property(property="tax_rate",      type="number",  format="float", example=15.0),
     *                 @OA\Property(property="tax_amount",    type="number",  format="float", example=165.00),
     *                 @OA\Property(property="total_amount",  type="number",  format="float", example=1265.00),
     *                 @OA\Property(property="currency",      type="string",  example="SAR")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingPreview(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/car-booking/confirm",
     *     tags={"Bookings"},
     *     summary="Confirm and submit a booking",
     *     operationId="bookingConfirm",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"temp_booking_id","payment_method"},
     *             @OA\Property(property="temp_booking_id",    type="string", example="TB-20250520-9XKL2"),
     *             @OA\Property(property="payment_method",     type="string", enum={"wallet","online"}, example="wallet"),
     *             @OA\Property(property="payment_gateway_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking confirmed.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Booking confirmed successfully."),
     *             @OA\Property(property="data",    ref="#/components/schemas/CarBookingResource"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingConfirm(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/car-booking/cancel",
     *     tags={"Bookings"},
     *     summary="Cancel an active booking",
     *     operationId="bookingCancel",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"booking_id"},
     *             @OA\Property(property="booking_id", type="integer", example=201),
     *             @OA\Property(property="reason",     type="string",  nullable=true, example="Change of plans")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Booking cancelled.", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingCancel(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/car-booking/extend/preview",
     *     tags={"Bookings"},
     *     summary="Preview the cost of extending a booking",
     *     operationId="bookingExtendPreview",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="booking_id",      in="query", required=true,  @OA\Schema(type="integer", example=201)),
     *     @OA\Parameter(name="new_return_date",  in="query", required=true,  description="New return date (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2025-06-15")),
     *     @OA\Response(
     *         response=200,
     *         description="Extension cost preview.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Extension preview ready."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="extra_days",     type="integer", example=7),
     *                 @OA\Property(property="extra_cost",     type="number",  format="float", example=1050.00),
     *                 @OA\Property(property="tax_amount",     type="number",  format="float", example=157.50),
     *                 @OA\Property(property="total_due",      type="number",  format="float", example=1207.50),
     *                 @OA\Property(property="currency",       type="string",  example="SAR")
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingExtendPreview(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/car-booking/extend",
     *     tags={"Bookings"},
     *     summary="Confirm a booking extension",
     *     operationId="bookingExtend",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"booking_id","new_return_date","payment_method"},
     *             @OA\Property(property="booking_id",          type="integer", example=201),
     *             @OA\Property(property="new_return_date",      type="string",  format="date", example="2025-06-15"),
     *             @OA\Property(property="payment_method",       type="string",  enum={"wallet","online"}, example="wallet"),
     *             @OA\Property(property="payment_gateway_id",   type="integer", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Booking extended.", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingExtend(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/car-booking/{bookingId}/extensions",
     *     tags={"Bookings"},
     *     summary="List all extensions for a specific booking",
     *     operationId="bookingExtensionsList",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="bookingId", in="path", required=true, description="Booking ID", @OA\Schema(type="integer", example=201)),
     *     @OA\Response(
     *         response=200,
     *         description="Booking extensions list.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Extensions retrieved."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id",              type="integer", example=1),
     *                     @OA\Property(property="extra_days",      type="integer", example=7),
     *                     @OA\Property(property="new_return_date", type="string",  format="date", example="2025-06-15"),
     *                     @OA\Property(property="amount_paid",     type="number",  format="float", example=1207.50),
     *                     @OA\Property(property="created_at",      type="string",  format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Booking not found.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function bookingExtensionsList(): void {}
}
