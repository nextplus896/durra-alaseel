<?php

namespace Tests\Feature\Api\V1\User;

use App\Models\Admin\Branch;
use App\Models\Admin\CancellationPolicy;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use App\Models\CarBooking;
use App\Models\Vendor\Cars\Car;
use App\Models\Vendor\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Feature tests for the Cancellation Policy API endpoints.
 *
 * Routes tested:
 *   GET  /api/v1/cancellation-policy           → show()    (public)
 *   POST /api/v1/cancellation-policy/preview   → previewRefund()  (auth:api)
 *
 * All monetary assertions use the spec examples:
 *   Daily = 200 SAR | window = 4h | deduction = 1 day | fee = 10%
 */
class CancellationPolicyApiTest extends TestCase
{
    use RefreshDatabase;

    private Car $car;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSystemMaintenance();
        $this->artisan('passport:keys --force');

        // Build minimal car+branch hierarchy for booking factories
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
    // GET /api/v1/cancellation-policy
    // =========================================================================

    /**
     * @test
     * Public endpoint returns the active cancellation policy.
     * No authentication required.
     */
    public function public_endpoint_returns_active_policy(): void
    {
        CancellationPolicy::create([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1.00,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
        ]);

        $response = $this->getJson('/api/v1/cancellation-policy');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success')
            ->assertJsonPath('data.policy.cancellation_window_hours', 4)
            ->assertJsonPath('data.policy.deduction_type', 'day')
            ->assertJsonPath('data.policy.service_fee_type', 'percentage')
            ->assertJsonPath('data.policy.is_active', true);
    }

    /**
     * @test
     * Returns 404 when no active policy exists.
     */
    public function returns_404_when_no_active_policy(): void
    {
        // Remove the migration seed record so no policy exists
        CancellationPolicy::query()->delete();

        $response = $this->getJson('/api/v1/cancellation-policy');

        $response->assertStatus(404)
            ->assertJsonPath('type', 'error');
    }

    /**
     * @test
     * Returns 404 when policy exists but is inactive.
     */
    public function returns_404_when_policy_is_inactive(): void
    {
        // Remove migration seed (active), replace with an inactive record
        CancellationPolicy::query()->delete();
        CancellationPolicy::create([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10,
            'is_active'                 => false, // inactive
        ]);

        $response = $this->getJson('/api/v1/cancellation-policy');

        $response->assertStatus(404);
    }

    // =========================================================================
    // POST /api/v1/cancellation-policy/preview
    // =========================================================================

