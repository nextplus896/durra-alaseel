<?php

use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $vendor = auth('vendor_api')->user();
        if (!$vendor) {
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

        $publisherUserId = make_user_id_for_pusher('vendor', $vendor->id);
        try {
            $beamsToken = $beamsClient->generateToken($publisherUserId);
        } catch (Throwable $e) {
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
    Route::controller(CarController::class)->middleware(['kyc.verification.guard'])->prefix('car')->name("car")->group(function () {
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
    Route::controller(BookingController::class)->middleware(['kyc.verification.guard'])->prefix('booking')->name("booking")->group(function () {
        Route::get('requests', 'bookings');
        Route::get('accept', 'accept');
        Route::get('reject', 'reject');
        Route::get('complete', 'complete');
    });

    // Booking History routes
    Route::controller(BookingHistoryController::class)->prefix('history')->name("history")->group(function () {
        Route::get('view', 'view');
    });

    //Withdraw Money Routes
    Route::controller(WithdrawController::class)->middleware(['kyc.verification.guard'])->prefix("withdraw")->name('withdraw.')->group(function () {
        Route::get("wallet-gateways", "walletGateways");
        Route::get("gateway/input-fields", "gatewayInputFields");
        Route::post("submit", "submit");
    });
});
