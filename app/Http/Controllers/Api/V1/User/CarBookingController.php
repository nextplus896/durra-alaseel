<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use Carbon\Carbon;
use App\Models\CarBooking;
use App\Models\CarBookingTransaction;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TemporaryData;
use App\Constants\GlobalConst;
use App\Http\Helpers\Response;
use App\Models\Vendor\Cars\Car;
use App\Constants\CarBookingConst;
use App\Constants\NotificationConst;
use App\Models\UserNotification;
use App\Models\Admin\Cars\CarArea;
use App\Models\Admin\Cars\CarType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Admin\BasicSettings;
use App\Traits\PaymentGateway\Gpay;
use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\CryptoTransaction;
use App\Models\Admin\TransactionSetting;
use App\Traits\PaymentGateway\Authorize;
use App\Models\Vendor\VendorNotification;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\PaymentGatewayCurrency;
use Illuminate\Support\Facades\Notification;
use net\authorize\api\contract\v1 as AnetAPI;
use App\Notifications\User\CarBookingNotification;
use net\authorize\api\controller as AnetController;
use App\Http\Helpers\PaymentGateway as PaymentGatewayHelper;
use App\Services\BookingBalanceService;
use App\Services\CarBookingExtensionService;
use App\Models\BranchDeliverySetting;
use App\Models\BranchWorkingHour;
use App\Http\Resources\Api\CarBookingResource;
use App\Http\Resources\Api\CarBookingExtensionResource;
use App\Http\Resources\Api\CarResource;
use App\Notifications\User\BookingExtendedNotification;

class CarBookingController extends Controller
{
    use ControlDynamicInputFields, Authorize;

