<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Providers\Admin\BasicSettingsProvider;
use Pusher\PushNotifications\PushNotifications;
use App\Http\Controllers\Api\V1\User\SettingController;
use App\Http\Controllers\Api\V1\CarListController;

// Settings
Route::controller(SettingController::class)->prefix("settings")->group(function () {
    Route::get("basic-settings", "basicSettings");
    Route::get("splash-screen", "splashScreen");
    Route::get("onboard-screens", "onboardScreens");
    Route::get("languages", "getLanguages");
    Route::get("countries", "getCountries");
});

// Public Car Listing API (no auth required)
Route::controller(CarListController::class)->prefix("cars")->group(function () {
    Route::get("/", "index");                           // GET /api/v1/cars?sort=price_asc&car_type_id=1&car_model_id=2&per_page=15
    Route::get("/types", "carTypes");                   // GET /api/v1/cars/types
    Route::get("/models", "carModels");                 // GET /api/v1/cars/models?car_type_id=1
    Route::get("/vendor/{vendorId}", "vendorCars");     // GET /api/v1/cars/vendor/5?sort=price_desc
    Route::get("/{id}", "show");                        // GET /api/v1/cars/123
});
