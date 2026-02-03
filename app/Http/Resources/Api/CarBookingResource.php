<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CarBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array with explicit type casting
     * to prevent Flutter type mismatch errors
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'trip_id' => $this->trip_id ? (int) $this->trip_id : null,
            'car_id' => (int) $this->car_id,
            'user_id' => (int) $this->user_id,
            'branch_id' => $this->branch_id ? (int) $this->branch_id : null,
            'slug' => (string) ($this->slug ?? ''),
            'rental_days' => $this->rental_days ? (int) $this->rental_days : 0,
            'allowance_km' => $this->allowance_km ? (int) $this->allowance_km : null,
            'car_model' => (string) ($this->car_model ?? ''),
            'car_number' => (string) ($this->car_number ?? ''),
            'location' => (string) ($this->location ?? ''),
            'pickup_latitude' => $this->pickup_latitude ? (float) $this->pickup_latitude : null,
            'pickup_longitude' => $this->pickup_longitude ? (float) $this->pickup_longitude : null,
            'pickup_address' => (string) ($this->pickup_address ?? ''),
            'pickup_time' => (string) ($this->pickup_time ?? ''),
            'pickup_date' => (string) ($this->pickup_date ?? ''),
            'round_pickup_time' => (string) ($this->round_pickup_time ?? ''),
            'round_pickup_date' => (string) ($this->round_pickup_date ?? ''),
            'destination' => (string) ($this->destination ?? ''),
            'phone' => (string) ($this->phone ?? ''),
            'email' => (string) ($this->email ?? ''),
            'type' => (string) ($this->type ?? ''),
            'message' => (string) ($this->message ?? ''),
            'status' => (int) $this->status,
            'payment_type' => (string) ($this->payment_type ?? ''),
            'trx_id' => (string) ($this->trx_id ?? ''),
            'amount' => $this->amount ? (float) $this->amount : 0.0,
            'charges' => $this->charges ? (float) $this->charges : 0.0,
            'delivery_fee' => $this->delivery_fee ? (float) $this->delivery_fee : 0.0,
            'tax_amount' => $this->tax_amount ? (float) $this->tax_amount : 0.0,
            'tax_percentage' => $this->tax_percentage ? (float) $this->tax_percentage : 0.0,
            'subtotal' => $this->subtotal ? (float) $this->subtotal : 0.0,
            'total_amount' => $this->total_amount ? (float) $this->total_amount : 0.0,
            'distance' => $this->distance ? (float) $this->distance : null,
            'balance_deducted' => $this->balance_deducted ? (float) $this->balance_deducted : null,
            'is_delivery' => (bool) $this->is_delivery,
            'paid_from_balance' => (bool) $this->paid_from_balance,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'cars' => $this->whenLoaded('cars', function () {
                return new CarResource($this->cars);
            }),
        ];
    }
}
