<?php

namespace App\Models\Admin\Cars;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        if ($this->image) {
            return files_asset_path('car-models') . '/' . $this->image;
        }
        return files_asset_path('default');
    }
}
