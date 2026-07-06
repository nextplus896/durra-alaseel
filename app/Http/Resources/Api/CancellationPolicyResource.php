<?php

namespace App\Http\Resources\Api;

use App\Constants\CancellationPolicyConst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialises the global CancellationPolicy model for mobile API consumers.
 *
 * Includes deduction_types and fee_types arrays so the Flutter/mobile
 * client can render dropdowns without hard-coding string constants.
 */
class CancellationPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => (int) $this->id,
            'cancellation_window_hours' => (int) $this->cancellation_window_hours,
            'deduction_type'            => (string) $this->deduction_type,
            'deduction_value'           => $this->deduction_value !== null ? (float) $this->deduction_value : 0.0,
            'service_fee_type'          => (string) $this->service_fee_type,
            'service_fee_value'         => $this->service_fee_value !== null ? (float) $this->service_fee_value : 0.0,
            'is_active'                 => (bool) $this->is_active,
            'updated_at'                => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            // Human-readable labels for UI rendering
            'deduction_type_label'      => (string) ($this->deduction_type_label ?? $this->deduction_type),
            'service_fee_label'         => (string) ($this->service_fee_label ?? $this->service_fee_type),

            // Available options for client-side dropdowns
            'deduction_types'           => CancellationPolicyConst::DEDUCTION_TYPE_LABELS,
            'fee_types'                 => CancellationPolicyConst::FEE_TYPE_LABELS,
        ];
    }
}
