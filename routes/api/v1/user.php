<?php

use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\User\AddMoneyController;
use App\Http\Controllers\Api\V1\User\CarBookingController;
use App\Http\Controllers\Api\V1\User\DashboardController;
use App\Http\Controllers\Api\V1\User\TransactionController;
use App\Http\Controllers\Api\V1\User\BalanceController;
use App\Http\Controllers\Api\V1\User\BranchController;
use App\Http\Controllers\Api\V1\User\DeliveryController;
use App\Http\Controllers\Api\V1\User\WalletController;
use App\Http\Controllers\Api\V1\User\PaymentController;
use App\Http\Controllers\Api\V1\User\IdentificationController;
use Pusher\PushNotifications\PushNotifications;

Route::prefix('user')
    ->name('api.user.')
    ->group(function () {

        // Test endpoint to verify auth is working
        Route::get('pusher/test-auth', function (Request $request) {
            Log::info('🧪 PUSHER TEST AUTH ENDPOINT CALLED');
            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated',
                    'headers' => [
                        'Authorization' => $request->header('Authorization') ? 'Present' : 'Missing',
                    ],
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Authentication working',
                'user_id' => $user->id,
                'publishable_id' => 'user-' . $user->id,
                'timestamp' => now()->toDateTimeString(),
            ]);
        })->name('pusher.test.auth');

        // Pusher Beams Auth (Mobile Push Notifications)
        // Returns a Beams token JSON that the mobile SDK expects.
        Route::get('pusher/beams-auth', function (Request $request) {
            Log::info('═══════════════════════════════════════════════════');
            Log::info('🔔 PUSHER BEAMS AUTH REQUEST RECEIVED');
            Log::info('═══════════════════════════════════════════════════');
            Log::info('📍 Request URL: ' . $request->fullUrl());
            Log::info('📍 Request Method: ' . $request->method());
            Log::info('📍 Request IP: ' . $request->ip());
            Log::info('📍 Timestamp: ' . now()->toDateTimeString());
            Log::info('Request Headers:', [
                'Authorization' => $request->header('Authorization') ? 'Bearer ***' : 'Missing',
                'Accept' => $request->header('Accept'),
                'Content-Type' => $request->header('Content-Type'),
                'User-Agent' => $request->header('User-Agent'),
            ]);
            Log::info('Query Parameters: ' . json_encode($request->query()));
            Log::info('Request Body: ' . ($request->getContent() ?: 'Empty'));

            $user = auth('api')->user();
            if (!$user) {
                Log::error('❌ Authentication failed - No authenticated user');
                Log::error('   This means the Bearer token is invalid or expired');
                Log::error('   Check if user is logged in and token is being sent correctly');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            Log::info('✅ User authenticated successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->fullname ?? 'N/A',
            ]);

            $basic_settings = BasicSettingsProvider::get();
            if (!$basic_settings) {
                Log::error('❌ Basic settings not found');
                return response('Basic setting not found!', 404);
            }

            $notification_config = $basic_settings->push_notification_config;
            if (!$notification_config) {
                Log::error('❌ Push notification config not found in basic settings');
                return response('Notification configuration not found!', 404);
            }

            $instance_id = $notification_config->instance_id ?? null;
            $primary_key = $notification_config->primary_key ?? null;

            Log::info('📋 Push Notification Config:', [
                'instance_id' => $instance_id ? (substr($instance_id, 0, 10) . '...') : 'Missing',
                'primary_key' => $primary_key ? 'Present (****)' : 'Missing',
            ]);

            if ($instance_id == null || $primary_key == null) {
                Log::error('❌ Instance ID or Primary Key missing');
                return response('Sorry! You have to configure first to send push notification.', 404);
            }

            try {
                $beamsClient = new PushNotifications([
                    'instanceId' => $instance_id,
                    'secretKey' => $primary_key,
                ]);
                Log::info('✅ Pusher Beams client created');
            } catch (Throwable $e) {
                Log::error('❌ Failed to create Pusher Beams client: ' . $e->getMessage());
                return response(['Server Error. Failed to create beams client.'], 500);
            }

            // Simple format: user-{userId}
            $publisherUserId = 'user-' . $user->id;
            Log::info('📱 Publisher User ID: ' . $publisherUserId);

            try {
                $beamsToken = $beamsClient->generateToken($publisherUserId);
                Log::info('✅ Beams token generated successfully');
                Log::info('Token structure: ' . json_encode(array_keys((array)$beamsToken)));

                // Log the complete response (for debugging)
                $responseData = json_decode(json_encode($beamsToken), true);
                Log::info('📤 Response Data:', [
                    'token_length' => isset($responseData['token']) ? strlen($responseData['token']) : 0,
                    'has_token' => isset($responseData['token']),
                    'response_keys' => array_keys($responseData),
                ]);

                Log::info('═══════════════════════════════════════════════════');
                Log::info('✅ PUSHER BEAMS AUTH SUCCESSFUL');
                Log::info('📱 Returning token to mobile app');
                Log::info('═══════════════════════════════════════════════════');
            } catch (Throwable $e) {
                Log::error('❌ Failed to generate beams token: ' . $e->getMessage());
                Log::error('Exception: ' . get_class($e));
                Log::error('Stack trace: ' . $e->getTraceAsString());
                Log::error('═══════════════════════════════════════════════════');
                return response(['Server Error. Failed to generate beams token.'], 500);
            }

            return response()->json($beamsToken);
        })->name('pusher.beams.auth');

        Route::controller(ProfileController::class)
            ->prefix('profile')
            ->group(function () {
                Route::get('info', 'profileInfo');
                Route::post('info/update', 'profileInfoUpdate')->middleware(['app.mode']);
                Route::post('password/update', 'profilePasswordUpdate')->middleware(['app.mode']);
                Route::post('delete-account', 'deleteProfile')->middleware(['app.mode']);
            });

        // Logout Route
        Route::post('logout', [ProfileController::class, 'logout']);

        // // Dashboard, Notification,
        Route::controller(DashboardController::class)->group(function () {
            Route::get('dashboard', 'dashboard');
            Route::get('notifications', 'notifications');
        });

        // // Transaction
        Route::controller(TransactionController::class)
            ->prefix('transaction')
            ->group(function () {
                Route::get('log', 'log');
            });

        // Balance Management
        Route::controller(BalanceController::class)
            ->prefix('balance')
            ->name('balance.')
            ->group(function () {
                Route::get('/', 'getBalance');
                Route::get('history', 'getTransactionHistory');
                Route::post('recharge', 'initiateRecharge');
                Route::post('recharge/callback', 'rechargeCallback')->name('recharge.callback')->withoutMiddleware(['auth:api']);
                Route::get('recharge/return', 'rechargeReturn')->name('recharge.return')->withoutMiddleware(['auth:api']);
                Route::get('tax-settings', 'getTaxSettings');
                Route::post('calculate-tax', 'calculateWithTax');
                Route::post('check', 'checkBalance');
            });

        // Branch & Delivery Services
        Route::controller(BranchController::class)
            ->prefix('branches')
            ->name('branches.')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/{id}', 'show');
                Route::post('check-service-area', 'checkServiceArea');
                Route::post('cars-with-delivery', 'getCarsWithDelivery');
                Route::post('delivery-price', 'getDeliveryPrice');
            });

        // Delivery radius check
        Route::post('check-delivery-area', [DeliveryController::class, 'checkDeliveryArea'])
            ->name('check.delivery.area');

        // // car booking
        Route::controller(CarBookingController::class)->prefix('car-booking')->name('car.booking.')->group(function () {
            Route::get('booking/history', 'bookingHistory');
            Route::get('area', 'carArea');
            Route::get('type', 'carType');
            Route::post('area/types', 'getAreaTypes');
            Route::post('search/car', 'searchCar');
            Route::get('car/details', 'viewCar');
            Route::post('temp/store', 'store');
            Route::get('preview', 'preview');
            Route::post('confirm', 'confirm');
            // Automatic Gateway Response Routes
            Route::get('success/response/{gateway}', 'success')->withoutMiddleware(['auth:api'])->name('payment.success');
            Route::get('cancel/response/{gateway}', 'cancel')->withoutMiddleware(['auth:api'])->name('payment.cancel');

            // POST Route For Unauthenticated Request
            Route::post('success/response/{gateway}', 'postSuccess')
                ->name('payment.success')
                ->withoutMiddleware(['auth:api']);
            Route::post('cancel/response/{gateway}', 'postCancel')
                ->name('payment.cancel')
                ->withoutMiddleware(['auth:api']);

            //redirect with Btn Pay
            Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')
                ->name('payment.btn.pay')
                ->withoutMiddleware(['auth:api']);

            Route::get('manual/input-fields', 'manualInputFields');
            Route::get('re-manual/input-fields', 'reManualInputFields');

            // Submit with manual gateway
            Route::post('manual/submit', 'manualSubmit');

            // Automatic gateway additional fields
            Route::get('payment-gateway/additional-fields', 'gatewayAdditionalFields');

            Route::prefix('payment')
                ->name('payment.')
                ->group(function () {
                    Route::post('crypto/confirm/{trx_id}', 'cryptoPaymentConfirm')->name('crypto.confirm');
                });
            Route::post('repayment/submit', 'repaymentSubmit')->name('repayment.submit');

            // authorize payment submit
            Route::post('authorize-payment-submit', 'authorizePaymentSubmit')->name('authorize.payment');

            // Rental extension
            Route::get('extend/preview', 'previewExtension')->name('extend.preview');
            Route::post('extend', 'extendBooking')->name('extend');
            Route::get('{bookingId}/extensions', 'extensionHistory')->name('extension.history');
            Route::post('cancel', 'cancelBooking')->name('cancel');
        });

        // Wallet Management Routes
        Route::controller(WalletController::class)->prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/recharge', 'recharge')->name('recharge');
            Route::get('/transactions', 'transactions')->name('transactions');
        });

        // Payment Management Routes
        Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
            Route::post('/{paymentId}/refund', 'refund')->name('refund');
        });

        // National ID and Driving License Management Routes
        Route::controller(IdentificationController::class)
            ->prefix('identification')
            ->name('identification.')
            ->group(function () {
                Route::get('info', 'getIdentification')->name('info');
                Route::get('all', 'getAllIdentifications')->name('all');

                Route::prefix('national-id')->name('national_id.')->group(function () {
                    Route::post('upload', 'uploadNationalId')->name('upload')->middleware(['app.mode']);
                    Route::delete('delete', 'deleteNationalId')->name('delete')->middleware(['app.mode']);
                });

                Route::prefix('driving-license')->name('driving_license.')->group(function () {
                    Route::post('upload', 'uploadDrivingLicense')->name('upload')->middleware(['app.mode']);
                    Route::delete('delete', 'deleteDrivingLicense')->name('delete')->middleware(['app.mode']);
                });
            });
    });
