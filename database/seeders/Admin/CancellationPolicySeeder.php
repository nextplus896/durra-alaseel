<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ensures a default global cancellation policy record exists.
 *
 * Safe to run multiple times — only inserts if the table is empty.
 * Default values: 4-hour window, 1 rental day deduction, 10% service fee.
 */
class CancellationPolicySeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('cancellation_policies')->count() > 0) {
            return;
        }

        DB::table('cancellation_policies')->insert([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1.00,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
            'last_edit_by'              => null,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }
}
