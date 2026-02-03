<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
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
            'vendor_id' => (int) $this->vendor_id,
            'car_area_id' => $this->car_area_id ? (int) $this->car_area_id : null,
            'car_type_id' => $this->car_type_id ? (int) $this->car_type_id : null,
            'car_model_id' => $this->car_model_id ? (int) $this->car_model_id : null,
            'branch_id' => $this->branch_id ? (int) $this->branch_id : null,
            'car_title' => $this->car_title ?? null,
            'slug' => (string) ($this->slug ?? ''),
            'image' => (string) ($this->image ?? ''),
            'image_url' => (string) ($this->image_url ?? ''),
            'car_type' => $this->type ? (string) $this->type->name : '',
            'car_model' => $this->carModel ? (string) $this->carModel->name : (string) ($this->car_model ?? ''),
            'car_number' => (string) ($this->car_number ?? ''),
            'seat' => $this->seat ? (int) $this->seat : 0,
            'year' => $this->year ? (int) $this->year : null,
            'experience' => $this->experience ? (int) $this->experience : 0,
            'fees' => $this->fees ? (float) $this->fees : 0.0,
            'price_per_day' => $this->price_per_day ? (float) $this->price_per_day : 0.0,
            'price_per_week' => $this->price_per_week ? (float) $this->price_per_week : null,
            'price_per_month' => $this->price_per_month ? (float) $this->price_per_month : null,
            'allowance_km' => $this->allowance_km ? (float) $this->allowance_km : null,
            'allowance_price_per_km' => $this->allowance_price_per_km ? (float) $this->allowance_price_per_km : null,
            'status' => (int) $this->status,
            'approval' => (int) $this->approval,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            // Relationships
            'type' => $this->whenLoaded('type'),
            'car_model_relation' => $this->whenLoaded('carModel'),
            'area' => $this->whenLoaded('area'),
            'branch' => $this->whenLoaded('branch'),
            'vendor' => $this->whenLoaded('vendor'),
        ];
    }
}
