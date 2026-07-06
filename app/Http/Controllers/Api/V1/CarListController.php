<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\Vendor\Cars\Car;
use App\Models\TemporaryData;
use App\Constants\CarBookingConst;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarListController extends Controller
{
    /**
     * Get all cars with sorting and filtering
     *
     * Query Parameters:
     * - sort: price_asc, price_desc (default: price_desc)
     * - car_type_id: Filter by car type ID
     * - car_model_id: Filter by car model ID
     * - vendor_id: Filter by vendor ID
     * - per_page: Items per page (default: 15)
     * - page: Page number
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sort'         => 'nullable|string|in:price_asc,price_desc,distance_asc,distance_desc',
            'car_type_id'  => 'nullable|integer|exists:car_types,id',
            'car_model_id' => 'nullable|integer|exists:car_models,id',
            'vendor_id'    => 'nullable|integer|exists:vendors,id',
            'branch_id'    => 'nullable|integer|exists:branches,id',
            'per_page'     => 'nullable|integer|min:1|max:100',
            'user_lat'     => 'nullable|numeric|between:-90,90',
            'user_lng'     => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        // Distance sort requires coordinates from the caller
        $sort = $request->input('sort', 'price_desc');
        $isDistanceSort = in_array($sort, ['distance_asc', 'distance_desc']);
        if ($isDistanceSort && (!$request->filled('user_lat') || !$request->filled('user_lng'))) {
            return Response::error([__('user_lat and user_lng are required for distance sorting.')], [], 422);
        }

        $userLat = $request->filled('user_lat') ? (float) $request->input('user_lat') : null;
        $userLng = $request->filled('user_lng') ? (float) $request->input('user_lng') : null;

        $query = Car::query()
            ->where('cars.status', true)
            ->where('cars.approval', true)
            ->with(['type', 'carModel', 'area', 'vendor', 'branch']);

        // Filter by car type
        if ($request->filled('car_type_id')) {
            $query->where('car_type_id', $request->car_type_id);
        }

        // Filter by car model
        if ($request->filled('car_model_id')) {
            $query->where('car_model_id', $request->car_model_id);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Build available filters based on current query (before sorting and pagination)
        $filtersQueryForTypes = (clone $query);
        $typeIds = $filtersQueryForTypes->select('cars.car_type_id')->distinct()->pluck('car_type_id')->filter()->toArray();

        $filtersQueryForModels = (clone $query);
        $modelIds = $filtersQueryForModels->select('cars.car_model_id')->distinct()->pluck('car_model_id')->filter()->toArray();

        $filtersQueryForBranches = (clone $query);
        $branchIds = $filtersQueryForBranches->select('cars.branch_id')->distinct()->pluck('branch_id')->filter()->toArray();

        // Sorting
        if ($isDistanceSort) {
            // Join branches to access their coordinates for the Haversine ORDER BY.
            // select('cars.*') prevents column name ambiguity when branches is joined.
            $query->select('cars.*')
                ->leftJoin('branches', 'cars.branch_id', '=', 'branches.id');

            $direction = ($sort === 'distance_asc') ? 'ASC' : 'DESC';

            // Haversine formula in SQL. LEAST(1, …) guards against floating-point values
            // marginally above 1 that would cause acos() to return NULL.
            // Cars whose branch has no coordinates are pushed to the end of the list.
            $query->orderByRaw(
                "CASE
                    WHEN branches.latitude IS NULL OR branches.longitude IS NULL THEN 999999
                    ELSE (6371 * acos(LEAST(1,
                        cos(radians(?)) * cos(radians(branches.latitude))
                        * cos(radians(branches.longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(branches.latitude))
                    )))
                END {$direction}",
                [$userLat, $userLng, $userLat]
            );
        } elseif ($sort === 'price_asc') {
            $query->orderBy('cars.fees', 'asc');
        } else {
            $query->orderBy('cars.fees', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $cars = $query->paginate($perPage);

        // Determine token to return:
        // - If client passed an existing token and it exists, echo it back.
        // - Else if booking/search params are present (pickup_date & pickup_time), create TemporaryData like `searchCar()` and return its identifier.
        $tokenToReturn = null;
        if ($request->filled('token')) {
            $temp = TemporaryData::where('identifier', $request->input('token'))->first();
            if ($temp) {
                $tokenToReturn = $temp->identifier;
            }
        } elseif ($request->filled('pickup_date') && $request->filled('pickup_time')) {
            try {
                $payload = $request->only(['car_area', 'car_type', 'pickup_time', 'pickup_date', 'round_pickup_date', 'round_pickup_time']);
                $payload = array_filter($payload, function ($v) {
                    return $v !== null && $v !== '';
                });
                $car_booking = TemporaryData::create([
                    'identifier' => generate_unique_string('temporary_datas', 'identifier', 20),
                    'type' => Str::slug(CarBookingConst::CAR_BOOKING),
                    'data' => $payload,
                ]);
                $tokenToReturn = $car_booking->identifier;
            } catch (Exception $e) {
                // don't break listing on TemporaryData creation failure; token remains null
                $tokenToReturn = null;
            }
        }

        // Transform data for response
        $data = [
            'available_filters' => [
                'car_types' => CarType::whereIn('id', $typeIds)->select('id', 'name', 'slug')->get(),
                'car_models' => CarModel::whereIn('id', $modelIds)->select('id', 'name', 'car_type_id')->get(),
                'branches' => \App\Models\Admin\Branch::whereIn('id', $branchIds)->where('status', true)
                    ->with(['deliverySettings'])
                    ->get(['id', 'name', 'slug', 'address'])
                    ->map(function ($branch) {
                        $deliverySetting = $branch->deliverySettings->first();
                        return [
                            'id'             => $branch->id,
                            'name'           => $branch->name,
                            'slug'           => $branch->slug,
                            'address'        => $branch->address,
                            'is_delivery'    => $deliverySetting ? (bool) $deliverySetting->delivery_available : false,
                            'delivery_price' => $deliverySetting ? (float) $deliverySetting->delivery_price : 0,
                        ];
                    }),
            ],
            'cars' => $cars->getCollection()->map(function ($car) use ($userLat, $userLng) {
                $feesRaw = (float) $car->fees;
                $formattedFees = ((int) $feesRaw == $feesRaw) ? (string) ((int) $feesRaw) : number_format($feesRaw, 2, '.', '');
                return [
                    'id'           => $car->id,
                    'vendor_id'    => $car->vendor_id,
                    'car_title'    => $car->car_title,
                    'car_model'    => $car->car_model,
                    'car_number'   => $car->car_number,
                    'seat'         => $car->seat,
                    'year'         => $car->year,
                    'fees'         => $formattedFees,
                    'price'        => $formattedFees, // alias for Flutter convenience
                    'image'        => $car->image,
                    'image_url'    => $car->image_url,
                    'status'       => $car->status,
                    'is_delivery'  => $car->isDeliveryAvailable(),
                    'delivery_price' => $car->getDeliveryPrice(),
                    'car_type' => $car->type ? [
                        'id'   => $car->type->id,
                        'name' => $car->type->name,
                        'slug' => $car->type->slug,
                    ] : null,
                    'car_model_info' => $car->carModel ? [
                        'id'        => $car->carModel->id,
                        'name'      => $car->carModel->name,
                        'image_url' => $car->carModel->image_url,
                    ] : null,
                    'area' => $car->area ? [
                        'id'   => $car->area->id,
                        'name' => $car->area->name,
                    ] : null,
                    'branch' => $car->branch ? [
                        'id'               => $car->branch->id,
                        'name'             => $car->branch->name,
                        'slug'             => $car->branch->slug,
                        'address'          => $car->branch->address,
                        'latitude'         => $car->branch->latitude ? (float) $car->branch->latitude : null,
                        'longitude'        => $car->branch->longitude ? (float) $car->branch->longitude : null,
                        'delivery_enabled' => (bool) $car->branch->delivery_enabled,
                        'delivery_radius_km' => (float) ($car->branch->delivery_radius_km ?? $car->branch->service_radius_km),
                    ] : null,
                    'vendor' => $car->vendor ? [
                        'id'       => $car->vendor->id,
                        'name'     => $car->vendor->fullname ?? $car->vendor->firstname . ' ' . $car->vendor->lastname,
                        'username' => $car->vendor->username,
                    ] : null,
                    'created_at'   => $car->created_at?->toIso8601String(),
                    // Populated when user_lat/user_lng are provided; null otherwise
                    'distance_km'  => ($userLat !== null && $userLng !== null && $car->branch && $car->branch->latitude)
                        ? round($car->branch->calculateDistance($userLat, $userLng), 2)
                        : null,
                ];
            }),
            'pagination' => [
                'total'        => $cars->total(),
                'per_page'     => $cars->perPage(),
                'current_page' => $cars->currentPage(),
                'last_page'    => $cars->lastPage(),
                'from'         => $cars->firstItem(),
                'to'           => $cars->lastItem(),
            ],
            'data_path' => [
                'base_url'   => url('/'),
                'image_path' => files_asset_path_basename('site-section'),
            ],
            'token' => $tokenToReturn,
        ];

        return Response::success([__('Cars fetched successfully!')], $data, 200);
    }

    /**
     * Get cars for a specific vendor with sorting and filtering
     *
     * @param Request $request
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function vendorCars(Request $request, $vendorId)
    {
        $request->merge(['vendor_id' => $vendorId]);
        return $this->index($request);
    }

    /**
     * Get all car types for filter dropdown
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function carTypes()
    {
        $carTypes = CarType::where('status', true)
            ->select('id', 'name', 'slug')
            ->orderBy('name', 'asc')
            ->get();

        return Response::success([__('Car types fetched successfully!')], ['car_types' => $carTypes], 200);
    }

    /**
     * Get all car models for filter dropdown
     * Optionally filter by car_type_id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function carModels(Request $request)
    {
        $query = CarModel::where('status', true)
            ->select('id', 'name', 'car_type_id', 'image');

        if ($request->filled('car_type_id')) {
            $query->where('car_type_id', $request->car_type_id);
        }

        $carModels = $query->orderBy('name', 'asc')->get()->map(function ($model) {
            return [
                'id'          => $model->id,
                'name'        => $model->name,
                'car_type_id' => $model->car_type_id,
                'image_url'   => $model->image_url,
            ];
        });

        return Response::success([__('Car models fetched successfully!')], ['car_models' => $carModels], 200);
    }

    /**
     * Get all branches for filter dropdown
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function branches()
    {
        $branches = \App\Models\Admin\Branch::where('status', true)
            ->with(['deliverySettings'])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'slug', 'address'])
            ->map(function ($branch) {
                $deliverySetting = $branch->deliverySettings->first();
                return [
                    'id'             => $branch->id,
                    'name'           => $branch->name,
                    'slug'           => $branch->slug,
                    'address'        => $branch->address,
                    'is_delivery'    => $deliverySetting ? (bool) $deliverySetting->delivery_available : false,
                    'delivery_price' => $deliverySetting ? (float) $deliverySetting->delivery_price : 0,
                ];
            });

        return Response::success([__('Branches fetched successfully!')], ['branches' => $branches], 200);
    }

    /**
     * Get single car details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $car = Car::with(['type', 'carModel', 'area', 'vendor'])
            ->where('status', true)
            ->where('approval', true)
            ->find($id);

        if (!$car) {
            return Response::error([__('Car not found')], [], 404);
        }

        $feesRaw = (float) $car->fees;
        $formattedFees = ((int) $feesRaw == $feesRaw) ? (string) ((int) $feesRaw) : number_format($feesRaw, 2, '.', '');

        // Determine token to return for show (echo back valid TemporaryData.identifier if provided)
        $tokenToReturn = null;
        $reqToken = request()->input('token');
        if ($reqToken) {
            $temp = TemporaryData::where('identifier', $reqToken)->first();
            if ($temp) {
                $tokenToReturn = $temp->identifier;
            }
        }

        // Determine token for show: echo provided valid token or create TemporaryData if booking params present
        $tokenToReturn = null;
        $reqToken = request()->input('token');
        if ($reqToken) {
            $temp = TemporaryData::where('identifier', $reqToken)->first();
            if ($temp) {
                $tokenToReturn = $temp->identifier;
            }
        } elseif (request()->filled('pickup_date') && request()->filled('pickup_time')) {
            try {
                $payload = request()->only(['car_area', 'car_type', 'pickup_time', 'pickup_date', 'round_pickup_date', 'round_pickup_time']);
                $payload = array_filter($payload, function ($v) {
                    return $v !== null && $v !== '';
                });
                $car_booking = TemporaryData::create([
                    'identifier' => generate_unique_string('temporary_datas', 'identifier', 20),
                    'type' => Str::slug(CarBookingConst::CAR_BOOKING),
                    'data' => $payload,
                ]);
                $tokenToReturn = $car_booking->identifier;
            } catch (Exception $e) {
                $tokenToReturn = null;
            }
        }

        $data = [
            'car' => [
                'id'           => $car->id,
                'vendor_id'    => $car->vendor_id,
                'car_title'    => $car->car_title,
                'car_model'    => $car->car_model,
                'car_number'   => $car->car_number,
                'seat'         => $car->seat,
                'year'         => $car->year,
                'fees'         => $formattedFees,
                'price'        => $formattedFees,
                'image'        => $car->image,
                'image_url'    => $car->image_url,
                'status'       => $car->status,
                'is_delivery'  => $car->isDeliveryAvailable(),
                'delivery_price' => $car->getDeliveryPrice(),
                'car_type' => $car->type ? [
                    'id'   => $car->type->id,
                    'name' => $car->type->name,
                    'slug' => $car->type->slug,
                ] : null,
                'car_model_info' => $car->carModel ? [
                    'id'        => $car->carModel->id,
                    'name'      => $car->carModel->name,
                    'image_url' => $car->carModel->image_url,
                ] : null,
                'area' => $car->area ? [
                    'id'   => $car->area->id,
                    'name' => $car->area->name,
                ] : null,
                'branch' => $car->branch ? [
                    'id'               => $car->branch->id,
                    'name'             => $car->branch->name,
                    'slug'             => $car->branch->slug,
                    'address'          => $car->branch->address,
                    'latitude'         => $car->branch->latitude ? (float) $car->branch->latitude : null,
                    'longitude'        => $car->branch->longitude ? (float) $car->branch->longitude : null,
                    'delivery_enabled' => (bool) $car->branch->delivery_enabled,
                    'delivery_radius_km' => (float) ($car->branch->delivery_radius_km ?? $car->branch->service_radius_km),
                ] : null,
                'vendor' => $car->vendor ? [
                    'id'       => $car->vendor->id,
                    'name'     => $car->vendor->fullname ?? $car->vendor->firstname . ' ' . $car->vendor->lastname,
                    'username' => $car->vendor->username,
                ] : null,
                'created_at' => $car->created_at?->toIso8601String(),
            ],
            'data_path' => [
                'base_url'   => url('/'),
                'image_path' => files_asset_path_basename('site-section'),
            ],
            'token' => $tokenToReturn,
        ];

        return Response::success([__('Car fetched successfully!')], $data, 200);
    }
}
