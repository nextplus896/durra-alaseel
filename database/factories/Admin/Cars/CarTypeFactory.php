<?php

namespace Database\Factories\Admin\Cars;

use App\Models\Admin\Cars\CarType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * CarType factory — generates a car-type lookup row (e.g. "SUV", "Sedan").
 *
 * Tests that create a Car must first have a CarType to satisfy the
 * car_type_id foreign key. slug and name are unique per SQLite in-memory DB.
 *
 * Example:
 *   $type = CarType::factory()->create();
 *   Car::factory()->create(['car_type_id' => $type->id]);
 */
class CarTypeFactory extends Factory
{
    protected $model = CarType::class;

    public function definition(): array
    {
        $name = fake()->unique()->word() . fake()->numberBetween(1, 999);
        return [
            'name'         => $name,
            'slug'         => Str::slug($name),
            'status'       => 1,
            'last_edit_by' => 1, // admin_id placeholder; SQLite ignores FK
        ];
    }
}
