<?php

namespace App\Models\Admin\Cars;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CarModel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id'          => 'integer',
        'car_type_id' => 'integer',
        'name'        => 'string',
        'image'       => 'string',
        'status'      => 'integer',
    ];

    protected $appends = ['image_url'];

    public function carType()
    {
        return $this->belongsTo(CarType::class, 'car_type_id');
    }

    public function cars()
    {
        return $this->hasMany(\App\Models\Vendor\Cars\Car::class, 'car_model_id');
    }

    /**
     * Get full URL for the car model image
     */
    public function getImageUrlAttribute()
    {
        $image = $this->image;
        if (!$image) return files_asset_path('default');

        // Handle JSON encoded array or array values
        if (is_string($image) && (Str::startsWith($image, "[") || Str::startsWith($image, '"'))) {
            $decoded = json_decode($image, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $image = $decoded[0];
            }
        }

        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        if (!$image) return files_asset_path('default');

        // If already a full URL, return as-is
        if (Str::startsWith($image, 'http')) {
            return $image;
        }

        return files_asset_path('car-models') . '/' . $image;
    }
}
