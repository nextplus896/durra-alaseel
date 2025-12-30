<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\Branch;
use App\Models\BranchDeliverySetting;
use App\Models\Vendor\Cars\Car;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    /**
     * Get all active branches
     */
    public function index()
    {
        $branches = Branch::where('status', true)
            ->select('id', 'name', 'slug', 'latitude', 'longitude', 'service_radius', 'address')
            ->get();

        return Response::success(
            [__('Branches fetched successfully')],
            ['branches' => $branches],
            200
        );
    }

    /**
     * Get branch details by ID
     */
    public function show($id)
    {
        $branch = Branch::where('status', true)
            ->select('id', 'name', 'slug', 'latitude', 'longitude', 'service_radius', 'address')
            ->find($id);

        if (!$branch) {
            return Response::error([__('Branch not found')], [], 404);
        }

        return Response::success(
            [__('Branch details fetched successfully')],
            ['branch' => $branch],
            200
        );
    }

    /**
     * Check if user's GPS coordinates fall within any branch's service range
     * Returns list of branches that can serve the user's location
     */
    public function checkServiceArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $userLat = $request->latitude;
        $userLng = $request->longitude;

        $branches = Branch::where('status', true)->get();
        $servicingBranches = [];

        foreach ($branches as $branch) {
            $distance = $this->calculateDistance(
                $userLat,
                $userLng,
                $branch->latitude,
                $branch->longitude
            );

            // Check if user is within the branch's service radius
            if ($distance <= $branch->service_radius) {
                $servicingBranches[] = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                    'address' => $branch->address,
                    'distance_km' => round($distance, 2),
                    'service_radius' => $branch->service_radius,
                    'latitude' => $branch->latitude,
                    'longitude' => $branch->longitude,
                ];
            }
        }

        // Sort by distance (nearest first)
        usort($servicingBranches, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });

        $isServiceable = count($servicingBranches) > 0;

        return Response::success(
            [$isServiceable ? __('Service available in your area') : __('No service available in your area')],
            [
                'is_serviceable' => $isServiceable,
                'branches' => $servicingBranches,
                'user_coordinates' => [
                    'latitude' => $userLat,
                    'longitude' => $userLng,
                ],
            ],
            200
        );
    }

    /**
     * Get available cars with delivery options for a specific location
     */
    public function getCarsWithDelivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'car_type_id' => 'nullable|integer|exists:car_types,id',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $userLat = $request->latitude;
        $userLng = $request->longitude;

        // First, find branches within service range
        $branches = Branch::where('status', true)->get();
        $servicingBranchIds = [];

        foreach ($branches as $branch) {
            $distance = $this->calculateDistance(
                $userLat,
                $userLng,
                $branch->latitude,
                $branch->longitude
            );

            if ($distance <= $branch->service_radius) {
                $servicingBranchIds[$branch->id] = $distance;
            }
        }

        if (empty($servicingBranchIds)) {
            return Response::success(
                [__('No cars available for delivery in your area')],
                [
                    'cars' => [],
                    'user_coordinates' => [
                        'latitude' => $userLat,
                        'longitude' => $userLng,
                    ],
                ],
                200
            );
        }

        // Get cars from these branches
        $carsQuery = Car::whereIn('branch_id', array_keys($servicingBranchIds))
            ->where('status', true)
            ->where('approval', true)
            ->with(['branch', 'type', 'carModel', 'vendor']);

        if ($request->car_type_id) {
            $carsQuery->where('car_type_id', $request->car_type_id);
        }

        $cars = $carsQuery->get();

        // Add delivery information to each car
        $carsWithDelivery = $cars->map(function ($car) use ($servicingBranchIds) {
            $branchDistance = $servicingBranchIds[$car->branch_id] ?? 0;

            // Get delivery settings for this car's vendor and branch
            $deliverySetting = BranchDeliverySetting::where('branch_id', $car->branch_id)
                ->where('vendor_id', $car->vendor_id)
                ->first();

            return [
                'id' => $car->id,
                'slug' => $car->slug,
                'car_title' => $car->car_title,
                'car_type' => $car->type ? $car->type->name : null,
                'car_model' => $car->carModel ? $car->carModel->name : null,
                'seat' => $car->seat,
                'year' => $car->year,
                'fees' => $car->fees,
                'image' => $car->image,
                'branch' => [
                    'id' => $car->branch->id ?? null,
                    'name' => $car->branch->name ?? null,
                    'distance_km' => round($branchDistance, 2),
                ],
                'delivery' => [
                    'available' => $deliverySetting ? $deliverySetting->delivery_available : false,
                    'price' => $deliverySetting ? $deliverySetting->delivery_price : 0,
                ],
            ];
        });

        // Sort by distance
        $carsWithDelivery = $carsWithDelivery->sortBy('branch.distance_km')->values();

        return Response::success(
            [__('Cars fetched successfully')],
            [
                'cars' => $carsWithDelivery,
                'user_coordinates' => [
                    'latitude' => $userLat,
                    'longitude' => $userLng,
                ],
            ],
            200
        );
    }

    /**
     * Get delivery price for a specific car to user's location
     */
    public function getDeliveryPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => 'required|integer|exists:cars,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 400);
        }

        $car = Car::with('branch')->find($request->car_id);

        if (!$car || !$car->branch) {
            return Response::error([__('Car or branch not found')], [], 404);
        }

        // Calculate distance from user to branch
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $car->branch->latitude,
            $car->branch->longitude
        );

        // Check if within service area
        if ($distance > $car->branch->service_radius) {
            return Response::error(
                [__('Your location is outside the delivery service area for this car')],
                [
                    'distance_km' => round($distance, 2),
                    'service_radius' => $car->branch->service_radius,
                ],
                400
            );
        }

        // Get delivery settings
        $deliverySetting = BranchDeliverySetting::where('branch_id', $car->branch_id)
            ->where('vendor_id', $car->vendor_id)
            ->first();

        if (!$deliverySetting || !$deliverySetting->delivery_available) {
            return Response::error(
                [__('Delivery is not available for this car')],
                [],
                400
            );
        }

        return Response::success(
            [__('Delivery price fetched successfully')],
            [
                'delivery_available' => true,
                'delivery_price' => $deliverySetting->delivery_price,
                'distance_km' => round($distance, 2),
                'branch' => [
                    'id' => $car->branch->id,
                    'name' => $car->branch->name,
                    'address' => $car->branch->address,
                ],
                'currency' => get_default_currency_code(),
            ],
            200
        );
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Radius of Earth in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
