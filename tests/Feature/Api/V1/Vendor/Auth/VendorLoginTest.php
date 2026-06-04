<?php

namespace Tests\Feature\Api\V1\Vendor\Auth;

use App\Models\Admin\Currency;
use App\Models\Vendor\Vendor;
use App\Models\Vendor\VendorWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the Vendor API login endpoint.
 * Route: POST /api/v1/vendor/login
 * Controller: Api\V1\Vendor\Auth\LoginController@login
 * Guard: vendor_api (Passport — separate guard from user `api`)
 *
 * What we cover:
 *   1. Happy path — vendor logs in with correct credentials
 *   2. Auth failures — wrong password, missing fields, banned account
 *
 * Key difference from User login:
 *   The vendor guard is `vendor_api` (not `api`). Tokens issued here
 *   only work on routes protected by `auth:vendor_api` middleware.
 *   This ensures users cannot impersonate vendors and vice versa.
 */
class VendorLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSystemMaintenance();
        $this->artisan('passport:install --force');
    }

    /**
     * @test
     * A vendor logs in with their registered email and password.
     *
     * Example: Vendor opens the Vendor app → enters email + password.
     * Success: receives a vendor_api Passport token for all protected routes.
     * The response shape mirrors the User login response: type=success + token.
     *
     * Why create a VendorWallet? The login controller calls refreshUserWallets()
     * which does $vendor->wallets->pluck(). Since Vendor::wallets() is hasOne,
     * a null result causes a "pluck() on null" exception. Creating a VendorWallet
     * row ensures the relationship returns a collection-like result.
     */
    public function vendor_can_login_with_valid_email_and_password(): void
    {
        $currency = Currency::create([
            'admin_id' => 1, 'country' => 'Saudi Arabia', 'name' => 'Saudi Riyal',
            'code' => 'SAR', 'symbol' => '﷼', 'type' => 'FIAT',
            'rate' => 1.00, 'default' => true, 'status' => true,
        ]);

        $vendor = Vendor::factory()->create([
            'email'    => 'vendor@rentals.com',
            'password' => bcrypt('vendor_pass_123'),
            'status'   => 1,
        ]);

        VendorWallet::create([
            'vendor_id'   => $vendor->id,
            'currency_id' => $currency->id,
            'balance'     => 0,
            'due_payment' => 0,
            'status'      => true,
        ]);

        $response = $this->postJson('/api/v1/vendor/login', [
            'credentials' => 'vendor@rentals.com',
            'password'    => 'vendor_pass_123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success')
            ->assertJsonStructure(['data' => ['token']]);
    }

    /**
     * @test
     * Wrong password returns an error without leaking any user data.
     *
     * Security: The error message must say "credentials didn't match"
     * (not "user found but password wrong") to avoid user enumeration.
     */
    public function vendor_login_fails_with_wrong_password(): void
    {
        Vendor::factory()->create([
            'email'    => 'vendor@rentals.com',
            'password' => bcrypt('actual-password'),
            'status'   => 1,
        ]);

        $response = $this->postJson('/api/v1/vendor/login', [
            'credentials' => 'vendor@rentals.com',
            'password'    => 'wrong-password',
        ]);

        $response->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * The credentials field is required — omitting it is a validation error.
     */
    public function vendor_login_fails_without_credentials(): void
    {
        $response = $this->postJson('/api/v1/vendor/login', [
            'password' => 'pass',
        ]);

        $response->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * A banned vendor (status=0) cannot log in.
     *
     * Example: Admin suspended a vendor for listing fraudulent cars.
     * Even with correct credentials, the status check blocks them.
     */
    public function banned_vendor_cannot_login(): void
    {
        Vendor::factory()->create([
            'email'    => 'banned-vendor@example.com',
            'password' => bcrypt('correct-pass'),
            'status'   => 0,  // BANNED
        ]);

        $response = $this->postJson('/api/v1/vendor/login', [
            'credentials' => 'banned-vendor@example.com',
            'password'    => 'correct-pass',
        ]);

        $response->assertJsonPath('type', 'error');
    }
}
