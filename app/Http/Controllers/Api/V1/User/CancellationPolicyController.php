<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Http\Resources\Api\CancellationPolicyResource;
use App\Http\Resources\Api\CancellationRefundResource;
use App\Models\Admin\CancellationPolicy;
use App\Models\CarBooking;
use App\Services\CancellationRefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Exposes the global cancellation policy and refund calculator to mobile clients.
 *
 * Routes:
 *   GET  /api/v1/cancellation-policy           → show()
 *   POST /api/v1/cancellation-policy/preview   → previewRefund()  (auth:api)
 */
class CancellationPolicyController extends Controller
{
    public function __construct(
        private readonly CancellationRefundService $refundService,
    ) {}

    // -------------------------------------------------------------------------
    // GET /api/v1/cancellation-policy
    // -------------------------------------------------------------------------

    /**
     * Return the active global cancellation policy.
     *
     * Public endpoint — no authentication required.
     * Mobile clients display this on the booking cancellation confirmation screen.
     */
    public function show()
    {
        try {
            $policy = CancellationPolicy::getActive();

            if ($policy === null) {
                return Response::error(
                    [__('No active cancellation policy configured.')],
                    null,
                    404
                );
            }

            return Response::success(
                [__('Cancellation policy retrieved successfully.')],
                ['policy' => new CancellationPolicyResource($policy)],
                200
            );
        } catch (Exception $e) {
            Log::error('CancellationPolicy Show Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error([__('Unable to retrieve cancellation policy.')], null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/cancellation-policy/preview
    // -------------------------------------------------------------------------

    /**
     * Preview the refund breakdown for a specific booking cancellation.
     *
     * Requires authentication. The booking must belong to the authenticated user.
     * This is a read-only calculation — no booking state is changed.
     *
     * Request body:
     *   booking_id  int  required  ID of the booking to preview cancellation for
     */
    public function previewRefund(Request $request)
    {
        $user = Auth::guard('api')->user();

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:car_bookings,id',
        ]);

        if ($validator->fails()) {
            return Response::error(
                $validator->errors()->all(),
                null,
                422
            );
        }

        try {
            $booking = CarBooking::where('id', $request->integer('booking_id'))
                ->where('user_id', $user->id)
                ->first();

            if ($booking === null) {
                return Response::error(
                    [__('Booking not found or does not belong to you.')],
                    null,
                    404
                );
            }

            $refundDto = $this->refundService->calculate($booking);

            return Response::success(
                [__('Refund preview calculated successfully.')],
                ['refund' => new CancellationRefundResource($refundDto)],
                200
            );
        } catch (Exception $e) {
            Log::error('CancellationPolicy PreviewRefund Error: ' . $e->getMessage(), [
                'user_id'    => $user->id ?? null,
                'booking_id' => $request->integer('booking_id'),
                'trace'      => $e->getTraceAsString(),
            ]);

            return Response::error([__('Unable to calculate refund preview.')], null, 500);
        }
    }
}
