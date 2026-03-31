<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Services\DeliveryRadiusService;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    /**
     * Check whether a user-selected delivery location is within the
     * allowed radius for a given branch.
     *
     * POST /api/v1/user/check-delivery-area
     *
     * Request body:
     *   branch_id  integer  required
     *   lat        numeric  required  (-90 to 90)
     *   lng        numeric  required  (-180 to 180)
     *
     * Response:
     *   {
     *     "status": true,
     *     "data": {
     *       "allowed": true,
     *       "distance_km": 4.2,
     *       "max_radius": 10.0
     *     }
     *   }
     */
    public function checkDeliveryArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:branches,id',
            'lat'       => 'required|numeric|between:-90,90',
            'lng'       => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        $validated = $validator->validate();

        try {
            $result = app(DeliveryRadiusService::class)->checkDeliveryEligibility(
                (int) $validated['branch_id'],
                (float) $validated['lat'],
                (float) $validated['lng']
            );

            return Response::success([__('Delivery area check completed.')], $result, 200);
        } catch (Exception $e) {
            return Response::error([__('Something went wrong! Please try again.')], [], 500);
        }
    }
}
