<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Branch factory — represents a physical rental branch location.
 *
 * Branches are required by Cars (branch_id) and by CarBookings (branch_id).
 * The slug must be unique; latitude/longitude are random valid coordinates.
 * last_edit_by uses admin_id=1 as a placeholder (SQLite ignores FK).
 *
 * Example:
 *   $branch = Branch::factory()->create();
 *   Car::factory()->create(['branch_id' => $branch->id]);
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = fake()->city() . ' ' . fake()->numberBetween(1, 9999);
        return [
            'name'              => $name,
            'slug'              => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
            'address'           => fake()->address(),
            'latitude'          => fake()->latitude(20.0, 30.0),   // Saudi Arabia range
            'longitude'         => fake()->longitude(36.0, 55.0),
            'service_radius_km' => 10.00,
            'status'            => 1,
            'last_edit_by'      => null,
        ];
    }
}
