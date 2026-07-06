<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\User\CancellationPolicyController as UserCancellationPolicyController;
use App\Http\Controllers\Webhooks\TwilioWebhookController;
use App\Http\Controllers\Webhooks\MoyasarWebhookController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// OTP Verification API (v1)
Route::prefix('v1')->group(function () {
    Route::prefix('otp')->middleware('throttle:10,1')->group(function () {
        Route::post('/request', [OtpController::class, 'request']);
        Route::post('/verify', [OtpController::class, 'verify']);
    });
});

// Twilio Webhooks (no auth required)
Route::prefix('webhooks/twilio')->group(function () {
    Route::post('/status', [TwilioWebhookController::class, 'statusCallback']);
    Route::post('/pricing', [TwilioWebhookController::class, 'pricingCallback']);
});

// Moyasar Webhook (no auth required - called by Moyasar servers)
Route::post('/webhooks/moyasar', [MoyasarWebhookController::class, 'handle'])->name('moyasar.webhook');

// Cancellation Policy (v1)
Route::prefix('v1')->group(function () {
    // Public: get active global cancellation policy
    Route::get('cancellation-policy', [UserCancellationPolicyController::class, 'show'])
        ->name('api.v1.cancellation.policy.show');

    // Authenticated: preview refund for a booking
    Route::middleware('auth:api')
        ->post('cancellation-policy/preview', [UserCancellationPolicyController::class, 'previewRefund'])
        ->name('api.v1.cancellation.policy.preview');
});
