<?php

namespace Database\Factories\Admin\Cars;

use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * CarModel factory — represents a car model (e.g. "Camry", "Highlander").
 *
 * Every CarModel belongs to a CarType. If no car_type_id is supplied the
 * factory creates its own CarType so the test does not need to wire them up.
 *
 * Example:
 *   // Auto-creates a CarType:
 *   $model = CarModel::factory()->create();
 *
 *   // Share a CarType across records:
 *   $type  = CarType::factory()->create();
 *   $model = CarModel::factory()->create(['car_type_id' => $type->id]);
 */
class CarModelFactory extends Factory
{
    protected $model = CarModel::class;

    public function definition(): array
    {
        return [
            'car_type_id' => CarType::factory(),
            'name'        => fake()->word() . ' ' . fake()->numberBetween(100, 999),
            'status'      => 1,
        ];
    }
}
