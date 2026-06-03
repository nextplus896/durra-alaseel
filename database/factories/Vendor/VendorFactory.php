<?php

namespace Database\Factories\Vendor;

use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Vendor factory — creates a fully active vendor account.
 *
 * The Vendor model uses `auth:vendor_api` guard and HasApiTokens (Passport).
 * Tests that call Passport::actingAs($vendor, [], 'vendor_api') must create
 * a vendor through this factory.
 *
 * Default state: active (status=1), email verified, KYC approved.
 * Password defaults to "password" so login tests can use a known credential.
 *
 * Example:
 *   $vendor = Vendor::factory()->create();
 *   Passport::actingAs($vendor, [], 'vendor_api');
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'firstname'      => fake()->firstName(),
            'lastname'       => fake()->lastName(),
            'username'       => fake()->unique()->userName() . fake()->numberBetween(1, 9999),
            'email'          => fake()->unique()->safeEmail(),
            'status'         => 1,
            'email_verified' => 1,
            'kyc_verified'   => 1, // GlobalConst::VERIFIED
            'password'       => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State: vendor account is banned.
     * Used in login-rejection tests.
     */
    public function banned(): static
    {
        return $this->state(['status' => 0]);
    }
}
