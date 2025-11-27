<?php

namespace App\Models\Vendor\Cars;

use App\Models\Admin\Cars\CarArea;
use App\Models\Admin\Cars\CarType;
use App\Models\Admin\Cars\CarModel;
use App\Models\CarBooking;
use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id'           => 'integer',
        'vendor_id'    => 'integer',
        'car_area_id'  => 'integer',
        'car_type_id'  => 'integer',
        'car_model_id' => 'integer',
        'car_title'    => 'object',
        'slug'         => 'string',
        'image'        => 'string',
        'car_model'    => 'string',
        'car_number'   => 'string',
        'seat'         => 'integer',
        'year'         => 'integer',
        'experience'   => 'integer',
        'fees'         => 'decimal:8',
        'status'       => 'integer',
        'approval'     => 'integer',
    ];

    protected $appends = ['image_url'];

    public function type()
    {
        return $this->belongsTo(CarType::class, 'car_type_id');
    }
    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }
    public function area()
    {
        return $this->belongsTo(CarArea::class, 'car_area_id');
    }
    public function branch()
    {
        return $this->belongsTo(CarArea::class, 'car_area_id');
    }
    public function bookings()
    {
        return $this->hasMany(CarBooking::class, 'car_id', 'id');
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    /**
     * Get full URL for the car image (stored from car model)
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return files_asset_path('car-models') . '/' . $this->image;
        }
        return files_asset_path('default');
    }
}
