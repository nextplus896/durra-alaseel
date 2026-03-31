<?php

namespace App\Http\Controllers\Api\V1\Swagger;

abstract class UserApiDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/cars",
     *     tags={"Cars"},
     *     summary="List all available cars (paginated)",
     *     operationId="carsList",
     *     @OA\Parameter(name="page",        in="query", required=false, description="Page number",       @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page",    in="query", required=false, description="Items per page",    @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="car_type_id", in="query", required=false, description="Filter by car type", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="branch_id",   in="query", required=false, description="Filter by branch",   @OA\Schema(type="integer")),
     *     @OA\Parameter(name="min_price",   in="query", required=false, description="Min daily price",    @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="max_price",   in="query", required=false, description="Max daily price",    @OA\Schema(type="number", format="float")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of cars.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Cars retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data",  type="array",  @OA\Items(ref="#/components/schemas/CarResource")),
     *                 @OA\Property(property="meta",  ref="#/components/schemas/PaginationMeta"),
     *                 @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *             ),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public static function carsList(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/cars/{id}",
     *     tags={"Cars"},
     *     summary="Get a single car by ID",
     *     operationId="carShow",
     *     @OA\Parameter(name="id", in="path", required=true, description="Car ID", @OA\Schema(type="integer", example=101)),
     *     @OA\Response(
     *         response=200,
     *         description="Car resource.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Car retrieved successfully."),
     *             @OA\Property(property="data",    ref="#/components/schemas/CarResource"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Car not found.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function carShow(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/cars/types",
     *     tags={"Cars"},
     *     summary="List all car types / categories",
     *     operationId="carTypes",
     *     @OA\Response(
     *         response=200,
     *         description="Car type list.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Car types retrieved."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id",   type="integer", example=1),
     *                     @OA\Property(property="name", type="string",  example="SUV"),
     *                     @OA\Property(property="icon", type="string",  nullable=true, format="uri")
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public static function carTypes(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/profile/info",
     *     tags={"Users"},
     *     summary="Retrieve the authenticated user's profile",
     *     operationId="userProfileGet",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Profile retrieved successfully."),
     *             @OA\Property(property="data",    ref="#/components/schemas/UserProfile"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function userProfileGet(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/profile/info/update",
     *     tags={"Users"},
     *     summary="Update the authenticated user's profile",
     *     operationId="userProfileUpdate",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="firstname",  type="string", example="Ahmad"),
     *                 @OA\Property(property="lastname",   type="string", example="Al-Rashidi"),
     *                 @OA\Property(property="email",      type="string", format="email", example="ahmad@example.com"),
     *                 @OA\Property(property="mobile",     type="string", example="501234567"),
     *                 @OA\Property(property="country_code", type="string", example="+966"),
     *                 @OA\Property(property="image",      type="string", format="binary", description="Profile picture (JPEG / PNG, max 2 MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Profile updated successfully."),
     *             @OA\Property(property="data",    ref="#/components/schemas/UserProfile"),
     *             @OA\Property(property="errors",  type="object",  nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function userProfileUpdate(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/profile/password/update",
     *     tags={"Users"},
     *     summary="Change the authenticated user's password",
     *     operationId="userPasswordUpdate",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password",      type="string", format="password", example="OldP@ssw0rd!"),
     *             @OA\Property(property="password",              type="string", format="password", minLength=8, example="NewP@ssw0rd!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewP@ssw0rd!")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password changed.", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function userPasswordUpdate(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/dashboard",
     *     tags={"Users"},
     *     summary="Retrieve the user's dashboard summary",
     *     operationId="userDashboard",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Dashboard loaded."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="active_bookings",    type="integer", example=2),
     *                 @OA\Property(property="completed_bookings", type="integer", example=14),
     *                 @OA\Property(property="wallet_balance",     type="number",  format="float", example=250.00),
     *                 @OA\Property(property="currency",           type="string",  example="SAR")
     *             ),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
     * )
     */
    public static function userDashboard(): void {}
}
