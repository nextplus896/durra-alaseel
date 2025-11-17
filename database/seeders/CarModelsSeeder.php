<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;

class CarModelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get existing car types
        $carTypes = CarType::all();

        // If no car types exist, create sample ones
        if ($carTypes->isEmpty()) {
            $this->command->info('No car types found. Creating sample car types...');

            $sampleTypes = [
                ['name' => 'Sedan', 'slug' => Str::slug('Sedan'), 'status' => true, 'last_edit_by' => 1],
                ['name' => 'SUV', 'slug' => Str::slug('SUV'), 'status' => true, 'last_edit_by' => 1],
                ['name' => 'Luxury', 'slug' => Str::slug('Luxury'), 'status' => true, 'last_edit_by' => 1],
                ['name' => 'Compact', 'slug' => Str::slug('Compact'), 'status' => true, 'last_edit_by' => 1],
                ['name' => 'Van', 'slug' => Str::slug('Van'), 'status' => true, 'last_edit_by' => 1],
            ];

            foreach ($sampleTypes as $typeData) {
                CarType::create($typeData);
            }

            $carTypes = CarType::all();
            $this->command->info('Created ' . count($sampleTypes) . ' car types.');
        }

        // Sample car models data
        $modelsData = [
            // Sedan models
            'Sedan' => [
                'Toyota Camry',
                'Honda Accord',
                'BMW 3 Series',
                'Mercedes-Benz C-Class',
                'Audi A4',
            ],
            // SUV models
            'SUV' => [
                'Toyota RAV4',
                'Honda CR-V',
                'Ford Explorer',
                'Jeep Grand Cherokee',
                'Nissan Pathfinder',
            ],
            // Luxury models
            'Luxury' => [
                'Mercedes-Benz S-Class',
                'BMW 7 Series',
                'Audi A8',
                'Lexus LS',
                'Porsche Panamera',
            ],
            // Compact models
            'Compact' => [
                'Toyota Corolla',
                'Honda Civic',
                'Volkswagen Golf',
                'Mazda 3',
                'Hyundai Elantra',
            ],
            // Van models
            'Van' => [
                'Toyota Hiace',
                'Mercedes-Benz Sprinter',
                'Ford Transit',
                'Nissan NV',
                'Honda Odyssey',
            ],
        ];

        foreach ($carTypes as $type) {
            $typeName = $type->name;

            // Find matching models for this type
            $models = [];
            foreach ($modelsData as $category => $modelList) {
                if (stripos($typeName, $category) !== false) {
                    $models = $modelList;
                    break;
                }
            }

            // If no specific match, add some generic models
            if (empty($models)) {
                $models = [
                    $typeName . ' Model A',
                    $typeName . ' Model B',
                    $typeName . ' Model C',
                ];
            }

            // Create models for this type
            foreach ($models as $modelName) {
                CarModel::create([
                    'car_type_id' => $type->id,
                    'name' => $modelName,
                    'image' => null, // Images can be added later via admin panel
                    'status' => true,
                ]);
            }
        }

        $this->command->info('Car models seeded successfully!');
    }
}
