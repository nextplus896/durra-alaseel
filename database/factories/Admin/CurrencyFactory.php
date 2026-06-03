<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Currency factory — generates a currency row for use in wallet tests.
 *
 * WalletService and User::getBalanceAttribute() both look for a wallet that
 * is linked to a Currency where `default = true`. If no default currency
 * exists, balance always reads as 0.0 and WalletService throws.
 *
 * admin_id=1 is a placeholder; SQLite does not enforce the FK.
 *
 * Usage:
 *   Currency::factory()->create();              // non-default (fiat, SAR-like)
 *   Currency::factory()->asDefault()->create(); // marks this currency as default
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'admin_id' => 1,
            'country'  => fake()->country(),
            'name'     => 'Saudi Riyal',
            'code'     => 'SAR',
            'symbol'   => '﷼',
            'type'     => 'FIAT',
            'rate'     => 1.00,
            'sender'   => false,
            'receiver' => false,
            'default'  => false,
            'status'   => true,
        ];
    }

    /**
     * State: marks this currency as the platform default.
     *
     * UserWallet rows linked to this currency are the ones that
     * User::getBalanceAttribute() and WalletService read from.
     */
    public function asDefault(): static
    {
        return $this->state(['default' => true, 'code' => 'SAR']);
    }
}
