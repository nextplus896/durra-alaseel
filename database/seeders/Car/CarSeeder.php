<?php

namespace Database\Seeders\Car;

use App\Models\Admin\Branch;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\Vendor\Cars\Car;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarSeeder extends Seeder
{
    /**
     * Car fleet definitions.
     *
     * Each car template defines:
     *  - model_name: must exist in car_models table (matched by name)
     *  - type_slug:  matched against car_types.slug (sedan / suv / car)
     *  - image:      filename inside public/backend/images/car-models/
     *  - seats, year, allowance_km, prices, insurance
     *
     * Prices in SAR.
     */
    private function fleetTemplates(): array
    {
        return [
            // ── Sedans ──────────────────────────────────────────
            [
                'model_name'             => 'Toyota Camry',
                'type_slug'              => 'sedan',
                'image'                  => 'toyota-camry-2024.webp',
                'seats'                  => 5,
                'year'                   => 2024,
                'allowance_km'           => 300,
                'allowance_price_per_km' => 1.00,
                'daily_insurance'        => 35.00000000,
                'deductible_insurance'   => 1500.00000000,
                'price_per_day'          => 200.00000000,
                'price_per_week'         => 170.00000000,
                'price_per_month'        => 150.00000000,
            ],
            [
                'model_name'             => 'Hyundai Sonata',
                'type_slug'              => 'sedan',
                'image'                  => 'hyundai-sonata-2024.webp',
                'seats'                  => 5,
                'year'                   => 2024,
                'allowance_km'           => 300,
                'allowance_price_per_km' => 1.00,
                'daily_insurance'        => 30.00000000,
                'deductible_insurance'   => 1500.00000000,
                'price_per_day'          => 180.00000000,
                'price_per_week'         => 155.00000000,
                'price_per_month'        => 140.00000000,
            ],
            [
                'model_name'             => 'Nissan Sunny',
                'type_slug'              => 'sedan',
                'image'                  => 'nissan-sunny-2023.webp',
                'seats'                  => 5,
                'year'                   => 2023,
                'allowance_km'           => 250,
                'allowance_price_per_km' => 0.80,
                'daily_insurance'        => 25.00000000,
                'deductible_insurance'   => 2000.00000000,
                'price_per_day'          => 120.00000000,
                'price_per_week'         => 100.00000000,
                'price_per_month'        => 90.00000000,
            ],

            // ── Economy / Compact ───────────────────────────────
            [
                'model_name'             => 'Toyota Yaris',
                'type_slug'              => 'car',
                'image'                  => 'toyota-yaris-2024.webp',
                'seats'                  => 5,
                'year'                   => 2024,
                'allowance_km'           => 200,
                'allowance_price_per_km' => 0.75,
                'daily_insurance'        => 20.00000000,
                'deductible_insurance'   => 2000.00000000,
                'price_per_day'          => 100.00000000,
                'price_per_week'         => 85.00000000,
                'price_per_month'        => 75.00000000,
            ],

            // ── SUVs ────────────────────────────────────────────
            [
                'model_name'             => 'Toyota RAV4',
                'type_slug'              => 'suv',
                'image'                  => 'toyota-rav4-2024.webp',
                'seats'                  => 5,
                'year'                   => 2024,
                'allowance_km'           => 300,
                'allowance_price_per_km' => 1.50,
                'daily_insurance'        => 45.00000000,
                'deductible_insurance'   => 2000.00000000,
                'price_per_day'          => 300.00000000,
                'price_per_week'         => 260.00000000,
                'price_per_month'        => 230.00000000,
            ],
            [
                'model_name'             => 'Nissan Patrol',
                'type_slug'              => 'suv',
                'image'                  => 'nissan-patrol-2024.webp',
                'seats'                  => 7,
                'year'                   => 2024,
                'allowance_km'           => 350,
                'allowance_price_per_km' => 2.00,
                'daily_insurance'        => 60.00000000,
                'deductible_insurance'   => 3000.00000000,
                'price_per_day'          => 600.00000000,
                'price_per_week'         => 520.00000000,
                'price_per_month'        => 450.00000000,
            ],
            [
                'model_name'             => 'Toyota Land Cruiser',
                'type_slug'              => 'suv',
                'image'                  => 'toyota-land-cruiser-2024.webp',
                'seats'                  => 7,
                'year'                   => 2024,
                'allowance_km'           => 350,
                'allowance_price_per_km' => 2.00,
                'daily_insurance'        => 65.00000000,
                'deductible_insurance'   => 3000.00000000,
                'price_per_day'          => 700.00000000,
                'price_per_week'         => 600.00000000,
                'price_per_month'        => 520.00000000,
            ],
            [
                'model_name'             => 'Mitsubishi Pajero',
                'type_slug'              => 'suv',
                'image'                  => 'mitsubishi-pajero-2023.webp',
                'seats'                  => 7,
                'year'                   => 2023,
                'allowance_km'           => 300,
                'allowance_price_per_km' => 1.50,
                'daily_insurance'        => 50.00000000,
                'deductible_insurance'   => 2500.00000000,
                'price_per_day'          => 400.00000000,
                'price_per_week'         => 350.00000000,
                'price_per_month'        => 300.00000000,
            ],

            // ── Vans ────────────────────────────────────────────
            [
                'model_name'             => 'Toyota Hiace',
                'type_slug'              => 'car',
                'image'                  => 'toyota-hiace-2024.webp',
                'seats'                  => 14,
                'year'                   => 2024,
                'allowance_km'           => 400,
                'allowance_price_per_km' => 2.00,
                'daily_insurance'        => 50.00000000,
                'deductible_insurance'   => 3000.00000000,
                'price_per_day'          => 450.00000000,
                'price_per_week'         => 390.00000000,
                'price_per_month'        => 350.00000000,
            ],
        ];
    }

    public function run(): void
    {
        // Build lookup maps
        $typesBySlug  = CarType::whereIn('slug', ['sedan', 'suv', 'car'])->get()->keyBy('slug');
        $modelsByName = CarModel::all()->keyBy('name');

        // Verify required models exist
        $requiredModels = collect($this->fleetTemplates())->pluck('model_name')->unique();
        $missing = $requiredModels->diff($modelsByName->keys());
        if ($missing->isNotEmpty()) {
            $this->command->warn('Missing car models in DB: ' . $missing->join(', '));
            $this->command->warn('Run CarModelsSeeder first, or add these models via the admin panel.');
            $this->command->warn('Skipping CarSeeder.');
            return;
        }

        // Get active branches (exclude under-construction)
        $activeBranches = Branch::where('status', true)->get();
        if ($activeBranches->isEmpty()) {
            $this->command->warn('No active branches found. Run BranchSeeder first.');
            return;
        }

        $templates    = $this->fleetTemplates();
        $vendorId     = 1;
        $insertCount  = 0;
        $plateCounters = [];

        foreach ($activeBranches as $branch) {
            // Each active branch gets the full fleet
            foreach ($templates as $tpl) {
                $carModel = $modelsByName->get($tpl['model_name']);
                $carType  = $typesBySlug->get($tpl['type_slug']);

                if (!$carModel || !$carType) {
                    continue;
                }

                // Generate sequential plate numbers per model
                $modelKey = $tpl['model_name'];
                if (!isset($plateCounters[$modelKey])) {
                    $plateCounters[$modelKey] = 1;
                }
                $seq = str_pad($plateCounters[$modelKey]++, 3, '0', STR_PAD_LEFT);

                // Arabic title
                $titleAr = $tpl['model_name'] . ' ' . $tpl['year'];
                $titleEn = $tpl['model_name'] . ' ' . $tpl['year'];
                $carTitle = json_encode([
                    'ar' => ['car_title' => $titleAr],
                    'en' => ['car_title' => $titleEn],
                ]);

                Car::create([
                    'vendor_id'              => $vendorId,
                    'car_area_id'            => null,
                    'car_type_id'            => $carType->id,
                    'car_model_id'           => $carModel->id,
                    'branch_id'              => $branch->id,
                    'slug'                   => Str::slug($tpl['model_name'] . '-' . $branch->slug . '-' . $seq),
                    'car_title'              => $carTitle,
                    'car_model'              => $tpl['model_name'],
                    'car_number'             => 'درة ' . $seq,
                    'seat'                   => $tpl['seats'],
                    'year'                   => $tpl['year'],
                    'experience'             => null,
                    'fees'                   => 0.00000000,
                    'image'                  => $tpl['image'],
                    'allowance_km'           => $tpl['allowance_km'],
                    'allowance_price_per_km' => $tpl['allowance_price_per_km'],
                    'daily_insurance'        => $tpl['daily_insurance'],
                    'deductible_insurance'   => $tpl['deductible_insurance'],
                    'price_per_day'          => $tpl['price_per_day'],
                    'price_per_week'         => $tpl['price_per_week'],
                    'price_per_month'        => $tpl['price_per_month'],
                    'status'                 => true,
                    'approval'               => 1,
                ]);

                $insertCount++;
            }
        }

        $this->command->info("Seeded {$insertCount} cars across {$activeBranches->count()} active branches ({$this->countTemplates()} models each).");
    }

    private function countTemplates(): int
    {
        return count($this->fleetTemplates());
    }
}