    /**
     * @test
     * Unauthenticated request to preview endpoint returns 401.
     */
    public function unauthenticated_preview_returns_401(): void
    {
        $response = $this->postJson('/api/v1/cancellation-policy/preview', [
            'booking_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * @test
     * Preview refund when cancelling OUTSIDE the window (10h > 4h window).
     *
     * Spec: Rental = 200 SAR | Fee = 10% = 20 SAR | Refund = 180 SAR
     */
    public function preview_outside_window_returns_only_service_fee(): void
    {
        $this->createActivePolicyDefault();

        $user = $this->createUserWithWallet();
        Passport::actingAs($user, [], 'api');

        // Pickup is 10 hours from now — outside the 4h window
        $booking = CarBooking::factory()->create([
            'user_id'     => $user->id,
            'car_id'      => $this->car->id,
            'branch_id'   => $this->branch->id,
            'amount'      => 200.00,
            'rental_days' => 1,
            'pickup_date' => now()->addHours(10)->toDateString(),
            'pickup_time' => now()->addHours(10)->format('H:i:s'),
        ]);

        $response = $this->postJson('/api/v1/cancellation-policy/preview', [
            'booking_id' => $booking->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success')
            ->assertJsonPath('data.refund.rental_amount', 200)
            ->assertJsonPath('data.refund.deduction_amount', 0)
            ->assertJsonPath('data.refund.service_fee_amount', 20)
            ->assertJsonPath('data.refund.refund_amount', 180)
            ->assertJsonPath('data.refund.is_within_window', false);
    }

    /**
     * @test
     * Preview refund when cancelling INSIDE the window (2h < 4h window).
     *
     * Spec (Case 2):
     *   Rental = 200 SAR | Deduction = 1 day = 200 | Fee = 10% = 20 | Refund = 0
     */
    public function preview_inside_window_applies_deduction_and_fee(): void
    {
        $this->createActivePolicyDefault();

        $user = $this->createUserWithWallet();
        Passport::actingAs($user, [], 'api');

        // Pickup is 2 hours from now — inside the 4h window
        $booking = CarBooking::factory()->create([
            'user_id'     => $user->id,
            'car_id'      => $this->car->id,
            'branch_id'   => $this->branch->id,
            'amount'      => 200.00,
            'rental_days' => 1,
            'pickup_date' => now()->addHours(2)->toDateString(),
            'pickup_time' => now()->addHours(2)->format('H:i:s'),
        ]);

        $response = $this->postJson('/api/v1/cancellation-policy/preview', [
            'booking_id' => $booking->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'success')
            ->assertJsonPath('data.refund.deduction_amount', 200)
            ->assertJsonPath('data.refund.service_fee_amount', 20)
            ->assertJsonPath('data.refund.refund_amount', 0)
            ->assertJsonPath('data.refund.is_within_window', true);
    }

    /**
     * @test
     * Multi-day booking inside window: spec example (3 days × 200 SAR = 600).
     *
     * Spec (Multi-Day Example):
     *   Rental = 600 | Deduction = 1 day = 200 | Fee = 10% = 60 | Refund = 340
     */
    public function preview_multi_day_inside_window_calculates_correctly(): void
    {
        $this->createActivePolicyDefault();

        $user = $this->createUserWithWallet();
        Passport::actingAs($user, [], 'api');

        $booking = CarBooking::factory()->create([
            'user_id'     => $user->id,
            'car_id'      => $this->car->id,
            'branch_id'   => $this->branch->id,
            'amount'      => 600.00,
            'rental_days' => 3,
            'pickup_date' => now()->addHours(1)->toDateString(),
            'pickup_time' => now()->addHours(1)->format('H:i:s'),
        ]);

        $response = $this->postJson('/api/v1/cancellation-policy/preview', [
            'booking_id' => $booking->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.refund.rental_amount', 600)
            ->assertJsonPath('data.refund.deduction_amount', 200)
            ->assertJsonPath('data.refund.service_fee_amount', 60)
            ->assertJsonPath('data.refund.refund_amount', 340);
    }

    /**
     * @test
     * A user cannot preview a refund for another user's booking (returns 404).
     */
    public function user_cannot_preview_another_users_booking(): void
    {
        $this->createActivePolicyDefault();

        $owner = $this->createUserWithWallet();
        $other = $this->createUserWithWallet();

        Passport::actingAs($other, [], 'api');

        $booking = CarBooking::factory()->create([
            'user_id'   => $owner->id, // belongs to owner, not $other
            'car_id'    => $this->car->id,
            'branch_id' => $this->branch->id,
            'amount'    => 200.00,
        ]);

        $response = $this->postJson('/api/v1/cancellation-policy/preview', [
            'booking_id' => $booking->id,
        ]);

        $response->assertStatus(404);
    }

    /**
     * @test
     * Validation rejects a missing booking_id.
     */
    public function preview_fails_validation_without_booking_id(): void
    {
        $user = $this->createUserWithWallet();
        Passport::actingAs($user, [], 'api');

        $response = $this->postJson('/api/v1/cancellation-policy/preview', []);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * Create the standard default global policy used in spec examples.
     *   window = 4h | deduction = 1 day | fee = 10%
     *
     * Deletes existing records first (migration seed) so the service
     * only finds this policy via CancellationPolicy::getActive().
     */
    private function createActivePolicyDefault(): CancellationPolicy
    {
        CancellationPolicy::query()->delete();

        return CancellationPolicy::create([
            'cancellation_window_hours' => 4,
            'deduction_type'            => 'day',
            'deduction_value'           => 1.00,
            'service_fee_type'          => 'percentage',
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
        ]);
    }
}
