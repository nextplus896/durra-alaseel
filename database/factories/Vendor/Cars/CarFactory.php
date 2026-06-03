<?php

namespace Database\Factories\Vendor\Cars;

use App\Models\Admin\Branch;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\Vendor\Cars\Car;
use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Car factory — creates a rentable car with tiered pricing.
 *
 * A Car requires: vendor_id, car_type_id, car_model_id, branch_id.
 * When none are provided, the factory auto-creates them (convenient but slow
 * if called in a loop — share the same vendor/branch across multiple cars).
 *
 * Tiered pricing:
 *   price_per_day   → used when rental_days ≤ 7
 *   price_per_week  → used when rental_days 8–30
 *   price_per_month → used when rental_days ≥ 31
 *
 * Default state: status=1 (active), approval=1 (approved by admin).
 *
 * Example:
 *   $car = Car::factory()->create([
 *       'vendor_id'    => $vendor->id,
 *       'branch_id'    => $branch->id,
 *       'price_per_day' => 150.00,
 *   ]);
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        $title = ['en' => fake()->word() . ' Car', 'ar' => 'سيارة ' . fake()->word()];

        return [
            'vendor_id'              => Vendor::factory(),
            'car_type_id'            => CarType::factory(),
            'car_model_id'           => CarModel::factory(),
            'branch_id'              => Branch::factory(),
            'car_area_id'            => null,
            'car_title'              => json_encode($title),
            'slug'                   => Str::slug(fake()->unique()->words(3, true)),
            'car_model'              => 'Model ' . fake()->numberBetween(100, 999),
            'car_number'             => strtoupper(fake()->bothify('??####')),
            'seat'                   => 5,
            'year'                   => 2023,
            'experience'             => 0,
            'price_per_day'          => 100.00,  // SAR/day  (1–7 day bookings)
            'price_per_week'         => 80.00,   // SAR/day  (8–30 day bookings)
            'price_per_month'        => 70.00,   // SAR/day  (31+ day bookings)
            'allowance_km'           => 200,
            'allowance_price_per_km' => 1.50,
            'status'                 => 1,
            'approval'               => 1,
        ];
    }

    /**
     * State: car is pending admin approval.
     * Use when testing that unapproved cars are hidden from search results.
     */
    public function pendingApproval(): static
    {
        return $this->state(['approval' => 0]);
    }
}
