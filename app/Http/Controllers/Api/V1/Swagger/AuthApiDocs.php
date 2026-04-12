<?php

namespace App\Http\Controllers\Api\V1\Swagger;

abstract class AuthApiDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"Authentication"},
     *     summary="Register a new user account",
     *     operationId="userRegister",
     *     @OA\RequestBody(
     *         required=true,
     *         description="New user registration payload.",
     *         @OA\JsonContent(
     *             required={"firstname","lastname","email","password","phone"},
     *             @OA\Property(property="firstname", type="string", example="Ahmad"),
     *             @OA\Property(property="lastname",  type="string", example="Al-Rashidi"),
     *             @OA\Property(property="email",     type="string", format="email", example="ahmad@example.com"),
     *             @OA\Property(property="password",  type="string", format="password", minLength=6, example="P@ssw0rd!"),
     *             @OA\Property(property="phone",     type="string", example="+966501234567", description="Phone number including country code."),
     *             @OA\Property(property="refer",     type="string", nullable=true, example="ABCD1234", description="Referral ID of an existing user (optional)."),
     *             @OA\Property(property="agree",     type="string", nullable=true, example="on",       description="Required only when agree_policy is enabled in system settings.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="User successfully registered"))
     *             ),
     *             @OA\Property(property="data", ref="#/components/schemas/AuthTokenData"),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=400, description="User already exists or registration failed.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error.",                               @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function register(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"Authentication"},
     *     summary="Authenticate a user and issue a Bearer token",
     *     operationId="userLogin",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Use email address or username in the credentials field.",
     *         @OA\JsonContent(
     *             required={"credentials","password"},
     *             @OA\Property(property="credentials", type="string", example="aymansaadtest@gmail.com", description="User email address or username."),
     *             @OA\Property(property="password",    type="string", format="password",           example="Aym@nS23d")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="User successfully logged in"))
     *             ),
     *             @OA\Property(property="data", ref="#/components/schemas/AuthTokenData"),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Wrong credentials or account suspended.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="User not found.",                         @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function login(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/user/logout",
     *     tags={"Authentication"},
     *     summary="Revoke the current Bearer token (logout)",
     *     operationId="userLogout",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="Logout success!"))
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated.", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
     *     @OA\Response(response=500, description="Server error.",    @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function logout(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/password/forgot/find/user",
     *     tags={"Authentication"},
     *     summary="Step 1 – Find account and send reset code to email",
     *     operationId="forgotPasswordSendCode",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"credentials"},
     *             @OA\Property(property="credentials", type="string", example="ahmad@example.com", description="User email address or username.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent to email.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="Verification code sended to your email address"))
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token",     type="string", example="abc123def456...", description="Pass this token in Steps 2 and 3."),
     *                 @OA\Property(property="wait_time", type="string", example="")
     *             ),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Account suspended.",  @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="User not found.",      @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error.",        @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function forgotPasswordSendCode(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/password/forgot/verify/code",
     *     tags={"Authentication"},
     *     summary="Step 2 – Verify the reset code",
     *     operationId="forgotPasswordVerifyCode",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","code"},
     *             @OA\Property(property="token", type="string",  example="abc123def456...", description="Token received from Step 1."),
     *             @OA\Property(property="code",  type="integer", example=483921,            description="Numeric OTP code received by email.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="OTP successfully verified!"))
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token",     type="string", example="abc123def456..."),
     *                 @OA\Property(property="wait_time", type="string", example="")
     *             ),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid OTP.",      @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=440, description="Session expired.",   @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function forgotPasswordVerifyCode(): void {}

    /**
     * @OA\Get(
     *     path="/api/v1/password/forgot/resend/code",
     *     tags={"Authentication"},
     *     summary="Step 2b – Resend the password-reset code",
     *     operationId="forgotPasswordResendCode",
     *     @OA\Parameter(name="token", in="query", required=true, @OA\Schema(type="string"), example="abc123def456...", description="Token received from Step 1."),
     *     @OA\Response(
     *         response=200,
     *         description="OTP resent successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="OTP resend success"))
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token",     type="string", example="abc123def456..."),
     *                 @OA\Property(property="wait_time", type="string", example="")
     *             ),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Too soon to resend or invalid token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function forgotPasswordResendCode(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/password/forgot/reset",
     *     tags={"Authentication"},
     *     summary="Step 3 – Set a new password",
     *     operationId="forgotPasswordReset",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","password","password_confirmation"},
     *             @OA\Property(property="token",                 type="string", example="abc123def456...", description="Token from Step 1 (must not be expired)."),
     *             @OA\Property(property="password",              type="string", format="password", minLength=6, example="NewP@ssw0rd!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password",            example="NewP@ssw0rd!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object",
     *                 @OA\Property(property="success", type="array", @OA\Items(type="string", example="Password reset success"))
     *             ),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="type", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid or expired token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Server error.",             @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function forgotPasswordReset(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/otp/request",
     *     tags={"Authentication"},
     *     summary="Request an OTP via SMS or WhatsApp (Twilio Verify)",
     *     operationId="otpRequest",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Send OTP to the given phone number via the chosen channel.",
     *         @OA\JsonContent(
     *             required={"phone","channel"},
     *             @OA\Property(property="phone",   type="string", example="+966501234567", description="Full E.164 phone number including country code."),
     *             @OA\Property(property="channel", type="string", enum={"sms","whatsapp"}, example="sms", description="Delivery channel: sms or whatsapp."),
     *             @OA\Property(property="locale",  type="string", enum={"en","ar","es","fr"}, nullable=true, example="en", description="Language for the OTP message (default: en).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="OTP sent successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="verification_id", type="string",  example="VE1234567890abcdef"),
     *                 @OA\Property(property="channel",         type="string",  example="sms"),
     *                 @OA\Property(property="to",              type="string",  example="+966501234567"),
     *                 @OA\Property(property="status",          type="string",  example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to send OTP (e.g. invalid number, Twilio error).",
     *         @OA\JsonContent(
     *             @OA\Property(property="success",    type="boolean", example=false),
     *             @OA\Property(property="message",    type="string",  example="Failed to send OTP"),
     *             @OA\Property(property="error_code", type="string",  nullable=true, example="21211")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit exceeded (max 10 requests/minute).", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function otpRequest(): void {}

    /**
     * @OA\Post(
     *     path="/api/v1/otp/verify",
     *     tags={"Authentication"},
     *     summary="Verify an OTP code (Twilio Verify)",
     *     operationId="otpVerify",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","code"},
     *             @OA\Property(property="phone", type="string", example="+966501234567", description="Full E.164 phone number used when requesting the OTP."),
     *             @OA\Property(property="code",  type="string", minLength=6, maxLength=6, example="482910", description="6-digit OTP code received via SMS or WhatsApp.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",  example="Phone verified successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="verification_id", type="string", example="VE1234567890abcdef"),
     *                 @OA\Property(property="status",          type="string", example="approved")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired OTP code.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success",    type="boolean", example=false),
     *             @OA\Property(property="message",    type="string",  example="Invalid verification code"),
     *             @OA\Property(property="error_code", type="string",  nullable=true, example="60200")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=429, description="Rate limit exceeded.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public static function otpVerify(): void {}
}
