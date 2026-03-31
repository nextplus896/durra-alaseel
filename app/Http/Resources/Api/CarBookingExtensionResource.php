<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maps a CarBookingTransaction (type=extension) to the extension API shape.
 */
class CarBookingExtensionResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                   => (int)   $this->id,
            'car_booking_id'       => (int)   $this->car_booking_id,
            'extension_days'       => (int)   ($this->extension_days ?? 0),
            'previous_return_date' => $this->previous_return_date
                ? $this->previous_return_date->toDateString()
                : null,
            'new_return_date'      => $this->new_return_date
                ? $this->new_return_date->toDateString()
                : null,
            'rental_fees'          => (float) ($this->amount ?? 0),
            'tax_percentage'       => (float) ($this->tax_percentage ?? 0),
            'tax_amount'           => (float) ($this->tax_amount ?? 0),
            'total_cost'           => (float) ($this->total ?? 0),
            'daily_rate'           => $this->daily_rate !== null
                ? (float) $this->daily_rate
                : null,
            'transacted_at'        => $this->transacted_at
                ? $this->transacted_at->toIso8601String()
                : null,
            'created_at'           => $this->created_at
                ? $this->created_at->toIso8601String()
                : null,
        ];
    }
}
