<?php

use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\User\AddMoneyController;
use App\Http\Controllers\Api\V1\User\CarBookingController;
use App\Http\Controllers\Api\V1\User\DashboardController;
use App\Http\Controllers\Api\V1\User\TransactionController;
use App\Http\Controllers\Api\V1\User\BalanceController;
use App\Http\Controllers\Api\V1\User\BranchController;
use Pusher\PushNotifications\PushNotifications;

Route::prefix('user')
    ->name('api.user.')
    ->group(function () {

        // Pusher Beams Auth (Mobile Push Notifications)
        // Returns a Beams token JSON that the mobile SDK expects.
        Route::get('pusher/beams-auth', function (Request $request) {
            $user = auth('api')->user();
            if (!$user) {
                return response(['Inconsistent request'], 401);
            }

            $basic_settings = BasicSettingsProvider::get();
            if (!$basic_settings) {
                return response('Basic setting not found!', 404);
            }

            $notification_config = $basic_settings->push_notification_config;
            if (!$notification_config) {
                return response('Notification configuration not found!', 404);
            }

            $instance_id = $notification_config->instance_id ?? null;
            $primary_key = $notification_config->primary_key ?? null;
            if ($instance_id == null || $primary_key == null) {
                return response('Sorry! You have to configure first to send push notification.', 404);
            }

            $beamsClient = new PushNotifications([
                'instanceId' => $instance_id,
                'secretKey' => $primary_key,
            ]);

            $publisherUserId = make_user_id_for_pusher('user', $user->id);
            try {
                $beamsToken = $beamsClient->generateToken($publisherUserId);
            } catch (Throwable $e) {
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
        });
    });
