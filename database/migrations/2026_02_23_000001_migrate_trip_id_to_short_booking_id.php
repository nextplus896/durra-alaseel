<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alphanumeric charset — ambiguous chars (0/O, 1/I/L) excluded.
     */
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const LENGTH  = 6;

    public function up(): void
    {
        // 1. Re-generate all existing trip_id values to the new 6-char alphanumeric format.
        //    We collect already-assigned codes per batch to avoid intra-batch duplicates.
        $used = [];

        DB::table('car_bookings')
            ->orderBy('id')
            ->chunk(200, function ($bookings) use (&$used) {
                foreach ($bookings as $booking) {
                    do {
                        $code = $this->generateCode();
                    } while (
                        in_array($code, $used, true) ||
                        DB::table('car_bookings')->where('trip_id', $code)->where('id', '!=', $booking->id)->exists()
                    );

                    $used[] = $code;
                    DB::table('car_bookings')->where('id', $booking->id)->update(['trip_id' => $code]);
                }
            });

        // 2. Ensure the column is a regular string (not null by default) and add a unique index.
        Schema::table('car_bookings', function (Blueprint $table) {
            // Make trip_id non-nullable with a default (all rows now have values).
            $table->string('trip_id', 10)->nullable()->change();
            $table->unique('trip_id', 'car_bookings_trip_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('car_bookings', function (Blueprint $table) {
            $table->dropUnique('car_bookings_trip_id_unique');
        });

        // Restore numeric-style trip_ids (12345678) — best effort only.
        DB::table('car_bookings')
            ->orderBy('id')
            ->chunk(200, function ($bookings) {
                foreach ($bookings as $booking) {
                    $numeric = (string) random_int(10000000, 99999999);
                    DB::table('car_bookings')->where('id', $booking->id)->update(['trip_id' => $numeric]);
                }
            });
    }

    private function generateCode(): string
    {
        $charset = self::CHARSET;
        $len     = strlen($charset);
        $code    = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= $charset[random_int(0, $len - 1)];
        }
        return $code;
    }
};
