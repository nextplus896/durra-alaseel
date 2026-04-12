<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Admin\Branch;
use App\Models\BranchWorkingHour;

class BranchSeeder extends Seeder
{
    /**
     * Working hour patterns.
     * day_of_week uses Carbon convention: 0=Sunday, 1=Monday, ..., 6=Saturday
     */
    private function getPatterns(): array
    {
        // Pattern A: Sat-Thu two shifts, Fri evening only
        $patternA = [];
        foreach ([6, 0, 1, 2, 3, 4] as $day) { // Sat-Thu
            $patternA[] = ['day' => $day, 'open' => '09:00', 'close' => '12:00'];
            $patternA[] = ['day' => $day, 'open' => '17:00', 'close' => '22:00'];
        }
        $patternA[] = ['day' => 5, 'open' => '17:00', 'close' => '22:00']; // Fri

        // Pattern B: Sat two shifts, Sun-Thu full day, Fri evening
        $patternB = [];
        $patternB[] = ['day' => 6, 'open' => '09:00', 'close' => '12:00']; // Sat
        $patternB[] = ['day' => 6, 'open' => '17:00', 'close' => '22:00']; // Sat
        foreach ([0, 1, 2, 3, 4] as $day) { // Sun-Thu
            $patternB[] = ['day' => $day, 'open' => '09:00', 'close' => '23:00'];
        }
        $patternB[] = ['day' => 5, 'open' => '17:00', 'close' => '22:00']; // Fri

        // Pattern C: Sat noon-11pm, Sun-Thu full day, Fri evening
        $patternC = [];
        $patternC[] = ['day' => 6, 'open' => '12:00', 'close' => '23:00']; // Sat
        foreach ([0, 1, 2, 3, 4] as $day) { // Sun-Thu
            $patternC[] = ['day' => $day, 'open' => '09:00', 'close' => '23:00'];
        }
        $patternC[] = ['day' => 5, 'open' => '17:00', 'close' => '22:00']; // Fri

        // Pattern D: Sat-Thu two shifts, Fri CLOSED (no slots)
        $patternD = [];
        foreach ([6, 0, 1, 2, 3, 4] as $day) { // Sat-Thu
            $patternD[] = ['day' => $day, 'open' => '09:00', 'close' => '12:00'];
            $patternD[] = ['day' => $day, 'open' => '17:00', 'close' => '22:00'];
        }
        // Fri: no slots at all

        return [
            'A' => $patternA,
            'B' => $patternB,
            'C' => $patternC,
            'D' => $patternD,
        ];
    }

    public function run(): void
    {
        $patterns = $this->getPatterns();

        $branches = [
            [
                'name'      => 'فرع فلسطين',
                'address'   => 'جده - حي بني مالك',
                'phone'     => '012-2872112',
                'email'     => 'almadina2@durratalaseel.com',
                'latitude'  => 21.53552899,
                'longitude' => 39.21215969,
                'pattern'   => 'A',
            ],
            [
                'name'      => 'فرع السبعين',
                'address'   => 'جده - حي العزيزية',
                'phone'     => '0544400902',
                'email'     => 'almadina3@durratalaseel.com',
                'latitude'  => 21.54748120,
                'longitude' => 39.20464680,
                'pattern'   => 'B',
            ],
            [
                'name'      => 'فرع قريش 1',
                'address'   => 'جده - حي السلامة',
                'phone'     => '012-6426260',
                'email'     => 'filastin@durratalaseel.com',
                'latitude'  => 21.58578816,
                'longitude' => 39.16244848,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع قريش 2',
                'address'   => 'جده - حي السلامة',
                'phone'     => '012-6226269',
                'email'     => 'alfayha@durratalaseel.com',
                'latitude'  => 21.58635582,
                'longitude' => 39.16329808,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع طريق المدينة 3',
                'address'   => 'جده - حي السلامة',
                'phone'     => '012-6226269',
                'email'     => 'alarbaein@durratalaseel.com',
                'latitude'  => 21.59437768,
                'longitude' => 39.16113530,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع طريق المدينة 2',
                'address'   => 'جده - حي البوادي',
                'phone'     => '012-6226269',
                'email'     => 'alsitiyn@durratalaseel.com',
                'latitude'  => 21.59739239,
                'longitude' => 39.16039487,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع أبحر',
                'address'   => 'جده - حي الفردوس',
                'phone'     => '0537208620',
                'email'     => 'aljamiea@durratalaseel.com',
                'latitude'  => 21.78270664,
                'longitude' => 39.11785606,
                'pattern'   => 'D',
            ],
            [
                'name'      => 'فرع عسفان',
                'address'   => 'جده - مخطط الفروسية',
                'phone'     => '0599180209',
                'email'     => 'quraysh1@durratalaseel.com',
                'latitude'  => 21.85492819,
                'longitude' => 39.21716227,
                'pattern'   => 'D',
            ],
            [
                'name'      => 'فرع الفيحاء 1',
                'address'   => 'جده - حي الفيحاء',
                'phone'     => '0561443132',
                'email'     => 'info@durratalaseel.com',
                'latitude'  => 21.49901741,
                'longitude' => 39.21625844,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع الفيحاء 2',
                'address'   => 'جده - حي الفيحاء',
                'phone'     => '0561443132',
                'email'     => 'info@durratalaseel.com',
                'latitude'  => 21.49327312,
                'longitude' => 39.22041438,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع الأربعين 1',
                'address'   => 'جده - حي الصفا',
                'phone'     => '0544504392',
                'email'     => 'info@durratalaseel.com',
                'latitude'  => 21.56551597,
                'longitude' => 39.21182576,
                'pattern'   => 'A',
            ],
            [
                'name'      => 'فرع النزهة',
                'address'   => 'مكة المكرمة - حي الضيافة',
                'phone'     => '0542688922',
                'email'     => 'info@durratalaseel.com',
                'latitude'  => 21.43599956,
                'longitude' => 39.79665522,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع الشهداء',
                'address'   => 'مكة المكرمة - طريق المدينة المنورة',
                'phone'     => '0542688922',
                'email'     => 'info@durratalaseel.com',
                'latitude'  => 21.45724035,
                'longitude' => 39.80755840,
                'pattern'   => 'C',
            ],
            [
                'name'      => 'فرع الكعكية',
                'address'   => 'مكة المكرمة - حي السبهاني',
                'phone'     => '0542688922',
                'email'     => 'info@durratalaseel.com',
                'latitude'  => 21.35672615,
                'longitude' => 39.79098713,
                'pattern'   => 'C',
            ],
        ];

        foreach ($branches as $data) {
            $pattern = $data['pattern'];
            unset($data['pattern']);

            $slug = Str::slug($data['name']);

            $branch = Branch::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, [
                    'slug'              => $slug,
                    'status'            => true,
                    'service_radius_km' => 10,
                    'delivery_enabled'  => true,
                    'last_edit_by'      => 1,
                ])
            );

            // Delete existing working hours and recreate from pattern
            $branch->workingHours()->delete();

            foreach ($patterns[$pattern] as $slot) {
                BranchWorkingHour::create([
                    'branch_id'   => $branch->id,
                    'day_of_week' => $slot['day'],
                    'open_time'   => $slot['open'],
                    'close_time'  => $slot['close'],
                    'is_enabled'  => true,
                ]);
            }
        }

        $this->command->info('Seeded 14 branches with working hours.');
    }
}
