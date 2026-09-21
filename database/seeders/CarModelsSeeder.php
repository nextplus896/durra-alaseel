<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;

class CarModelsSeeder extends Seeder
{
    public function run()
    {
        $carTypes = CarType::all();

        if ($carTypes->isEmpty()) {
            $this->command->warn('No car types found. Run TypeSeeder first.');
            return;
        }

        // Map your exact type names/slugs to their models
        $modelsData = [
            'Sedan' => [
                ['name' => 'Toyota Camry',        'image' => 'toyota-camry-2024.webp'],
                ['name' => 'Hyundai Sonata',      'image' => 'hyundai-sonata-2024.webp'],
                ['name' => 'Nissan Sunny',        'image' => 'nissan-sunny-2023.webp'],
            ],
            'Car' => [
                ['name' => 'Toyota Yaris',         'image' => 'toyota-yaris-2024.webp'],
                ['name' => 'Toyota Hiace',         'image' => 'toyota-hiace-2024.webp'],
            ],
            'Suv' => [
                ['name' => 'Toyota RAV4',          'image' => 'toyota-rav4-2024.webp'],
                ['name' => 'Nissan Patrol',        'image' => 'nissan-patrol-2024.webp'],
                ['name' => 'Toyota Land Cruiser',  'image' => 'toyota-land-cruiser-2024.webp'],
                ['name' => 'Mitsubishi Pajero',    'image' => 'mitsubishi-pajero-2023.webp'],
            ],
        ];

        $createdCount = 0;

        foreach ($carTypes as $type) {
            $typeName = $type->name;

            // Match type by name (case-insensitive)
            $models = [];
            foreach ($modelsData as $category => $modelList) {
                if (stripos($typeName, $category) !== false) {
                    $models = $modelList;
                    break;
                }
            }

            if (empty($models)) {
                continue; // Skip types with no matching models (e.g. old generic types)
            }

            foreach ($models as $modelData) {
                // Prevent duplicates if re-run
                $exists = CarModel::where('car_type_id', $type->id)
                    ->where('name', $modelData['name'])
                    ->exists();

                if (!$exists) {
                    CarModel::create([
                        'car_type_id' => $type->id,
                        'name'        => $modelData['name'],
                        'image'       => $modelData['image'],
                        'status'      => true,
                    ]);
                    $createdCount++;
                }
            }
        }

        $this->command->info("Seeded {$createdCount} car models.");
    }
}
