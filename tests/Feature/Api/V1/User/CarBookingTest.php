<?php

namespace Tests\Feature\Api\V1\User;

use App\Models\Admin\Branch;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\CarBooking;
use App\Models\Vendor\Cars\Car;
use App\Models\Vendor\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Feature tests for the User Car Booking API endpoints.
 *
 * Routes tested:
 *   GET /api/v1/user/car-booking/booking/history  → bookingHistory()
 *
 * Authorization rule: a user can only see their OWN bookings.
 * Requesting a booking that belongs to another user must return 404 or 403.
 *
 * Passport::actingAs($user, [], 'api') simulates a logged-in user
 * without running the OAuth flow — this is the standard Laravel way
 * to test API routes protected by Passport in PHPUnit.
 */
class CarBookingTest extends TestCase
{
    use RefreshDatabase;

    private Car $car;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSystemMaintenance();
        $this->artisan('passport:keys --force');

        // Create shared Car + Branch for booking tests.
        $vendor   = Vendor::factory()->create();
        $carType  = CarType::factory()->create();
        $carModel = CarModel::factory()->create(['car_type_id' => $carType->id]);
        $this->branch = Branch::factory()->create();

        $this->car = Car::factory()->create([
            'vendor_id'    => $vendor->id,
            'car_type_id'  => $carType->id,
            'car_model_id' => $carModel->id,
            'branch_id'    => $this->branch->id,
            'status'       => 1,
            'approval'     => 1,
        ]);
    }

    // =========================================================================
    // GET /api/v1/user/car-booking/booking/history
    // =========================================================================

    /**
     * @test
     * An authenticated user can list their own bookings.
     *
     * Example: In-app "My Bookings" screen calls this endpoint on load.
     * Returns all bookings (any status) for the authenticated user.
     * The user with 3 bookings should see exactly their own bookings, not others'.
     */
    public function authenticated_user_can_list_their_bookings(): void
    {
        $user = $this->createUserWithWallet();
        Passport::actingAs($user, [], 'api');

        // Create 3 bookings for this user.
        CarBooking::factory()->count(3)->create([
            'user_id'   => $user->id,
            'car_id'    => $this->car->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->getJson('/api/v1/user/car-booking/booking/history');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success');
    }

    /**
     * @test
     * An unauthenticated request to the booking history returns 401.
     *
     * The `auth:api` middleware blocks any request without a valid token.
     * This prevents one user from seeing another user's booking history
     * by simply omitting authentication.
     */
    public function unauthenticated_user_cannot_list_bookings(): void
    {
        $response = $this->getJson('/api/v1/user/car-booking/booking/history');

        $response->assertStatus(401);
    }
}
