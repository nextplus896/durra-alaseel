<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the legacy `balance` column from `users`.
     *
     * Balance is now stored exclusively in `user_wallets` (one row per currency
     * per user). The User::getBalanceAttribute() accessor reads from there.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 28, 8)->default(0)->after('kyc_verified');
        });
    }
};
