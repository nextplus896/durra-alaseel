<?php

namespace App\Services;

use App\Models\Admin\Branch;

class DeliveryRadiusService
{
    /**
     * Check whether a user-selected coordinate is eligible for delivery
     * from the given branch.
     *
     * Returns:
     *  - allowed        (bool)       whether delivery is permitted at this coordinate
     *  - distance_km    (float|null) actual distance from the branch, or null when
     *                               delivery is disabled on the branch
     *  - max_radius     (float|null) the branch's configured radius, or null when
     *                               delivery is disabled
     *
     * @param  int   $branchId
     * @param  float $lat
     * @param  float $lng
     * @return array{allowed: bool, distance_km: float|null, max_radius: float|null}
     */
    public function checkDeliveryEligibility(int $branchId, float $lat, float $lng): array
    {
        $branch = Branch::findOrFail($branchId);

        if (!$branch->delivery_enabled) {
            return [
                'allowed'     => false,
                'distance_km' => null,
                'max_radius'  => null,
            ];
        }

        // Fall back to service_radius_km when no dedicated delivery radius is set.
        $radius = $branch->delivery_radius_km !== null
            ? (float) $branch->delivery_radius_km
            : (float) $branch->service_radius_km;

        if ($radius <= 0) {
            return [
                'allowed'     => false,
                'distance_km' => null,
                'max_radius'  => null,
            ];
        }

        $distance = $branch->calculateDistance($lat, $lng);
        $allowed  = $distance <= $radius;

        return [
            'allowed'     => $allowed,
            'distance_km' => round($distance, 2),
            'max_radius'  => $radius,
        ];
    }
}
