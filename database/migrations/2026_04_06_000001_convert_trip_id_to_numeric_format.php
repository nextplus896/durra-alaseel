<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert all existing trip_id values to 7-digit numeric format:
     * 2-digit year (from booking's created_at) + 5 random digits.
     */
    public function up(): void
    {
        $used = [];

        DB::table('car_bookings')
            ->orderBy('id')
            ->chunk(200, function ($bookings) use (&$used) {
                foreach ($bookings as $booking) {
                    $yearPrefix = $booking->created_at
                        ? date('y', strtotime($booking->created_at))
                        : date('y');

                    do {
                        $code = $yearPrefix . str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                    } while (
                        in_array($code, $used, true) ||
                        DB::table('car_bookings')->where('trip_id', $code)->where('id', '!=', $booking->id)->exists()
                    );

                    $used[] = $code;
                    DB::table('car_bookings')->where('id', $booking->id)->update(['trip_id' => $code]);
                }
            });
    }

    /**
     * Rollback: re-generate trip_ids to 6-char alphanumeric format (best effort).
     */
    public function down(): void
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len   = strlen($chars);
        $used  = [];

        DB::table('car_bookings')
            ->orderBy('id')
            ->chunk(200, function ($bookings) use (&$used, $chars, $len) {
                foreach ($bookings as $booking) {
                    do {
                        $code = '';
                        for ($i = 0; $i < 6; $i++) {
                            $code .= $chars[random_int(0, $len - 1)];
                        }
                    } while (
                        in_array($code, $used, true) ||
                        DB::table('car_bookings')->where('trip_id', $code)->where('id', '!=', $booking->id)->exists()
                    );

                    $used[] = $code;
                    DB::table('car_bookings')->where('id', $booking->id)->update(['trip_id' => $code]);
                }
            });
    }
};
