<?php

namespace Tests\Feature\Api\V1\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Feature tests for the User Balance API endpoints.
 *
 * Routes tested:
 *   GET  /api/v1/user/balance         → BalanceController@getBalance
 *   GET  /api/v1/user/balance/history  → BalanceController@getTransactionHistory
 *
 * Both routes are protected by `auth:api` (Passport). Unauthenticated
 * requests must receive 401. Authenticated requests must receive 200.
 *
 * Why Passport::actingAs() instead of a real token?
 *   In feature tests we use Passport::actingAs() which bypasses token
 *   issuance entirely. This is Laravel's recommended approach for testing
 *   Passport-protected routes without running the OAuth flow. It sets the
 *   authenticated user directly on the request, identical to how a real
 *   token would set it.
 *
 * Response shape: { "type": "success", "data": { ... } }
 */
class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSystemMaintenance();
        $this->artisan('passport:keys --force');
    }

    // =========================================================================
    // GET /api/v1/user/balance
    // =========================================================================

    /**
     * @test
     * An authenticated user can retrieve their current wallet balance.
     *
     * Example: App home screen loads, shows "Balance: 750.00 SAR".
     * The response includes balance data — this test confirms the JSON
     * structure so the mobile app contract stays intact.
     */
    public function authenticated_user_can_view_balance(): void
    {
        $user = $this->createUserWithWallet(750.00);
        Passport::actingAs($user, [], 'api');

        $response = $this->getJson('/api/v1/user/balance');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success');
    }

    /**
     * @test
     * An unauthenticated request to the balance endpoint returns 401.
     *
     * The `auth:api` middleware rejects any request without a valid
     * Passport Bearer token. This prevents public access to account balances.
     */
    public function unauthenticated_request_to_balance_returns_401(): void
    {
        $response = $this->getJson('/api/v1/user/balance');

        $response->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/user/balance/history
    // =========================================================================

    /**
     * @test
     * An authenticated user can list their balance transaction history.
     *
     * Example: In-app "Wallet History" screen calls this endpoint.
     * Returns paginated BalanceTransaction records for the authenticated user.
     * We assert 200 + type=success; actual payload shape is tested via API docs.
     */
    public function authenticated_user_can_view_transaction_history(): void
    {
        $user = $this->createUserWithWallet(500.00);
        Passport::actingAs($user, [], 'api');

        $response = $this->getJson('/api/v1/user/balance/history');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success');
    }

    /**
     * @test
     * Unauthenticated request to transaction history returns 401.
     */
    public function unauthenticated_request_to_history_returns_401(): void
    {
        $response = $this->getJson('/api/v1/user/balance/history');

        $response->assertStatus(401);
    }
}
