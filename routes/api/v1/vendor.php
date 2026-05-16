<?php

use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\Vendor\ProfileController;
use App\Http\Controllers\Api\V1\User\AddMoneyController;
use App\Http\Controllers\Api\V1\Vendor\DashboardController;
use App\Http\Controllers\Api\V1\User\TransactionController;
use App\Http\Controllers\Api\V1\Vendor\BookingController;
use App\Http\Controllers\Api\V1\Vendor\BookingHistoryController;
use App\Http\Controllers\Api\V1\Vendor\CarController;
use App\Http\Controllers\Api\V1\Vendor\WithdrawController;
use Pusher\PushNotifications\PushNotifications;

Route::prefix('vendor')->name('api.vendor.')->group(function () {

    // Pusher Beams Auth (Mobile Push Notifications)
    // Returns a Beams token JSON that the mobile SDK expects.
    Route::get('pusher/beams-auth', function (Request $request) {
        \Log::info('═══════════════════════════════════════════════════');
        \Log::info('🔔 [VENDOR API] PUSHER BEAMS AUTH REQUEST RECEIVED');
        \Log::info('═══════════════════════════════════════════════════');
        \Log::info('📍 Request URL: ' . $request->fullUrl());
        \Log::info('📍 Request Method: ' . $request->method());
        \Log::info('📍 Request IP: ' . $request->ip());
        \Log::info('📍 Timestamp: ' . now()->toDateTimeString());
        \Log::info('Request Headers:', [
            'Authorization' => $request->header('Authorization') ? 'Bearer ***' : 'Missing',
            'Accept' => $request->header('Accept'),
            'Content-Type' => $request->header('Content-Type'),
            'User-Agent' => $request->header('User-Agent'),
        ]);
        \Log::info('Query Parameters: ' . json_encode($request->query()));
        \Log::info('Request Body: ' . ($request->getContent() ?: 'Empty'));
        
        $vendor = auth('vendor_api')->user();
        if (!$vendor) {
            \Log::error('❌ Authentication failed - No authenticated vendor');
            return response(['Inconsistent request'], 401);
        }
        
        // Validate user_id query parameter matches authenticated vendor (security check)
        $userIDInQueryParam = $request->query('user_id');
        \Log::info('📋 Validating user_id query parameter', [
            'authenticated_vendor_id' => $vendor->id,
            'query_param_user_id' => $userIDInQueryParam,
        ]);
        
        // The Pusher Beams SDK automatically sends user_id as query param
        // We must verify it matches the authenticated vendor
        if ($userIDInQueryParam && $userIDInQueryParam != 'vendor-' . $vendor->id) {
            \Log::error('❌ Vendor ID mismatch - query param does not match authenticated vendor', [
                'authenticated_vendor_id' => $vendor->id,
                'expected_format' => 'vendor-' . $vendor->id,
                'received' => $userIDInQueryParam,
            ]);
            return response(['Inconsistent request - vendor ID mismatch'], 401);
        }
        
        \Log::info('✅ Vendor authenticated successfully', [
            'vendor_id' => $vendor->id,
            'email' => $vendor->email,
        ]);

        $basic_settings = BasicSettingsProvider::get();
        if (!$basic_settings) {
            \Log::error('❌ Basic settings not found');
            return response('Basic setting not found!', 404);
        }

        $notification_config = $basic_settings->push_notification_config;
        if (!$notification_config) {
            \Log::error('❌ Push notification config not found in basic settings');
            return response('Notification configuration not found!', 404);
        }

        $instance_id = $notification_config->instance_id ?? null;
        $primary_key = $notification_config->primary_key ?? null;
        
        \Log::info('📋 Push Notification Config:', [
            'instance_id' => $instance_id ? (substr($instance_id, 0, 10) . '...') : 'Missing',
            'primary_key' => $primary_key ? 'Present (****)' : 'Missing',
        ]);
        
        if ($instance_id == null || $primary_key == null) {
            \Log::error('❌ Instance ID or Primary Key missing');
            return response('Sorry! You have to configure first to send push notification.', 404);
        }

        try {
            $beamsClient = new PushNotifications([
                'instanceId' => $instance_id,
                'secretKey' => $primary_key,
            ]);
            \Log::info('✅ Pusher Beams client created');
        } catch (Throwable $e) {
            \Log::error('❌ Failed to create Pusher Beams client: ' . $e->getMessage());
            return response(['Server Error. Failed to create beams client.'], 500);
        }

        $publisherUserId = make_user_id_for_pusher('vendor', $vendor->id);
        \Log::info('📱 Publisher User ID: ' . $publisherUserId);
        
        try {
            $beamsToken = $beamsClient->generateToken($publisherUserId);
            \Log::info('✅ Beams token generated successfully');
            \Log::info('Token structure: ' . json_encode(array_keys((array)$beamsToken)));
            
            // Log the complete response (for debugging)
            $responseData = json_decode(json_encode($beamsToken), true);
            \Log::info('📤 Response Data:', [
                'token_length' => isset($responseData['token']) ? strlen($responseData['token']) : 0,
                'has_token' => isset($responseData['token']),
                'response_keys' => array_keys($responseData),
            ]);
            
            \Log::info('═══════════════════════════════════════════════════');
            \Log::info('✅ [VENDOR API] PUSHER BEAMS AUTH SUCCESSFUL');
            \Log::info('📱 Returning token to mobile app');
            \Log::info('═══════════════════════════════════════════════════');
        } catch (Throwable $e) {
            \Log::error('❌ Failed to generate beams token: ' . $e->getMessage());
            \Log::error('Exception: ' . get_class($e));
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('═══════════════════════════════════════════════════');
            return response(['Server Error. Failed to generate beams token.'], 500);
        }

        return response()->json($beamsToken);
    })->name('pusher.beams.auth');

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('info', 'profileInfo');
        Route::post('info/update', 'profileInfoUpdate')->middleware(['app.mode']);
        Route::post('password/update', 'profilePasswordUpdate')->middleware(['app.mode']);
        Route::post('delete-account', 'deleteProfile')->middleware(['app.mode']);
    });

    // Logout Route
    Route::post('logout', [ProfileController::class, 'logout']);

    // Dashboard, Notification,
    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'dashboard');
        Route::get('notifications', 'notifications');
        Route::post('due/pay', 'duePay');
    });

    // Transaction
    Route::controller(TransactionController::class)->prefix('transaction')->group(function () {
        Route::get('log', 'log');
    });

    // Car routes
    Route::controller(CarController::class)->middleware(['kyc.verification.guard'])->prefix('car')->group(function () {
        Route::get('list', 'list');   // GET /api/v1/vendor/car/list - Get vendor's own cars with sort/filter
        Route::get('area', 'carArea');
        Route::get('type', 'carType');
        Route::post('area/types', 'getAreaTypes');
        Route::post('store', 'store');
        Route::post('status', 'statusUpdate');
        Route::post('details', 'details');
        Route::post('update', 'update');
        Route::post('delete', 'delete');
    });

    // Booking routes
    Route::controller(BookingController::class)->middleware(['kyc.verification.guard'])->prefix('booking')->group(function () {
        Route::get('requests', 'bookings');
        Route::get('accept', 'accept');
        Route::post('reject', 'reject');
        Route::get('complete', 'complete');
    });

    // Booking History routes
    Route::controller(BookingHistoryController::class)->prefix('history')->group(function () {
        Route::get('view', 'view');
    });

    //Withdraw Money Routes
    Route::controller(WithdrawController::class)->middleware(['kyc.verification.guard'])->prefix("withdraw")->name('withdraw.')->group(function () {
        Route::get("wallet-gateways", "walletGateways");
        Route::get("gateway/input-fields", "gatewayInputFields");
        Route::post("submit", "submit");
    });
});
