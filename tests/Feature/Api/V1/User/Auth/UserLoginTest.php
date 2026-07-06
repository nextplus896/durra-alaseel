<?php

namespace Tests\Feature\Api\V1\User\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Feature tests for the User API login endpoint.
 * Route: POST /api/v1/login
 * Controller: Api\V1\User\Auth\LoginController@login
 * Guard: api (Passport)
 *
 * What we cover:
 *   1. Happy path — login with email, login with username
 *   2. Validation failures — missing credentials, missing password
 *   3. Auth failures — non-existent user (404), wrong password, banned account
 *
 * Why feature tests (not unit)?
 *   Feature tests exercise the full HTTP stack: routing, middleware, controller,
 *   service, and response formatting. They verify the actual API contract
 *   the mobile app depends on (JSON shape, status codes, type field).
 *
 * Response format:
 *   Success: { "type": "success", "data": { "token": "...", "user_info": {...} } }
 *   Error:   { "type": "error",   "message": { "error": [...] } }
 *
 * The SystemMaintenance row must exist with status=false to let requests
 * through the SystemMaintenanceApi middleware. seedSystemMaintenance() handles this.
 */
class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // SystemMaintenanceApi middleware reads from DB on every request.
        $this->seedSystemMaintenance();
        // passport:install creates both encryption keys AND the OAuth clients
        // (personal access client + password grant client). Without the client
        // row in oauth_clients, createToken() throws "Personal access client not found".
        $this->artisan('passport:install --force');
    }

    // =========================================================================
    // Section 1: Happy Paths
    // =========================================================================

    /**
     * @test
     * A user can log in using their email address as the credential.
     *
     * Example: Mobile app sends { "credentials": "ali@example.com", "password": "secret123" }.
     * The LoginController detects the @ symbol and uses the `email` column to look up the user.
     * On success, a Passport access token is returned for subsequent API calls.
     */
    public function user_can_login_with_email(): void
    {
        User::factory()->create([
            'email'          => 'ali@example.com',
            'password'       => bcrypt('secret123'),
            'status'         => 1,  // active
            'email_verified' => 1,  // email confirmed
        ]);

        $response = $this->postJson('/api/v1/login', [
            'credentials' => 'ali@example.com',
            'password'    => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user_info' => ['id', 'email', 'username'],
                ],
            ]);
    }

    /**
     * @test
     * A user can also log in using their username (no @ symbol = username lookup).
     *
     * Example: Mobile app sends { "credentials": "ali_driver", "password": "..." }.
     * The username field is the alternative identifier for users without email access.
     */
    public function user_can_login_with_username(): void
    {
        User::factory()->create([
            'username'       => 'ali_driver_99',
            'password'       => bcrypt('pass1234'),
            'status'         => 1,
            'email_verified' => 1,  // avoid triggering sendCodeToMail() in the login flow
        ]);

        $response = $this->postJson('/api/v1/login', [
            'credentials' => 'ali_driver_99',
            'password'    => 'pass1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success');
    }

    // =========================================================================
    // Section 2: Validation Failures
    // =========================================================================

    /**
     * @test
     * The credentials field is required. Omitting it returns an error response.
     *
     * The validator catches this before any DB query runs.
     * Mobile apps should show a "Username or email is required" message.
     */
    public function login_fails_when_credentials_field_is_missing(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'password' => 'somepass',
        ]);

        $response->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * The password field is required. Omitting it returns an error response.
     */
    public function login_fails_when_password_field_is_missing(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'credentials' => 'ali@example.com',
        ]);

        $response->assertJsonPath('type', 'error');
    }

    // =========================================================================
    // Section 3: Auth Failures
    // =========================================================================

    /**
     * @test
     * Attempting to log in with an email that doesn't exist returns 404.
     *
     * The controller checks User::where(email, ...) first, and returns 404
     * explicitly — not the generic "credentials didn't match" — so the mobile
     * app can show "Account not found. Please register."
     */
    public function login_fails_with_404_when_user_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'credentials' => 'nobody@nowhere.com',
            'password'    => 'anything',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * Correct email but wrong password returns an error response.
     *
     * The user exists but bcrypt::check() fails.
     * No token is issued. The response type is 'error'.
     */
    public function login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'valid@example.com',
            'password' => bcrypt('correct-password'),
            'status'   => 1,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'credentials' => 'valid@example.com',
            'password'    => 'wrong-password',
        ]);

        $response->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * A banned user (status=0) cannot log in even with correct credentials.
     *
     * Example: Admin banned a user for violating terms of service.
     * The login controller checks status after verifying the password.
     * Banned users see "Your account is temporarily banned" message.
     */
    public function banned_user_cannot_login(): void
    {
        User::factory()->create([
            'email'    => 'banned@example.com',
            'password' => bcrypt('validpass'),
            'status'   => 0,  // BANNED
        ]);

        $response = $this->postJson('/api/v1/login', [
            'credentials' => 'banned@example.com',
            'password'    => 'validpass',
        ]);

        $response->assertJsonPath('type', 'error');
    }
}