    public function bookingHistory()
    {
        try {
            $user = Auth::guard('api')->user();

            $bookings = CarBooking::where('user_id', $user->id)
                ->with(['cars.vendor'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Use Resource class for consistent type casting
            return Response::success(
                [__('History fetched successfully!')],
                ['history' => CarBookingResource::collection($bookings)],
                200
            );
        } catch (Exception $e) {
            Log::error('CarBooking History Error: ' . $e->getMessage(), [
                'user_id' => auth()->guard('api')->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return Response::error(
                [__('Error fetching booking history')],
                ['error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error'],
                500
            );
        }
    }

    public function carArea()
    {
        $car_area = CarArea::all();
        $message = [__('Car Area Fetched Successfully!')];
        return Response::success($message, $car_area);
    }

    public function carType()
    {
        $car_type = CarType::all();
        $message = [__('Car Type Fetched Successfully!')];
        return Response::success($message, $car_type);
    }

    public function getAreaTypes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all());
        }
        $area = CarArea::with([
            'types' => function ($type) {
                $type->with([
                    'type' => function ($car_type) {
                        $car_type->where('status', true);
                    },
                ]);
            },
        ])->find($request->area);
        if (!$area) {
            return Response::error([__('Area Not Found')], 404);
        }

        return Response::success([__('Types fetch successfully')], ['area' => $area], 200);
    }

    /**
     * Convert time to 24-hour format (HH:mm) if it's in 12-hour format (h:i A)
     */
    private function convertTo24HourFormat($time)
    {
        try {
            // If time contains AM/PM, convert it to 24-hour format
            if (preg_match('/(AM|PM|am|pm)/', $time)) {
                return Carbon::parse($time)->format('H:i');
            }
            // Already in 24-hour format, return as-is
            return $time;
        } catch (\Exception $e) {
            // If parsing fails, return original
            return $time;
        }
    }

    public function searchCar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_type' => 'required',
            'pickup_time' => 'required',
            'pickup_date' => 'required',
            'pickup_location' => 'nullable|array',
            'pickup_location.latitude' => 'required_with:pickup_location|numeric|between:-90,90',
            'pickup_location.longitude' => 'required_with:pickup_location|numeric|between:-180,180',
            'pickup_location.address' => 'required_with:pickup_location|string|max:500',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all());
        }
        $validated = $validator->validate();

        // Convert time to 24-hour format if needed
        $validated['pickup_time'] = $this->convertTo24HourFormat($validated['pickup_time']);
        if (!empty($validated['round_pickup_time'])) {
            $validated['round_pickup_time'] = $this->convertTo24HourFormat($validated['round_pickup_time']);
        }

        $pickupDateTime = Carbon::parse($validated['pickup_date'] . ' ' . $validated['pickup_time']);

        if ($pickupDateTime->isPast()) {
            return Response::error([__('Pickup date and time must be in the future.')], []);
        }

        if (!empty($validated['round_pickup_date']) && !empty($validated['round_pickup_time'])) {
            $roundPickupDateTime = Carbon::parse($validated['round_pickup_date'] . ' ' . $validated['round_pickup_time']);
            if ($roundPickupDateTime->isPast()) {
                return Response::error([__('Round pickup date and time must be in the future.')], []);
            }

            if ($roundPickupDateTime->lte($pickupDateTime)) {
                return Response::error([__('Round pickup date and time must be greater than pickup date and time.')], []);
            }
        }
        $cars = Car::where('car_type_id', $request->car_type)
            ->where('status', true)
            ->where('approval', true)
            ->whereDoesntHave('bookings', function ($query) use ($validated) {
                $query->where('pickup_date', Carbon::parse($validated['pickup_date']))->where('status', CarBookingConst::STATUONGOING);
            })
            ->get();
        $data_path = [
            'base_url' => url('/'),
            'image_path' => files_asset_path_basename('site-section'),
        ];

        try {
            $car_booking = TemporaryData::create([
                'identifier' => generate_unique_string('temporary_datas', 'identifier', 20),
                'type' => Str::slug(CarBookingConst::CAR_BOOKING),
                'data' => $validated,
            ]);
            return Response::success(
                [__('Car search successful')],
                [
                    'token' => $car_booking->identifier,
                    'cars' => CarResource::collection($cars),
                    'data_path' => $data_path
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('CarBooking Search Error: ' . $e->getMessage(), [
                'validated_data' => $validated ?? null,
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return Response::error(
                [__('Something Went Wrong! Please try again.')],
                ['error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error'],
                500
            );
        }
    }

    public function viewCar()
    {
        try {
            $cars = Car::where('status', true)
                ->whereHas('type', function ($query) {
                    $query->where('status', true);
                })
                ->whereHas('branch', function ($query) {
                    $query->where('status', true);
                })
                ->where(function ($query) {
                    $query
                        ->whereHas('bookings', function ($subquery) {
                            $subquery->where('status', '=', 3)->orWhere('status', '=', 1);
                        })
                        ->orWhereDoesntHave('bookings');
                })
                ->get();

            $car_data = [
                'base_url' => url('/'),
                'image_path' => files_asset_path_basename('site-section'),
                'cars' => CarResource::collection($cars),
            ];

            $message = [__('Cars Fetched Successfully!')];
            return Response::success($message, ['cars' => $car_data], 200);
        } catch (Exception $e) {
            Log::error('CarBooking ViewCar Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return Response::error(
                [__('Error fetching cars')],
                ['error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error'],
                500
            );
        }
    }

    public function preview(Request $request)
    {
        $requestId = (string) Str::uuid();
        $userId = Auth::guard('api')->id();

        Log::info('API car-booking.preview start', [
            'request_id' => $requestId,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'has_token' => $request->filled('token'),
            'car_id' => $request->input('car_id'),
            'rental_days' => $request->input('rental_days'),
            'pickup_date' => $request->input('pickup_date'),
            'pickup_time' => $request->input('pickup_time'),
            'include_delivery' => $request->input('include_delivery'),
        ]);

        try {
            // token is optional: accept either an existing token or inline booking data
            if ($request->filled('token')) {
                $validator = Validator::make($request->all(), [
                    'token' => 'required',
                    'car_id' => 'required',
                    'rental_days' => 'required|integer|min:1',
                    'pickup_location' => 'nullable|array',
                    'pickup_location.latitude' => 'required_with:pickup_location|numeric|between:-90,90',
                    'pickup_location.longitude' => 'required_with:pickup_location|numeric|between:-180,180',
                    'pickup_location.address' => 'required_with:pickup_location|string|max:500',
                ]);

                if ($validator->fails()) {
                    return Response::error($validator->errors()->all(), []);
                }

                $validated = $validator->validate();

                Log::info('API car-booking.preview validated (token flow)', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'token' => $validated['token'],
                    'car_id' => $validated['car_id'],
                    'rental_days' => $validated['rental_days'],
                ]);

                $booking_details = TemporaryData::where('identifier', $validated['token'])->first();

                if (!$booking_details) {
                    Log::warning('API car-booking.preview missing TemporaryData', [
                        'request_id' => $requestId,
                        'user_id' => $userId,
                        'token' => $validated['token'],
                    ]);
                    return Response::error([__('Something Went Wrong! Please try again.')], [], 500);
                }

                $car = Car::with(['type', 'carModel', 'area', 'branch', 'vendor'])
                    ->where('id', $validated['car_id'])
                    ->first();

                if (!$car) {
                    Log::warning('API car-booking.preview car not found (token flow)', [
                        'request_id' => $requestId,
                        'user_id' => $userId,
                        'car_id' => $validated['car_id'],
                    ]);
                    return Response::error([__('Car not found')], [], 404);
                }

                $validated_user = Auth::guard('api')->user();
                if (!$validated_user) {
                    Log::warning('API car-booking.preview unauthenticated (token flow)', [
                        'request_id' => $requestId,
                    ]);
                    return Response::error([__('Unauthorized')], [], 401);
                }
                $booking_data = $booking_details->data;
                $tokenToReturn = $validated['token'];
            } else {
                $validator = Validator::make($request->all(), [
                    'car_id' => 'required',
                    'pickup_time' => 'required',
                    'pickup_date' => 'required',
                    'rental_days' => 'required|integer|min:1',
                    'pickup_location' => 'nullable|array',
                    'pickup_location.latitude' => 'required_with:pickup_location|numeric|between:-90,90',
                    'pickup_location.longitude' => 'required_with:pickup_location|numeric|between:-180,180',
                    'pickup_location.address' => 'required_with:pickup_location|string|max:500',
                ]);

                if ($validator->fails()) {
                    return Response::error($validator->errors()->all(), []);
                }

                $validated = $validator->validate();

                Log::info('API car-booking.preview validated (inline flow)', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'car_id' => $validated['car_id'],
                    'rental_days' => $validated['rental_days'],
                ]);

                $car = Car::with(['type', 'carModel', 'area', 'branch', 'vendor'])
                    ->where('id', $validated['car_id'])
                    ->first();

                if (!$car) {
                    Log::warning('API car-booking.preview car not found (inline flow)', [
                        'request_id' => $requestId,
                        'user_id' => $userId,
                        'car_id' => $validated['car_id'],
                    ]);
                    return Response::error([__('Car not found')], [], 404);
                }

                $validated_user = Auth::guard('api')->user();
                if (!$validated_user) {
                    Log::warning('API car-booking.preview unauthenticated (inline flow)', [
                        'request_id' => $requestId,
                    ]);
                    return Response::error([__('Unauthorized')], [], 401);
                }
                // booking details come from request directly
                // Add car_type from the retrieved car
                $validated['car_type'] = $car->car_type_id;
                $booking_data = (object) $validated;
                $tokenToReturn = null;
            }

            Log::info('API car-booking.preview loaded car', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'car_id' => $car->id,
                'vendor_id' => $car->vendor_id,
                'branch_id' => $car->branch_id,
            ]);

            // Get delivery settings for this car
            $deliveryInfo = null;
            if ($car && $car->branch_id && $car->vendor_id) {
                $deliverySetting = BranchDeliverySetting::where('branch_id', $car->branch_id)
                    ->where('vendor_id', $car->vendor_id)
                    ->first();

                if ($deliverySetting) {
                    $deliveryInfo = [
                        'available' => $deliverySetting->delivery_available,
                        'price' => $deliverySetting->delivery_price,
                    ];
                }
            }

            Log::info('API car-booking.preview delivery resolved', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'delivery_info' => $deliveryInfo,
            ]);

            // Calculate pricing with tax - GOLDEN RULE: Backend calculates everything
            $balanceService = new BookingBalanceService();

            // Calculate rental fees based on tiered pricing (daily/weekly/monthly)
            $rentalDays = intval($validated['rental_days']);
            $rentalCalculation = $balanceService->calculateRentalFees($car, $rentalDays);
            $rentalFees = $rentalCalculation['rental_fees'];

            Log::info('API car-booking.preview rental fees calculated', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'rental_days' => $rentalDays,
                'rental_fees' => $rentalFees,
                'price_rule_applied' => $rentalCalculation['price_rule_applied'] ?? null,
            ]);

            $deliveryPrice = ($request->include_delivery && $deliveryInfo && $deliveryInfo['available'])
                ? floatval($deliveryInfo['price'])
                : 0;

            $pricingBreakdown = $balanceService->calculateBookingTotal($rentalFees, $deliveryPrice);

            Log::info('API car-booking.preview totals calculated', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'delivery_price' => $deliveryPrice,
                'pricing_total' => $pricingBreakdown['total'] ?? null,
                'tax_percentage' => $pricingBreakdown['tax_percentage'] ?? null,
            ]);

            // Add rental-specific details to pricing breakdown
            $pricingBreakdown['rental_days'] = $rentalDays;
            $pricingBreakdown['allowance_km'] = intval($request->allowance_km ?? 0);
            $pricingBreakdown['price_rule_applied'] = $rentalCalculation['price_rule_applied'];
            $pricingBreakdown['tax_rate'] = $pricingBreakdown['tax_percentage']; // Alias for frontend compatibility

            $payment_gateways = PaymentGateway::addMoney()->active()->with('currencies')->has('currencies')->get();

            Log::info('API car-booking.preview gateways loaded', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'gateway_count' => $payment_gateways->count(),
            ]);

            // Transform payment gateways to minimal structure to prevent JSON truncation
            $payment_gateways_minimal = $payment_gateways->map(function ($gateway) {
                return [
                    'id' => (int) $gateway->id,
                    'name' => (string) $gateway->name,
                    'type' => (string) $gateway->type,
                    'currencies' => $gateway->currencies->map(function ($currency) {
                        return [
                            'id' => (int) $currency->id,
                            'name' => (string) $currency->name,
                            'currency_code' => (string) $currency->currency_code,
                            'currency_symbol' => (string) $currency->currency_symbol,
                            'min_limit' => (float) ($currency->min_limit ?? 0),
                            'max_limit' => (float) ($currency->max_limit ?? 0),
                        ];
                    })->values()
                ];
            })->values();

            // Clean booking_details to prevent circular references or large objects
            $cleanBookingDetails = is_object($booking_data) ? (array) $booking_data : $booking_data;

            // Ensure only primitive types in booking_details
            $cleanBookingDetails = array_map(function ($value) {
                if (is_object($value)) {
                    return (array) $value;
                }
                return $value;
            }, $cleanBookingDetails);

            // Format dates and times for display
            if (isset($cleanBookingDetails['pickup_date'])) {
                try {
                    $cleanBookingDetails['pickup_date'] = Carbon::parse($cleanBookingDetails['pickup_date'])->format('d-m-Y');
                } catch (\Exception $e) {
                    // Keep original if parsing fails
                }
            }

            if (isset($cleanBookingDetails['pickup_time'])) {
                try {
                    $cleanBookingDetails['pickup_time'] = Carbon::parse($cleanBookingDetails['pickup_time'])->format('h:i A');
                } catch (\Exception $e) {
                    // Keep original if parsing fails
                }
            }

            if (isset($cleanBookingDetails['round_pickup_date']) && $cleanBookingDetails['round_pickup_date']) {
                try {
                    $cleanBookingDetails['round_pickup_date'] = Carbon::parse($cleanBookingDetails['round_pickup_date'])->format('d-m-Y');
                } catch (\Exception $e) {
                    // Keep original if parsing fails
                }
            }

            if (isset($cleanBookingDetails['round_pickup_time']) && $cleanBookingDetails['round_pickup_time']) {
                try {
                    $cleanBookingDetails['round_pickup_time'] = Carbon::parse($cleanBookingDetails['round_pickup_time'])->format('h:i A');
                } catch (\Exception $e) {
                    // Keep original if parsing fails
                }
            }

            Log::info('API car-booking.preview success', [
                'request_id' => $requestId,
                'user_id' => $userId,
            ]);

            return Response::success(
                [__('Booking data stored in the temporary table')],
                [
                    'token' => $tokenToReturn,
                    'booking_details' => $cleanBookingDetails,
                    'booking_currency' => get_default_currency_code(),
                    'car' => new CarResource($car),
                    'user' => [
                        'id' => (int) $validated_user->id,
                        'firstname' => (string) ($validated_user->firstname ?? ''),
                        'lastname' => (string) ($validated_user->lastname ?? ''),
                        'email' => (string) ($validated_user->email ?? ''),
                        'mobile' => (string) ($validated_user->mobile ?? ''),
                        'balance' => (float) ($validated_user->balance ?? 0),
                    ],
                    'user_balance' => [
                        'balance' => (float) round($validated_user->balance ?? 0, 2),
                        'has_sufficient_balance' => ($validated_user->balance ?? 0) >= $pricingBreakdown['total'],
                    ],
                    'delivery_info' => $deliveryInfo,
                    'pricing_breakdown' => $pricingBreakdown,
                    'payment-type' => [
                        'online-payment' => Str::Slug(PaymentGatewayConst::ONLINEPAYMENT),
                        'cash' => Str::Slug(PaymentGatewayConst::CASH),
                        'balance' => 'balance',
                    ],
                    'payment_gateways' => $payment_gateways_minimal,
                ],
                200,
            );
        } catch (\Throwable $e) {
            Log::error('API car-booking.preview exception', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error(
                [__('Something went wrong! Please try again.')],
                ['request_id' => $requestId],
                500
            );
        }
    }

    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => 'required',
            'car_slug' => 'required',
            'location' => 'required',
            'rental_days' => 'required|integer|min:1',
            'pickup_location' => 'nullable|array',
            'pickup_location.latitude' => 'required_with:pickup_location|numeric|between:-90,90',
            'pickup_location.longitude' => 'required_with:pickup_location|numeric|between:-180,180',
            'pickup_location.address' => 'required_with:pickup_location|string|max:500',
            'credentials' => 'required|email',
            'mobile' => 'nullable',
            'round_pickup_date' => 'nullable',
            'round_pickup_time' => 'nullable',
            'message' => 'nullable',
            'token' => 'nullable',
            'payment' => 'required',
            'include_delivery' => 'nullable|boolean',
            'delivery_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), []);
        }

        $validated = $validator->validate();
        $validated['user_id'] = auth()->guard('api')->user()->id;

        // Convert time to 24-hour format if needed (from formatted preview response)
        if (isset($request->pickup_time)) {
            $request->merge(['pickup_time' => $this->convertTo24HourFormat($request->pickup_time)]);
        }
        if (isset($request->round_pickup_time) && !empty($request->round_pickup_time)) {
            $request->merge(['round_pickup_time' => $this->convertTo24HourFormat($request->round_pickup_time)]);
        }

        // If token not provided, create TemporaryData with booking info so downstream flows (payment)
        // can still use a booking token. We create it before handling payment type.
        if (!$request->filled('token')) {
            try {
                $payload = $request->only(['car_type', 'pickup_time', 'pickup_date', 'round_pickup_date', 'round_pickup_time', 'location', 'credentials', 'mobile', 'message', 'payment', 'car_id', 'car_slug', 'include_delivery', 'delivery_price', 'rental_days', 'pickup_location']);
                $payload = array_filter($payload, function ($v) {
                    return $v !== null && $v !== '';
                });
                $car_booking = TemporaryData::create([
                    'identifier' => generate_unique_string('temporary_datas', 'identifier', 20),
                    'type' => Str::slug(CarBookingConst::CAR_BOOKING),
                    'data' => $payload,
                ]);
                $validated['token'] = $car_booking->identifier;
                $request->merge(['token' => $car_booking->identifier]);
            } catch (Exception $e) {
                Log::error('CarBooking TemporaryData Creation Error: ' . $e->getMessage(), [
                    'payload' => $payload ?? null,
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
                return Response::error([__('Something went wrong! Please try again')], ['error_details' => $e->getMessage()], 500);
            }
        }

        $payment = $request->payment;

        // Handle balance payment
        if ($payment === 'balance') {
            $trx_id = generate_unique_string('car_bookings', 'trx_id', 16);
            return $this->bookingConfirmWithBalance($validated, $trx_id, $request);
        }

        if ($payment == Str::slug(PaymentGatewayConst::CASH)) {
            $trx_id = generate_unique_string('car_bookings', 'trx_id', 16);
            return $this->bookingConfirm($validated, 'cash', $trx_id, $request);
        } else {
            $validator = Validator::make($request->all(), [
                'gateway_currency' => 'required',
                'gateway_type' => 'required',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors()->all(), []);
            }

            $temp_booking = TemporaryData::where('identifier', $request->token)->first();

            if (!$temp_booking) {
                return Response::error([__('Something went wrong! Please try again')], [], 400);
            }

            $temp_data = json_decode(json_encode($temp_booking->data), true);

            $temp_data['car_id'] = $validated['car_id'];
            $temp_data['car_slug'] = $validated['car_slug'];
            $temp_data['location'] = $validated['location'];
            $temp_data['rental_days'] = $validated['rental_days'];
            $temp_data['pickup_location'] = $validated['pickup_location'] ?? null;
            $temp_data['credentials'] = $validated['credentials'];
            $temp_data['mobile'] = $validated['mobile'];
            $temp_data['round_pickup_date'] = $validated['round_pickup_date'];
            $temp_data['round_pickup_time'] = $validated['round_pickup_time'];
            $temp_data['message'] = $validated['message'];
            $temp_data['payment'] = $validated['payment'];
            $temp_data['token'] = $validated['token'];
            $temp_data['user_id'] = $validated['user_id'];

            $temp_booking->update([
                'data' => $temp_data,
            ]);

            $request->merge(['amount' => $validated['fees'], 'token' => $request->token]);

            if ($request->gateway_type === PaymentGatewayConst::AUTOMATIC) {
                return $this->automaticSubmit($request);
            } else {
                $validator = Validator::make($request->all(), [
                    'transaction_id' => 'required',
                ]);

                if ($validator->fails()) {
                    return Response::error($validator->errors()->all(), []);
                }
                return $this->manualSubmit($request);
            }
        }
    }

    public function bookingConfirm($data, $type, $trx_id, $request = null)
    {
        $temp_booking = TemporaryData::where('identifier', $data['token'])->first();
        $basic_setting = BasicSettings::first();
        if (!$temp_booking) {
            return Response::error([__('Something went wrong! Please try again')], [], 400);
        }
        $temp_data = json_decode(json_encode($temp_booking->data), true);

        // Convert time formats to 24-hour format if needed
        if (isset($temp_data['pickup_time'])) {
            $temp_data['pickup_time'] = $this->convertTo24HourFormat($temp_data['pickup_time']);
        }
        if (isset($temp_data['round_pickup_time']) && !empty($temp_data['round_pickup_time'])) {
            $temp_data['round_pickup_time'] = $this->convertTo24HourFormat($temp_data['round_pickup_time']);
        }
        if (isset($data['pickup_time'])) {
            $data['pickup_time'] = $this->convertTo24HourFormat($data['pickup_time']);
        }
        if (isset($data['round_pickup_time']) && !empty($data['round_pickup_time'])) {
            $data['round_pickup_time'] = $this->convertTo24HourFormat($data['round_pickup_time']);
        }

        // GOLDEN RULE: Recalculate everything in backend - NEVER trust frontend values
        $car = Car::with('carModel')->findOrFail($data['car_id']);

        // ── Delivery radius enforcement (server-side security guard) ──────────────
        // This runs before any pricing so that pickup coords and delivery flag are
        // authoritative before the booking record is written.
        $branch = $car->branch;
        if ($branch) {
            if (!$branch->delivery_enabled) {
                // Branch does not offer delivery: force pickup at the branch itself.
                $data['pickup_location'] = [
                    'latitude'  => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'address'   => (string) ($branch->address ?? ''),
                ];
                if ($request) {
                    $request->merge(['include_delivery' => false]);
                }
            } elseif ($request && $request->include_delivery) {
                // Branch allows delivery — validate the user's chosen location.
                $deliveryLat = isset($data['pickup_location']['latitude'])
                    ? floatval($data['pickup_location']['latitude']) : null;
                $deliveryLng = isset($data['pickup_location']['longitude'])
                    ? floatval($data['pickup_location']['longitude']) : null;

                if ($deliveryLat !== null && $deliveryLng !== null) {
                    $radiusCheck = app(\App\Services\DeliveryRadiusService::class)
                        ->checkDeliveryEligibility($branch->id, $deliveryLat, $deliveryLng);

                    if (!$radiusCheck['allowed']) {
                        return Response::error(
                            [__('Selected location is outside the branch delivery area.')],
                            [
                                'distance_km'   => $radiusCheck['distance_km'],
                                'max_radius_km' => $radiusCheck['max_radius'],
                            ],
                            422
                        );
                    }
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────────────

        $balanceService = new BookingBalanceService();

        // Calculate rental fees based on tiered pricing
        $rentalDays = intval($data['rental_days']);
        $rentalCalculation = $balanceService->calculateRentalFees($car, $rentalDays);
        $rentalFees = $rentalCalculation['rental_fees'];

        $deliveryPrice = ($request && $request->include_delivery) ? floatval($request->delivery_price ?? 0) : 0;

        $charges = 0;
        if ($type === 'cash') {
            $chargesSetting = TransactionSetting::where('slug', 'cash')->first();
            if ($chargesSetting) {
                $fixed_charge_calc = $chargesSetting->fixed_charge;
                $percent_charge_calc = (($rentalFees / 100) * $chargesSetting->percent_charge);
                $charges = $fixed_charge_calc + $percent_charge_calc;
            }
        }

        $pricingBreakdown = $balanceService->calculateBookingTotal($rentalFees, $deliveryPrice, $charges);

        try {
            // Extract pickup location data
            $pickupLatitude = isset($data['pickup_location']['latitude']) ? floatval($data['pickup_location']['latitude']) : null;
            $pickupLongitude = isset($data['pickup_location']['longitude']) ? floatval($data['pickup_location']['longitude']) : null;
            $pickupAddress = isset($data['pickup_location']['address']) ? $data['pickup_location']['address'] : null;

            $booking_data = CarBooking::create([
                'car_id' => $data['car_id'],
                'user_id' => $data['user_id'],
                'slug' => $data['car_slug'],
                'trx_id' => $trx_id,
                'payment_type' => $type,
                'car_model' => $car->carModel?->name ?? $car->car_model,
                'car_number' => $car->car_number,
                'phone' => $data['mobile'],
                'email' => $data['credentials'],
                'location' => $data['location'],
                'destination' => $data['destination'] ?? null,
                'distance' => $data['distance'] ?? 0,
                'pickup_latitude' => $pickupLatitude,
                'pickup_longitude' => $pickupLongitude,
                'pickup_address' => $pickupAddress,
                'rental_days' => $rentalDays,
                'allowance_km' => null, // User doesn't set this - vendor only
                'trip_id' => generate_unique_code(),
                'pickup_time' => $temp_data['pickup_time'],
                'pickup_date' => $temp_data['pickup_date'],
                'round_pickup_time' => $data['round_pickup_time'],
                'round_pickup_date' => $data['round_pickup_date'],
                'amount' => $rentalFees,
                'charges' => $charges,
                'delivery_fee' => $deliveryPrice,
                'is_delivery' => ($deliveryPrice > 0),
                'subtotal' => $pricingBreakdown['subtotal'],
                'tax_percentage' => $pricingBreakdown['tax_percentage'],
                'tax_amount' => $pricingBreakdown['tax_amount'],
                'total_amount' => $pricingBreakdown['total'],
                'message' => $data['message'] ?? '',
                'status' => 1,
            ]);

            // Record rental transaction with effective daily rate
            $rentalDailyRate = $rentalDays > 0 ? round($rentalFees / $rentalDays, 2) : null;
            CarBookingTransaction::create([
                'car_booking_id'           => $booking_data->id,
                'type'                     => CarBookingTransaction::TYPE_RENTAL,
                'description'              => "{$rentalDays} days rental",
                'amount'                   => $rentalFees,
                'tax_percentage'           => $pricingBreakdown['tax_percentage'],
                'tax_amount'               => $pricingBreakdown['tax_amount'],
                'total'                    => $pricingBreakdown['total'],
                'daily_rate'               => $rentalDailyRate,
                'transacted_at'            => now(),
            ]);

            if ($deliveryPrice > 0) {
                CarBookingTransaction::create([
                    'car_booking_id'           => $booking_data->id,
                    'type'                     => CarBookingTransaction::TYPE_DELIVERY,
                    'description'              => 'Delivery service',
                    'amount'                   => $deliveryPrice,
                    'tax_percentage'           => 0,
                    'tax_amount'               => 0,
                    'total'                    => $deliveryPrice,
                    'daily_rate'               => null,
                    'transacted_at'            => now(),
                ]);
            }

            $confirm_booking = CarBooking::with('cars')
                ->where('slug', $booking_data->slug)
                ->first();
            // $temp_booking->delete();

            $this->bookingNotification($confirm_booking, $basic_setting);

            return Response::success([__('Booking Successful!')], [
                'booking_id' => $booking_data->id,
                'trx_id' => $trx_id,
                'total_amount' => $pricingBreakdown['total'],
            ], 200);
        } catch (Exception $e) {
            Log::error('CarBooking Confirm Error: ' . $e->getMessage(), [
                'data' => $data ?? null,
                'type' => $type ?? null,
                'trx_id' => $trx_id ?? null,
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'sql' => DB::getQueryLog(),
            ]);

            // Check for specific null value errors
            if (
                strpos($e->getMessage(), 'must not be accessed before initialization') !== false ||
                strpos($e->getMessage(), 'Null check operator') !== false
            ) {
                return Response::error([
                    __('Missing required data. Please check all fields are filled.'),
                ], [
                    'error_type' => 'null_value_error',
                    'error_details' => $e->getMessage(),
                    'missing_fields' => $this->identifyNullFields($data),
                ], 400);
            }

            return Response::error([__('Something went wrong! Please try again')], [
                'error_details' => $e->getMessage(),
                'error_type' => 'booking_creation_error',
            ], 400);
        }
    }

    /**
     * Confirm booking with balance payment
     */
    public function bookingConfirmWithBalance($data, $trx_id, $request)
    {
        $temp_booking = TemporaryData::where('identifier', $data['token'])->first();
        $basic_setting = BasicSettings::first();
        $user = Auth::guard('api')->user();

        if (!$temp_booking) {
            return Response::error([__('Something went wrong! Please try again')], [], 400);
        }
        $temp_data = json_decode(json_encode($temp_booking->data), true);

        // Convert time formats to 24-hour format if needed
        if (isset($temp_data['pickup_time'])) {
            $temp_data['pickup_time'] = $this->convertTo24HourFormat($temp_data['pickup_time']);
        }
        if (isset($temp_data['round_pickup_time']) && !empty($temp_data['round_pickup_time'])) {
            $temp_data['round_pickup_time'] = $this->convertTo24HourFormat($temp_data['round_pickup_time']);
        }
        if (isset($data['pickup_time'])) {
            $data['pickup_time'] = $this->convertTo24HourFormat($data['pickup_time']);
        }
        if (isset($data['round_pickup_time']) && !empty($data['round_pickup_time'])) {
            $data['round_pickup_time'] = $this->convertTo24HourFormat($data['round_pickup_time']);
        }

        // GOLDEN RULE: Recalculate everything in backend - NEVER trust frontend values
        $car = Car::with('carModel')->findOrFail($data['car_id']);

        // ── Delivery radius enforcement (server-side security guard) ──────────────
        $branch = $car->branch;
        if ($branch) {
            if (!$branch->delivery_enabled) {
                // Branch does not offer delivery: force pickup at the branch itself.
                $data['pickup_location'] = [
                    'latitude'  => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'address'   => (string) ($branch->address ?? ''),
                ];
                $request->merge(['include_delivery' => false]);
            } elseif ($request->include_delivery) {
                // Branch allows delivery — validate the user's chosen location.
                $deliveryLat = isset($data['pickup_location']['latitude'])
                    ? floatval($data['pickup_location']['latitude']) : null;
                $deliveryLng = isset($data['pickup_location']['longitude'])
                    ? floatval($data['pickup_location']['longitude']) : null;

                if ($deliveryLat !== null && $deliveryLng !== null) {
                    $radiusCheck = app(\App\Services\DeliveryRadiusService::class)
                        ->checkDeliveryEligibility($branch->id, $deliveryLat, $deliveryLng);

                    if (!$radiusCheck['allowed']) {
                        return Response::error(
                            [__('Selected location is outside the branch delivery area.')],
                            [
                                'distance_km'   => $radiusCheck['distance_km'],
                                'max_radius_km' => $radiusCheck['max_radius'],
                            ],
                            422
                        );
                    }
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────────────

        $balanceService = new BookingBalanceService();

        // Calculate rental fees based on tiered pricing
        $rentalDays = intval($data['rental_days']);
        $rentalCalculation = $balanceService->calculateRentalFees($car, $rentalDays);
        $rentalFees = $rentalCalculation['rental_fees'];

        $deliveryPrice = $request->include_delivery ? floatval($request->delivery_price ?? 0) : 0;

        $pricingBreakdown = $balanceService->calculateBookingTotal($rentalFees, $deliveryPrice);
        $totalAmount = $pricingBreakdown['total'];

        // Check if user has sufficient balance
        if (!$balanceService->hasSufficientBalance($user, $totalAmount)) {
            return Response::error([
                __('Insufficient balance. Your balance is :balance, required amount is :required', [
                    'balance'  => number_format($user->balance, 2),
                    'required' => number_format($totalAmount, 2),
                ])
            ], [
                'current_balance' => (float) round($user->balance, 2),
                'required_amount' => (float) round($totalAmount, 2),
                'shortfall'       => (float) round($totalAmount - $user->balance, 2),
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Extract pickup location data
            $pickupLatitude = isset($data['pickup_location']['latitude']) ? floatval($data['pickup_location']['latitude']) : null;
            $pickupLongitude = isset($data['pickup_location']['longitude']) ? floatval($data['pickup_location']['longitude']) : null;
            $pickupAddress = isset($data['pickup_location']['address']) ? $data['pickup_location']['address'] : null;

            // Create booking
            $booking_data = CarBooking::create([
                'car_id' => $data['car_id'],
                'user_id' => $data['user_id'],
                'slug' => $data['car_slug'],
                'trx_id' => $trx_id,
                'payment_type' => 'balance',
                'car_model' => $car->carModel?->name ?? $car->car_model,
                'car_number' => $car->car_number,
                'phone' => $data['mobile'],
                'email' => $data['credentials'],
                'location' => $data['location'],
                'destination' => $data['destination'] ?? null,
                'distance' => $data['distance'] ?? 0,
                'pickup_latitude' => $pickupLatitude,
                'pickup_longitude' => $pickupLongitude,
                'pickup_address' => $pickupAddress,
                'rental_days' => $rentalDays,
                'allowance_km' => null, // User doesn't set this - vendor only
                'trip_id' => generate_unique_code(),
                'pickup_time' => $temp_data['pickup_time'],
                'pickup_date' => $temp_data['pickup_date'],
                'round_pickup_time' => $data['round_pickup_time'],
                'round_pickup_date' => $data['round_pickup_date'],
                'amount' => $rentalFees,
                'charges' => 0,
                'delivery_fee' => $deliveryPrice,
                'is_delivery' => ($deliveryPrice > 0),
                'subtotal' => $pricingBreakdown['subtotal'],
                'tax_percentage' => $pricingBreakdown['tax_percentage'],
                'tax_amount' => $pricingBreakdown['tax_amount'],
                'total_amount' => $totalAmount,
                'paid_from_balance' => true,
                'balance_deducted' => $totalAmount,
                'message' => $data['message'] ?? '',
                'status' => 1,
            ]);

            // Record rental transaction with effective daily rate
            $rentalDailyRate = $rentalDays > 0 ? round($rentalFees / $rentalDays, 2) : null;
            CarBookingTransaction::create([
                'car_booking_id'           => $booking_data->id,
                'type'                     => CarBookingTransaction::TYPE_RENTAL,
                'description'              => "{$rentalDays} days rental",
                'amount'                   => $rentalFees,
                'tax_percentage'           => $pricingBreakdown['tax_percentage'],
                'tax_amount'               => $pricingBreakdown['tax_amount'],
                'total'                    => $totalAmount,
                'daily_rate'               => $rentalDailyRate,
                'transacted_at'            => now(),
            ]);

            if ($deliveryPrice > 0) {
                CarBookingTransaction::create([
                    'car_booking_id'           => $booking_data->id,
                    'type'                     => CarBookingTransaction::TYPE_DELIVERY,
                    'description'              => 'Delivery service',
                    'amount'                   => $deliveryPrice,
                    'tax_percentage'           => 0,
                    'tax_amount'               => 0,
                    'total'                    => $deliveryPrice,
                    'daily_rate'               => null,
                    'transacted_at'            => now(),
                ]);
            }

            // Deduct balance
            $balanceService->deductBalanceForBooking($user, $booking_data, $totalAmount);

            DB::commit();

            $confirm_booking = CarBooking::with('cars')
                ->where('slug', $booking_data->slug)
                ->first();

            $this->bookingNotification($confirm_booking, $basic_setting);
            $this->notifyUserOnBookingConfirmed($user, $confirm_booking, $basic_setting);

            return Response::success([__('Booking Successful! Amount deducted from your balance.')], [
                'booking_id' => $booking_data->id,
                'trx_id' => $trx_id,
                'amount_deducted' => $totalAmount,
                'new_balance' => (float) round($user->fresh()->balance, 2),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CarBooking Balance Payment Error: ' . $e->getMessage(), [
                'data' => $data ?? null,
                'trx_id' => $trx_id ?? null,
                'user_id' => $user->id ?? null,
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            // Check for type casting errors
            if (strpos($e->getMessage(), 'is not a subtype of type') !== false) {
                return Response::error([
                    __('Data type mismatch. Please contact support.'),
                ], [
                    'error_type' => 'type_casting_error',
                    'error_details' => $e->getMessage(),
                ], 400);
            }

            // Check for null value errors
            if (
                strpos($e->getMessage(), 'must not be accessed before initialization') !== false ||
                strpos($e->getMessage(), 'Null check operator') !== false
            ) {
                return Response::error([
                    __('Missing required data. Please check all fields are filled.'),
                ], [
                    'error_type' => 'null_value_error',
                    'error_details' => $e->getMessage(),
                    'missing_fields' => $this->identifyNullFields($data),
                ], 400);
            }

            return Response::error([__('Booking failed. Please try again.')], [
                'error_details' => $e->getMessage(),
                'error_type' => 'booking_creation_error',
            ], 400);
        }
    }

    public function bookingNotification($confirm_booking, $basic_setting)
    {
        if (auth()->check()) {
            $notification_content = [
                'title' => __('Booking', [], 'ar'),
                'message' => __('Your have a incoming booking request (Car Model: :model, Car Number: :number, Pick-up Date: :date, Pick-up Time: :time).', [
                    'model'  => $confirm_booking->cars->car_model,
                    'number' => $confirm_booking->cars->car_number,
                    'date'   => $confirm_booking->pickup_date ? Carbon::parse($confirm_booking->pickup_date)->format('d-m-Y') : '',
                    'time'   => $confirm_booking->pickup_time ? Carbon::parse($confirm_booking->pickup_time)->format('h:i A') : '',
                ], 'ar'),
            ];
            VendorNotification::create([
                'vendor_id' => $confirm_booking->cars->vendor_id,
                'message' => $notification_content,
            ]);
            try {
                if ($basic_setting->email_notification) {
                    Notification::route('mail', $confirm_booking->email)->notify(new CarBookingNotification($confirm_booking));
                }
                if ($basic_setting->vendor_push_notification) {
                    (new PushNotificationHelper())
                        ->prepare(
                            [$confirm_booking->cars->vendor_id],
                            [
                                'title' => $notification_content['title'],
                                'desc' => $notification_content['message'],
                                'user_type' => 'vendor',
                            ],
                        )
                        ->send();
                }
            } catch (Exception $e) {
            }
        }
    }

    public function automaticSubmit(Request $request)
    {
        try {
            $instance = PaymentGatewayHelper::init($request->all())
                ->type(PaymentGatewayConst::TYPEADDMONEY)
                ->setProjectCurrency(PaymentGatewayConst::PROJECT_CURRENCY_SINGLE)
                ->gateway()
                ->api()
                ->render();
        } catch (Exception $e) {
            return Response::error([$e->getMessage()], [], 500);
        }

        if ($instance instanceof RedirectResponse === false && isset($instance['gateway_type']) && $instance['gateway_type'] == PaymentGatewayConst::MANUAL) {
            return Response::error([__("Can't submit manual gateway in automatic link")], [], 400);
        }

        return Response::success(
            [__('Payment gateway response successful')],
            [
                'redirect_url' => $instance['redirect_url'],
                'redirect_links' => $instance['redirect_links'],
                'action_type' => $instance['type'] ?? false,
                'address_info' => $instance['address_info'] ?? [],
                'identifier'   => $instance['identifier'] ?? '',
            ],
            200,
        );
    }

    public function success(Request $request, $gateway)
    {
        try {
            sleep(2);
            $token = PaymentGatewayHelper::getToken($request->all(), $gateway);
            $temp_data = TemporaryData::where('type', PaymentGatewayConst::TYPEADDMONEY)
                ->where('identifier', $token)
                ->first();

            if (!$temp_data) {
                if (Transaction::where('callback_ref', $token)->exists()) {
                    // return Response::success([__('Transaction request sended successfully!')], [], 400);

                } else {
                    return Response::error([__("Transaction failed. Record didn't saved properly. Please try again")], [], 400);
                }
            }

            $update_temp_data = json_decode(json_encode($temp_data->data), true);
            $update_temp_data['callback_data'] = $request->all();
            $temp_data->update([
                'data' => $update_temp_data,
            ]);
            $temp_data = $temp_data->toArray();
            $instance = PaymentGatewayHelper::init($temp_data)
                ->type(PaymentGatewayConst::TYPEADDMONEY)
                ->setProjectCurrency(PaymentGatewayConst::PROJECT_CURRENCY_SINGLE)
                ->responseReceive();

            $transaction_info = Transaction::where('booking_token', $temp_data['data']->booking_token)->first();
            $booking_info = TemporaryData::where('identifier', $temp_data['data']->booking_token)->first();
            $booking_info = json_decode(json_encode($booking_info->data), true);


            $car_booking = CarBooking::where('trx_id', $transaction_info->trx_id)->first();
            if (!$car_booking) {
                $this->bookingConfirm($booking_info, 'online-payment', $transaction_info->trx_id);
            }

            // return $instance;
        } catch (Exception $e) {
            return Response::error([$e->getMessage()], [], 500);
        }
        return Response::success([__('Car Booked successfully!')], [], 200);
    }

    public function cancel(Request $request, $gateway)
    {
        $token = PaymentGatewayHelper::getToken($request->all(), $gateway);
        $temp_data = TemporaryData::where('type', PaymentGatewayConst::TYPEADDMONEY)
            ->where('identifier', $token)
            ->first();
        try {
            if ($temp_data != null) {
                $temp_data->delete();
            }
        } catch (Exception $e) {
            // Handel error
        }
        return Response::error([__('Payment process cancel successfully!')], [], 400);
    }

    public function postSuccess(Request $request, $gateway)
    {
        try {
            $token = PaymentGatewayHelper::getToken($request->all(), $gateway);
            $temp_data = TemporaryData::where('type', PaymentGatewayConst::TYPEADDMONEY)
                ->where('identifier', $token)
                ->first();
            if ($temp_data && $temp_data->data->creator_guard != 'api') {
                Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
            }
        } catch (Exception $e) {
            return Response::error([$e->getMessage()]);
        }

        return $this->success($request, $gateway);
    }

    public function postCancel(Request $request, $gateway)
    {
        try {
            $token = PaymentGatewayHelper::getToken($request->all(), $gateway);
            $temp_data = TemporaryData::where('type', PaymentGatewayConst::TYPEADDMONEY)
                ->where('identifier', $token)
                ->first();
            if ($temp_data && $temp_data->data->creator_guard != 'api') {
                Auth::guard($temp_data->data->creator_guard)->loginUsingId($temp_data->data->creator_id);
            }
        } catch (Exception $e) {
            return Response::error([$e->getMessage()]);
        }

        return $this->cancel($request, $gateway);
    }

    public function manualInputFields(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string|exists:payment_gateway_currencies',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $validated = $validator->validate();
        $gateway_currency = PaymentGatewayCurrency::where('alias', $validated['alias'])->first();

        $gateway = $gateway_currency->gateway;

        if (!$gateway->isManual()) {
            return Response::error([__("Can't get fields. Requested gateway is automatic")], [], 400);
        }

        if (!$gateway->input_fields || !is_array($gateway->input_fields)) {
            return Response::error([__('This payment gateway is under constructions. Please try with another payment gateway')], [], 503);
        }

        try {
            $input_fields = json_decode(json_encode($gateway->input_fields), true);
            $input_fields = array_reverse($input_fields);
        } catch (Exception $e) {
            return Response::error([__('Something went wrong! Please try again')], [], 500);
        }

        return Response::success(
            [__('Payment gateway input fields fetch successfully!')],
            [
                'gateway' => [
                    'desc' => $gateway->desc,
                ],
                'input_fields' => $input_fields,
                'currency' => $gateway_currency->only(['alias']),
            ],
            200,
        );
    }

    public function manualSubmit(Request $request)
    {
        try {
            $instance = PaymentGatewayHelper::init($request->only(['gateway_currency', 'amount']))
                ->setProjectCurrency(PaymentGatewayConst::PROJECT_CURRENCY_SINGLE)
                ->gateway()
                ->get();
        } catch (Exception $e) {
            return Response::error([$e->getMessage()], [], 401);
        }

        // Check it's manual or automatic
        if (!isset($instance['gateway_type']) || $instance['gateway_type'] != PaymentGatewayConst::MANUAL) {
            return Response::error([__("Can't submit automatic gateway in manual link")], [], 400);
        }

        $gateway = $instance['gateway'] ?? null;
        $gateway_currency = $instance['currency'] ?? null;
        if (!$gateway || !$gateway_currency || !$gateway->isManual()) {
            return Response::error([__('Selected gateway is invalid')], [], 400);
        }

        $amount = $instance['amount'] ?? null;
        if (!$amount) {
            return Response::error([__('Transaction Failed. Failed to save information. Please try again')], [], 400);
        }

        $this->file_store_location = 'transaction';
        $dy_validation_rules = $this->generateValidationRules($gateway->input_fields);

        $validator = Validator::make($request->all(), $dy_validation_rules);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }
        $validated = $validator->validate();
        $get_values = $this->placeValueWithFields($gateway->input_fields, $validated);
        $trx_id = generate_unique_string('transactions', 'trx_id', 16);

        // Make Transaction
        DB::beginTransaction();
        try {
            $id = DB::table('transactions')->insertGetId([
                'type' => PaymentGatewayConst::TYPEADDMONEY,
                'trx_id' => $trx_id,
                'user_type' => GlobalConst::USER,
                'user_id' => auth()->user()->id,
                'payment_gateway_currency_id' => $gateway_currency->id,
                'request_amount' => $amount->requested_amount,
                'request_currency' => get_default_currency_code(),
                'exchange_rate' => $gateway_currency->rate,
                'percent_charge' => $amount->percent_charge,
                'fixed_charge' => $amount->fixed_charge,
                'total_charge' => $amount->total_charge,
                'total_payable' => $amount->total_amount,
                'receive_amount' => $amount->will_get,
                'available_balance' => 0,
                'booking_token' => $request->token,
                'payment_currency' => $gateway_currency->currency_code,
                'details' => json_encode(['input_values' => $get_values]),
                'status' => PaymentGatewayConst::STATUSPENDING,
                'created_at' => now(),
            ]);

            DB::commit();

            $booking_info = TemporaryData::where('identifier', $request->token)->first();
            $booking_info = json_decode(json_encode($booking_info->data), true);
            $this->bookingConfirm($booking_info, 'online-payment', $trx_id);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error([__('Something went wrong! Please try again')], [], 500);
        }
        return Response::success([__('Transaction Success. Please wait for admin confirmation')], [], 200);
    }

    public function gatewayAdditionalFields(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|exists:payment_gateway_currencies,alias',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }
        $validated = $validator->validate();

        $gateway_currency = PaymentGatewayCurrency::where('alias', $validated['currency'])->first();

        $gateway = $gateway_currency->gateway;

        $data['available'] = false;
        $data['additional_fields'] = [];
        if (Gpay::isGpay($gateway)) {
            $gpay_bank_list = Gpay::getBankList();
            if ($gpay_bank_list == false) {
                return Response::error([__('Gpay bank list server response failed! Please try again')], [], 500);
            }
            $data['available'] = true;

            $gpay_bank_list_array = json_decode(json_encode($gpay_bank_list), true);

            $gpay_bank_list_array = array_map(function ($array) {
                $data['name'] = $array['short_name_by_gpay'];
                $data['value'] = $array['gpay_bank_code'];

                return $data;
            }, $gpay_bank_list_array);

            $data['additional_fields'][] = [
                'type' => 'select',
                'label' => 'Select Bank',
                'title' => 'Select Bank',
                'name' => 'bank',
                'values' => $gpay_bank_list_array,
            ];
        }

        return Response::success([__('Request response fetch successfully!')], $data, 200);
    }

    public function cryptoPaymentConfirm(Request $request, $trx_id)
    {
        $transaction = Transaction::where('trx_id', $trx_id)
            ->where('status', PaymentGatewayConst::STATUSWAITING)
            ->firstOrFail();

        $dy_input_fields = $transaction->details->payment_info->requirements ?? [];
        $validation_rules = $this->generateValidationRules($dy_input_fields);

        $validated = [];
        if (count($validation_rules) > 0) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        }

        if (!isset($validated['txn_hash'])) {
            return Response::error([__('Transaction hash is required for verify')]);
        }

        $receiver_address = $transaction->details->payment_info->receiver_address ?? '';

        // check hash is valid or not
        $crypto_transaction = CryptoTransaction::where('txn_hash', $validated['txn_hash'])
            ->where('receiver_address', $receiver_address)
            ->where('asset', $transaction->gateway_currency->currency_code)
            ->where(function ($query) {
                return $query->where('transaction_type', 'Native')->orWhere('transaction_type', 'native');
            })
            ->where('status', PaymentGatewayConst::NOT_USED)
            ->first();

        if (!$crypto_transaction) {
            return Response::error([__('Transaction hash is not valid! Please input a valid hash')], [], 404);
        }

        if ($crypto_transaction->amount >= $transaction->total_payable == false) {
            if (!$crypto_transaction) {
                Response::error([__('Insufficient amount added. Please contact with system administrator')], [], 400);
            }
        }

        DB::beginTransaction();
        try {

            // update crypto transaction as used
            DB::table($crypto_transaction->getTable())
                ->where('id', $crypto_transaction->id)
                ->update([
                    'status' => PaymentGatewayConst::USED,
                ]);

            // update transaction status
            $transaction_details = json_decode(json_encode($transaction->details), true);
            $transaction_details['payment_info']['txn_hash'] = $validated['txn_hash'];

            DB::table($transaction->getTable())
                ->where('id', $transaction->id)
                ->update([
                    'details' => json_encode($transaction_details),
                    'status' => PaymentGatewayConst::STATUSSUCCESS,
                ]);

            $this->bookingConfirm($transaction->booking_token, 'online-payment', $transaction->id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return Response::error([__('Something went wrong! Please try again')], [], 500);
        }

        return Response::success([__('Payment Confirmation Success!')], [], 200);
    }

    /**
     * Redirect Users for collecting payment via Button Pay (JS Checkout)
     */
    public function redirectBtnPay(Request $request, $gateway)
    {
        try {
            return PaymentGatewayHelper::init([])
                ->setProjectCurrency(PaymentGatewayConst::PROJECT_CURRENCY_SINGLE)
                ->handleBtnPay($gateway, $request->all());
        } catch (Exception $e) {
            return Response::error([$e->getMessage()], [], 500);
        }
    }

    // manual re-payment
    public function repaymentSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx_id' => 'required|string|exists:transactions',
        ]);

        if ($validator->fails()) {
            return Response::error([$validator->errors()->all()], []);
        }

        $trx_id = $request->trx_id;

        $transaction = Transaction::where('trx_id', $trx_id)->first();

        if (!$transaction || !isset($transaction->payment_gateway_currency_id)) {
            return Response::error([__('Invalid request')], []);
        }

        $gateway_currency = PaymentGatewayCurrency::find($transaction->payment_gateway_currency_id);

        if (!$gateway_currency || !$gateway_currency->gateway->isManual()) {
            return Response::error([__('Selected gateway is invalid')], []);
        }

        $gateway = $gateway_currency->gateway;
        $amount = $transaction->total_payable ?? null;
        if (!$amount) {
            return Response::error([__('Transaction Failed. Failed to save information. Please try again')], []);
        }

        $this->file_store_location = 'transaction';
        $dy_validation_rules = $this->generateValidationRules($gateway->input_fields);

        $validated = Validator::make($request->all(), $dy_validation_rules)->validate();
        $get_values = $this->placeValueWithFields($gateway->input_fields, $validated);

        foreach ($transaction->details->input_values as $key => $value) {
            if ($value->type == 'file') {
                unlink('public/transaction/' . $value->value);
            }
        }
        DB::beginTransaction();
        try {
            $id = DB::table('transactions')
                ->where('trx_id', $trx_id)
                ->update([
                    'details' => json_encode(['input_values' => $get_values]),
                    'status' => PaymentGatewayConst::STATUSPENDING,
                ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error([__('Something went wrong! Please try again')], []);
        }
        return Response::success([__('Transaction Success. Please wait for admin confirmation')], []);
    }



    public function reManualInputFields(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx_id' => 'required|exists:transactions',
        ]);

        if ($validator->fails()) {
            return Response::error([$validator->errors()->all()], []);
        }

        $validated = $validator->validate();

        $transaction = Transaction::where('trx_id', $validated['trx_id'])->first();

        $currency = $transaction->gateway_currency->alias;

        $validated = $validator->validate();
        $gateway_currency = PaymentGatewayCurrency::where('alias', $currency)->first();

        $gateway = $gateway_currency->gateway;

        if (!$gateway->isManual()) {
            return Response::error([__("Can't get fields. Requested gateway is automatic")], [], 400);
        }

        if (!$gateway->input_fields || !is_array($gateway->input_fields)) {
            return Response::error([__('This payment gateway is under constructions. Please try with another payment gateway')], [], 503);
        }

        try {
            $input_fields = json_decode(json_encode($gateway->input_fields), true);
            $input_fields = array_reverse($input_fields);
        } catch (Exception $e) {
            return Response::error([__('Something went wrong! Please try again')], [], 500);
        }

        return Response::success(
            [__('Payment gateway input fields fetch successfully!')],
            [
                'reject-reason' => $transaction->reject_reason,
                'gateway' => [
                    'desc' => $gateway->desc,
                ],
                'input_fields' => $input_fields,
                'currency' => $gateway_currency->only(['alias']),
            ],
            200,
        );
    }

    /**
     * Method function authorize payment submit
     * @param Illuminate\Http\Request $request
     */
    public function authorizePaymentSubmit(Request $request)
    {
        $validator          = Validator::make($request->all(), [
            'identifier'    => 'required',
            'card_number'   => 'required',
            'date'          => 'required',
            'code'          => 'required'
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }
        $validated          = $validator->validate();
        $temp_data          = TemporaryData::where('identifier', $validated['identifier'])->first();
        if (!$temp_data) {
            return Response::error([__('Sorry ! Data not found.')], [], 400);
        }

        $gateway_credentials          = $this->authorizeCredentials($temp_data);
        $basic_settings               = BasicSettings::first();

        $validated['card_number']     = str_replace(' ', '', $validated['card_number']);

        $convert_date   = explode('/', $validated['date']);
        $month          = $convert_date[1];
        $year           = $convert_date[0];

        $current_year   = substr(date('Y'), 0, 2);
        $full_year      = $current_year . $year;

        $validated['date'] = $full_year . '-' . $month;
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($gateway_credentials->app_login_id);
        $merchantAuthentication->setTransactionKey($gateway_credentials->transaction_key);
        $amount = $temp_data->data->amount->total_amount;


        // Set the transaction's refId
        $refId = 'ref' . time();

        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($validated['card_number']);
        $creditCard->setExpirationDate($validated['date']);
        $creditCard->setCardCode($validated['code']);


        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        $generate_invoice_number        = generate_random_string_number(10) . time();

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($generate_invoice_number);
        $order->setDescription("Add Money");

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName(auth()->user()->firstname);
        $customerAddress->setLastName(auth()->user()->lastname);
        $customerAddress->setCompany($basic_settings->site_name);
        $customerAddress->setAddress(auth()->user()->address->address ?? '');
        $customerAddress->setCity(auth()->user()->address->city ?? '');
        $customerAddress->setState(auth()->user()->address->state ?? '');
        $customerAddress->setZip(auth()->user()->address->zip ?? '');
        $customerAddress->setCountry(auth()->user()->address->country ?? '');

        $make_customer_id       = auth()->user()->id . $gateway_credentials->code;
        // Set the customer's identifying information
        $customerData = new AnetAPI\CustomerDataType();
        $customerData->setType("individual");
        $customerData->setId($make_customer_id);
        $customerData->setEmail(auth()->user()->email);

        // Add values for transaction settings
        $duplicateWindowSetting = new AnetAPI\SettingType();
        $duplicateWindowSetting->setSettingName("duplicateWindow");
        $duplicateWindowSetting->setSettingValue("60");

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setCustomer($customerData);
        $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);

        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);


        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);

        if ($gateway_credentials->mode == GlobalConst::ENV_SANDBOX) {
            $environment = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        } else {
            $environment = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        }
        /** @var \net\authorize\api\contract\v1\CreateTransactionResponse $response */
        $response   = $controller->executeWithApiResponse($environment);
        if ($response != null) {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $trx_id = generate_unique_string("transactions", "trx_id", 16);
                    $status = PaymentGatewayConst::STATUSSUCCESS;

                    try {
                        $this->createTransactionAuthorize($trx_id, $temp_data, $status);
                    } catch (Exception $e) {
                        return Response::error([__('Booking Field Please Try Again')], [], 400);
                    }
                    return Response::success([__('Car Booked successfully!')], [], 200);
                } else {
                    return Response::error([__('Transaction Failed')], [], 400);
                    if ($tresponse->getErrors() != null) {
                        return Response::error([__($tresponse->getErrors()[0]->getErrorText())], [], 400);
                    }
                }
            } else {
                return Response::error([__('Transaction Failed')], [], 400);

                $tresponse = $response->getTransactionResponse();

                if ($tresponse != null && $tresponse->getErrors() != null) {
                    return Response::error([__($tresponse->getErrors()[0]->getErrorText())], [], 400);
                } else {
                    return Response::error([__($response->getMessages()->getMessage()[0]->getText())], [], 400);
                }
            }
        } else {
            return Response::error([__('No response returned')], [], 400);
        }
    }

    /**
     * Helper method to identify null or missing fields in data array
     */
    private function identifyNullFields($data)
    {
        $requiredFields = [
            'car_id',
            'user_id',
            'car_slug',
            'location',
            'rental_days',
            'credentials',
            'mobile',
            'token'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    // =========================================================================
    // Rental Extension
    // =========================================================================

    /**
     * POST /api/v1/user/car-booking/extend
     *
     * Extend an ONGOING rental by paying from wallet balance.
     * Auto-approved when the car has no conflicting bookings.
     */
    public function extendBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id'     => 'required|integer|exists:car_bookings,id',
            'extension_days' => 'required|integer|min:1|max:90',
            'payment_type'   => 'sometimes|in:cash,balance',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        $user        = auth()->user();
        $paymentType = $request->input('payment_type', 'cash');

        $booking = CarBooking::with(['cars', 'user'])
            ->where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return Response::error([__('Booking not found.')], [], 404);
        }

        try {
            $extensionService = app(CarBookingExtensionService::class);
            $extension = $extensionService->processExtension(
                $booking,
                (int) $request->extension_days,
                (int) $user->id,
                $paymentType,
                $user
            );

            // Reload booking with fresh extension data
            $booking->refresh();
            $booking->load(['cars', 'bookingExtensions']);

            // Notify user
            try {
                $user->notify(new BookingExtendedNotification($booking, $extension));
            } catch (\Throwable $e) {
                Log::warning('Extension notification failed: ' . $e->getMessage());
            }

            // Push notification to user
            try {
                (new PushNotificationHelper())
                    ->prepare(
                        [$user->id],
                        [
                            'title'     => __('Booking Extended', [], 'ar'),
                            'desc'      => __('Your booking has been extended by :days day(s). New return date: :date.', [
                                'days' => $extension->extension_days,
                                'date' => $extension->new_return_date->toDateString(),
                            ], 'ar'),
                            'user_type' => 'user',
                        ]
                    )
                    ->send();
            } catch (\Throwable $e) {
                Log::warning('Push notification for extension failed: ' . $e->getMessage());
            }

            $successMessage = $paymentType === 'balance'
                ? __('Booking extended successfully. Extension cost deducted from your wallet.')
                : __('Booking extended successfully. Extension cost will be collected on car return.');

            return Response::success([$successMessage], [
                'booking'   => new CarBookingResource($booking),
                'extension' => new CarBookingExtensionResource($extension),
            ], 200);
        } catch (\Exception $e) {
            Log::error('CarBooking Extension Error: ' . $e->getMessage(), [
                'booking_id'     => $request->booking_id,
                'extension_days' => $request->extension_days,
                'user_id'        => $user->id,
            ]);

            return Response::error([$e->getMessage()], [], 400);
        }
    }

    /**
     * GET /api/v1/user/car-booking/{bookingId}/extensions
     *
     * Return the full extension history for a specific booking.
     */
    public function extensionHistory(Request $request, $bookingId)
    {
        $user    = auth()->user();
        $booking = CarBooking::where('id', $bookingId)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return Response::error([__('Booking not found.')], [], 404);
        }

        $extensions = $booking->bookingExtensions()->get();

        return Response::success([__('Extension history retrieved.')], [
            'booking_id'           => (int) $booking->id,
            'trx_id'               => (string) $booking->trx_id,
            'extension_count'      => (int) $booking->extension_count,
            'total_extension_days' => (int) $booking->total_extension_days,
            'current_return_date'  => $booking->return_date ? $booking->return_date->toDateString() : null,
            'extensions'           => CarBookingExtensionResource::collection($extensions),
        ], 200);
    }

    /**
     * GET /api/v1/user/car-booking/extend/preview
     *
     * Preview the cost and dates for a rental extension without committing any changes.
     * Query params: booking_id, extension_days
     */
    public function previewExtension(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id'     => 'required|integer|exists:car_bookings,id',
            'extension_days' => 'required|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        $user    = auth()->user();
        $booking = CarBooking::with('cars')
            ->where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return Response::error([__('Booking not found.')], [], 404);
        }

        $extensionDays = (int) $request->extension_days;

        if ((int) $booking->status !== CarBookingConst::STATUONGOING) {
            return Response::error([__('Only ongoing bookings can be extended.')], [], 422);
        }

        $newTotalDays = (int) $booking->rental_days + $extensionDays;
        if ($newTotalDays > 365) {
            return Response::error([__('Total rental duration cannot exceed 365 days.')], [], 422);
        }

        $currentReturnDate = $booking->calculateReturnDate();
        $newReturnDate = $currentReturnDate->copy()->addDays($extensionDays);

        // Availability check (read-only — no lock needed for preview)
        $hasConflict = CarBooking::hasConflictForExtension(
            (int) $booking->car_id,
            $currentReturnDate->toDateString(),
            $newReturnDate->toDateString(),
            (int) $booking->id
        );

        // Pricing calculation
        $car            = $booking->cars;
        $balanceService = app(BookingBalanceService::class);
        $rentalFeeData  = $balanceService->calculateRentalFees($car, $extensionDays);
        $taxCalc        = $balanceService->calculateTax($rentalFeeData['rental_fees']);

        return Response::success([__('Extension preview calculated.')], [
            'booking_id'            => (int) $booking->id,
            'trx_id'                => (string) $booking->trx_id,
            'car_model'             => (string) $booking->car_model,
            'current_rental_days'   => (int) $booking->rental_days,
            'extension_days'        => $extensionDays,
            'new_total_rental_days' => $newTotalDays,
            'current_return_date'   => $currentReturnDate->toDateString(),
            'new_return_date'       => $newReturnDate->toDateString(),
            'is_available'          => !$hasConflict,
            'availability_message'  => $hasConflict
                ? __('Car is not available for the requested extension period.')
                : __('Car is available for extension.'),
            'pricing' => [
                'price_rule_applied' => (string) $rentalFeeData['price_rule_applied'],
                'base_price'         => (float)  $rentalFeeData['base_price'],
                'extension_days'     => $extensionDays,
                'rental_fees'        => (float)  $rentalFeeData['rental_fees'],
                'tax_percentage'     => (float)  $taxCalc['tax_percentage'],
                'tax_amount'         => (float)  $taxCalc['tax_amount'],
                'total_cost'         => (float)  $taxCalc['total'],
            ],
        ], 200);
    }

    /**
     * Cancel a pending booking and auto-refund wallet balance.
     *
     * POST /api/v1/user/car-booking/cancel
     * Body: { "booking_id": 123 }
     */
    public function cancelBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $user = Auth::guard('api')->user();

        $booking = CarBooking::where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return Response::error([__('Booking not found.')], [], 404);
        }

        // Only pending (status=0) or booked (status=1) bookings can be cancelled by the user
        if (!in_array((int) $booking->status, [0, 1])) {
            return Response::error([__('Only pending or unstarted bookings can be cancelled.')], [], 422);
        }

        DB::beginTransaction();
        try {
            $booking->update(['status' => 5]); // 5 = user_cancelled

            // Auto-refund if paid from wallet balance
            if ($booking->paid_from_balance && $booking->balance_deducted > 0) {
                $balanceService = new BookingBalanceService();
                $balanceService->refundBalance(
                    $user,
                    $booking,
                    (float) $booking->balance_deducted,
                    __('Booking cancelled by user', [], 'ar'),
                );
            }

            DB::commit();

            // Send booking-cancelled notification to user
            $this->notifyUserOnBookingCancelled($user, $booking);

            return Response::success([__('Booking cancelled successfully.')], [
                'booking_id' => $booking->id,
                'refunded'   => $booking->paid_from_balance ? number_format($booking->balance_deducted, 2) : '0.00',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Booking cancellation failed', [
                'booking_id' => $request->booking_id,
                'user_id'    => $user->id,
                'error'      => $e->getMessage(),
            ]);

            return Response::error([__('Failed to cancel booking. Please try again.')], [], 500);
        }
    }

    /**
     * POST /api/v1/user/car-booking/validate-datetime
     *
     * Check if a pickup date/time falls within the working hours of a car's branch.
     */
    public function validatePickupDateTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id'      => 'required|integer|exists:cars,id',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        $pickupTime = $this->convertTo24HourFormat($request->pickup_time);
        $pickupDate = Carbon::parse($request->pickup_date);
        $dayOfWeek  = $pickupDate->dayOfWeek; // 0=Sunday, 6=Saturday

        $car = Car::with(['branch'])->find($request->car_id);

        if (!$car) {
            return Response::error([__('Car not found.')], [], 404);
        }

        $branch = $car->branch;

        if (!$branch) {
            return Response::error([__('Car branch not found.')], [], 404);
        }

        $workingHour = $branch->workingHours()
            ->enabled()
            ->forDay($dayOfWeek)
            ->first();

        $dayName = BranchWorkingHour::DAY_NAMES_EN[$dayOfWeek] ?? '';

        if (!$workingHour) {
            return Response::error(
                [__('Branch is closed on :day.', ['day' => $dayName])],
                [
                    'is_valid'    => false,
                    'day_of_week' => $dayOfWeek,
                    'day_name'    => $dayName,
                ],
                422
            );
        }

        $timeFormatted = Carbon::parse($pickupTime)->format('H:i:s');

        if ($timeFormatted < $workingHour->open_time || $timeFormatted >= $workingHour->close_time) {
            return Response::error(
                [__('Pickup time must be between :open and :close on :day.', [
                    'open'  => Carbon::parse($workingHour->open_time)->format('h:i A'),
                    'close' => Carbon::parse($workingHour->close_time)->format('h:i A'),
                    'day'   => $dayName,
                ])],
                [
                    'is_valid'   => false,
                    'day_name'   => $dayName,
                    'open_time'  => Carbon::parse($workingHour->open_time)->format('h:i A'),
                    'close_time' => Carbon::parse($workingHour->close_time)->format('h:i A'),
                ],
                422
            );
        }

        return Response::success(
            [__('Pickup date and time are within branch working hours.')],
            [
                'is_valid'    => true,
                'day_name'    => $dayName,
                'open_time'   => Carbon::parse($workingHour->open_time)->format('h:i A'),
                'close_time'  => Carbon::parse($workingHour->close_time)->format('h:i A'),
                'pickup_date' => $pickupDate->format('d-m-Y'),
                'pickup_time' => Carbon::parse($pickupTime)->format('h:i A'),
            ],
            200
        );
    }

    private function notifyUserOnBookingConfirmed($user, CarBooking $booking, $basic_setting): void
    {
        $booking->loadMissing('cars');

        $content = [
            'title'   => __('Booking Confirmed', [], 'ar'),
            'message' => __('Your booking is confirmed. Car: :model, Pickup: :date, Ref: :trx', [
                'model' => $booking->car_model ?? $booking->cars?->car_model ?? '',
                'date'  => $booking->pickup_date,
                'trx'   => $booking->trip_id,
            ], 'ar'),
            'time'    => now()->diffForHumans(),
            'image'   => files_asset_path('profile-default'),
        ];

        try {
            UserNotification::create([
                'type'    => NotificationConst::BOOKING_CONFIRMED,
                'user_id' => $user->id,
                'message' => $content,
            ]);
        } catch (\Throwable $e) {
            Log::warning('notifyUserOnBookingConfirmed: UserNotification failed', ['error' => $e->getMessage()]);
        }

        try {
            if ($basic_setting && $basic_setting->push_notification) {
                (new PushNotificationHelper())
                    ->prepare([$user->id], [
                        'title'     => $content['title'],
                        'desc'      => $content['message'],
                        'user_type' => 'user',
                    ])
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::warning('notifyUserOnBookingConfirmed: Push failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifyUserOnBookingCancelled($user, CarBooking $booking): void
    {
        $booking->loadMissing('cars');
        $basic_setting = BasicSettings::first();

        $content = [
            'title'   => __('Booking Cancelled', [], 'ar'),
            'message' => __('Your booking :trx has been cancelled.:refund', [
                'trx'    => $booking->trip_id,
                'refund' => $booking->paid_from_balance
                    ? ' ' . __(':amount SAR refunded to your wallet.', ['amount' => number_format($booking->balance_deducted, 2)], 'ar')
                    : '',
            ], 'ar'),
            'time'    => now()->diffForHumans(),
            'image'   => files_asset_path('profile-default'),
        ];

        try {
            UserNotification::create([
                'type'    => NotificationConst::BOOKING_CANCELLED,
                'user_id' => $user->id,
                'message' => $content,
            ]);
        } catch (\Throwable $e) {
            Log::warning('notifyUserOnBookingCancelled: UserNotification failed', ['error' => $e->getMessage()]);
        }

        try {
            if ($basic_setting && $basic_setting->push_notification) {
                (new PushNotificationHelper())
                    ->prepare([$user->id], [
                        'title'     => $content['title'],
                        'desc'      => $content['message'],
                        'user_type' => 'user',
                    ])
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::warning('notifyUserOnBookingCancelled: Push failed', ['error' => $e->getMessage()]);
        }
    }
}
