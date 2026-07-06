<?php

namespace Tests;

use App\Models\Admin\Currency;
use App\Models\Admin\SystemMaintenance;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable FK enforcement in SQLite so tests can seed data without
        // creating full admin/vendor records just to satisfy FK chains.
        // Production (MySQL) still enforces FKs — this only affects tests.
        if (config('database.default') === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
        }
    }

    /**
     * Seed a SystemMaintenance row with status=0 (not in maintenance).
     *
     * Why: The SystemMaintenanceApi middleware calls SystemMaintenance::first()
     * on every API request. If no row exists it will throw a null-dereference
     * error before any controller logic runs, making every API test fail with
     * a 500 instead of the expected response.
     *
     * How to apply: Call this once at the top of any Feature test setUp().
     */
    protected function seedSystemMaintenance(): void
    {
        SystemMaintenance::create([
            'slug'   => 'system-maintenance',
            'title'  => 'Maintenance',
            'status' => false, // false = NOT in maintenance mode
        ]);
    }

    /**
     * Create a User with an active default-currency wallet loaded with $balance.
     *
     * Why: WalletService, BookingBalanceService, and all balance-related tests
     * require a user_wallets row that is joined via a default Currency. Without
     * this row, User::getBalanceAttribute() always returns 0.0 and wallet
     * operations throw "first or fail" exceptions.
     *
     * Example:
     *   $user = $this->createUserWithWallet(500.00);
     *   $user->balance; // → 500.0
     */
    protected function createUserWithWallet(float $balance = 0.0): User
    {
        // Reuse the existing default SAR currency if it was already created in
        // this test (e.g., a previous createUserWithWallet() call in setUp()).
        // currencies.code has a UNIQUE constraint, so creating twice would throw.
        $currency = Currency::where('code', 'SAR')->first()
            ?? Currency::create([
                'admin_id' => 1,
                'country'  => 'Saudi Arabia',
                'name'     => 'Saudi Riyal',
                'code'     => 'SAR',
                'symbol'   => '﷼',
                'type'     => 'FIAT',
                'rate'     => 1.00,
                'default'  => true,
                'status'   => true,
            ]);

        $user = User::factory()->create([
            'status'         => 1,
            'email_verified' => 1,
        ]);

        UserWallet::create([
            'user_id'     => $user->id,
            'currency_id' => $currency->id,
            'balance'     => $balance,
            'status'      => true,
        ]);

        return $user->fresh();
    }
}
