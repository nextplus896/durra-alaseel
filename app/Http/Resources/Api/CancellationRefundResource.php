<?php

namespace App\Http\Resources\Api;

use App\DTO\CancellationRefundDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialises a CancellationRefundDTO for the mobile API.
 *
 * The resource wraps a DTO (not a model), so $this->resource
 * is a CancellationRefundDTO instance.
 *
 * @mixin CancellationRefundDTO
 */
class CancellationRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CancellationRefundDTO $dto */
        $dto = $this->resource;

        return [
            'rental_amount'             => (float) $dto->rental_amount,
            'deduction_amount'          => (float) $dto->deduction_amount,
            'service_fee_amount'        => (float) $dto->service_fee_amount,
            'refund_amount'             => (float) $dto->refund_amount,
            'is_within_window'          => (bool)  $dto->is_within_window,
            'hours_until_pickup'        => (float) $dto->hours_until_pickup,
            'cancellation_window_hours' => (int)   $dto->cancellation_window_hours,

            // Explanation strings for the mobile UI
            'summary' => $this->buildSummary($dto),
        ];
    }

    /**
     * Build a human-readable breakdown string for the UI.
     */
    private function buildSummary(CancellationRefundDTO $dto): string
    {
        if ($dto->is_within_window) {
            return __('Cancellation inside the window. Rental deduction and service fee applied.');
        }

        return __('Cancellation outside the window. Only service fee applied.');
    }
}
