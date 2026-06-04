<?php

namespace Tests\Feature\Api\V1\User;

use App\Models\Admin\BasicSettings;
use App\Models\Admin\Branch;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\CarBooking;
use App\Models\Vendor\Cars\Car;
use App\Models\Vendor\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Feature tests for the Booking Extension API.
 * Route: POST /api/v1/user/car-booking/extend
 * Controller: Api\V1\User\CarBookingController@extendBooking
 *
 * What we cover:
 *   1. Happy path — extend with cash payment
 *   2. Happy path — extend with balance (wallet) payment
 *   3. Auth guard — unauthenticated request rejected with 401
 *   4. Business rule — extension days required / validated
 *   5. Business rule — insufficient balance rejected
 *   6. Ownership — another user cannot extend this booking
 *
 * Setup:
 *   - 15% VAT seeded in BasicSettings
 *   - ONGOING booking (status=2) for a 100 SAR/day car
 *   - User wallet loaded with 5000 SAR for payment tests
 *
 * Note: The `extend` route requires `booking_id` in the request body,
 * not in the URL path. See routes/api/v1/user.php for the exact shape.
 */
class BookingExtensionTest extends TestCase
{
    use RefreshDatabase;

    private CarBooking $booking;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seedSystemMaintenance();
        $this->artisan('passport:keys --force');

        // Tax must be seeded so cost calculation is deterministic.
        BasicSettings::factory()->create(['tax_status' => true, 'tax_percentage' => 15.00]);

        $vendor   = Vendor::factory()->create();
        $carType  = CarType::factory()->create();
        $carModel = CarModel::factory()->create(['car_type_id' => $carType->id]);
        $branch   = Branch::factory()->create();

        $car = Car::factory()->create([
            'vendor_id'      => $vendor->id,
            'car_type_id'    => $carType->id,
            'car_model_id'   => $carModel->id,
            'branch_id'      => $branch->id,
            'price_per_day'  => 100.00,
        ]);

        $this->user    = $this->createUserWithWallet(5000.00);
        $this->booking = CarBooking::factory()->ongoing()->create([
            'car_id'               => $car->id,
            'user_id'              => $this->user->id,
            'branch_id'            => $branch->id,
            'rental_days'          => 5,
            'total_extension_days' => 0,
        ]);
    }

    // =========================================================================
    // Happy Paths
    // =========================================================================

    /**
     * @test
     * A user can extend their ONGOING booking by paying with cash.
     *
     * Example: Customer calls and asks to keep the car 3 more days.
     * Vendor agent extends on their behalf via the mobile API.
     * Cash payment means they settle at pickup. A booking_extensions row is created.
     */
    public function user_can_extend_booking_with_cash(): void
    {
        Passport::actingAs($this->user, [], 'api');

        $response = $this->postJson('/api/v1/user/car-booking/extend', [
            'booking_id'     => $this->booking->id,
            'extension_days' => 3,
            'payment_type'   => 'cash',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success');

        $this->assertDatabaseHas('booking_extensions', [
            'car_booking_id' => $this->booking->id,
            'extension_days' => 3,
        ]);
    }

    /**
     * @test
     * A user can extend their booking and pay from their wallet balance.
     *
     * Example: Customer extends for 2 more days → 2 × 100 SAR + 15% VAT = 230 SAR.
     * The wallet is debited and a balance_transactions row is created as the audit trail.
     */
    public function user_can_extend_booking_with_balance_payment(): void
    {
        Passport::actingAs($this->user, [], 'api');

        $response = $this->postJson('/api/v1/user/car-booking/extend', [
            'booking_id'     => $this->booking->id,
            'extension_days' => 2,
            'payment_type'   => 'balance',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success');

        // 2 days × 100 SAR × 1.15 = 230 SAR deducted from 5000 SAR
        $this->assertEquals(4770.00, $this->user->fresh()->balance,
            'Wallet must be debited by 230 SAR (2 days + 15% VAT)');
    }

    // =========================================================================
    // Auth Guard
    // =========================================================================

    /**
     * @test
     * An unauthenticated POST to the extend endpoint returns 401.
     *
     * Security: without authentication, anyone who knows a booking_id could
     * try to extend bookings. The auth:api middleware must block this.
     */
    public function unauthenticated_extend_request_returns_401(): void
    {
        $response = $this->postJson('/api/v1/user/car-booking/extend', [
            'booking_id'     => $this->booking->id,
            'extension_days' => 3,
            'payment_type'   => 'cash',
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // Business Rule Failures
    // =========================================================================

    /**
     * @test
     * Requesting 0 extension days returns an error.
     *
     * The CarBookingExtensionService validates extension_days must be ≥ 1.
     * A zero-day extension is meaningless and likely a client bug.
     */
    public function extension_fails_with_zero_extension_days(): void
    {
        Passport::actingAs($this->user, [], 'api');

        $response = $this->postJson('/api/v1/user/car-booking/extend', [
            'booking_id'     => $this->booking->id,
            'extension_days' => 0,
            'payment_type'   => 'cash',
        ]);

        $response->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * If the user's wallet balance is less than the extension cost, the
     * server returns an error and no balance is deducted.
     *
     * Example: Extension costs 575 SAR (5 days + VAT) but user only has 50 SAR.
     * The service throws "Insufficient balance" before any DB write.
     */
    public function extension_fails_when_balance_is_insufficient(): void
    {
        // Replace booking owner with a user who has very little balance.
        $poorUser = $this->createUserWithWallet(50.00);
        $this->booking->update(['user_id' => $poorUser->id]);

        Passport::actingAs($poorUser, [], 'api');

        $response = $this->postJson('/api/v1/user/car-booking/extend', [
            'booking_id'     => $this->booking->id,
            'extension_days' => 5,
            'payment_type'   => 'balance',
        ]);

        $response->assertJsonPath('type', 'error');
        // Wallet balance must be unchanged.
        $this->assertEquals(50.00, $poorUser->fresh()->balance);
    }

    /**
     * @test
     * A different user cannot extend a booking they don't own.
     *
     * Example: User B knows User A's booking_id (e.g., via a guessed ID).
     * The service checks booking.user_id === auth()->id() and throws.
     * This is the ownership check, separate from the authentication check.
     */
    public function extension_fails_when_user_does_not_own_the_booking(): void
    {
        $otherUser = $this->createUserWithWallet(5000.00);
        Passport::actingAs($otherUser, [], 'api');

        $response = $this->postJson('/api/v1/user/car-booking/extend', [
            'booking_id'     => $this->booking->id, // owned by $this->user
            'extension_days' => 3,
            'payment_type'   => 'cash',
        ]);

        $response->assertJsonPath('type', 'error');
    }
}
