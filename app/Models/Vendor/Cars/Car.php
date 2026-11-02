<?php

namespace App\Models\Vendor\Cars;

use App\Models\Admin\Cars\CarArea;
use App\Models\Admin\Cars\CarType;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Branch;
use App\Models\BranchDeliverySetting;
use App\Models\CarBooking;
use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id'              => 'integer',
        'vendor_id'       => 'integer',
        'car_area_id'     => 'integer',
        'car_type_id'     => 'integer',
        'car_model_id'    => 'integer',
        'branch_id'       => 'integer',
        'car_title'       => 'object',
        'slug'            => 'string',
        'image'           => 'string',
        'car_model'       => 'string',
        'car_number'      => 'string',
        'seat'            => 'integer',
        'year'            => 'integer',
        'experience'      => 'integer',
        'fees'            => 'decimal:8',
        'price_per_day'   => 'decimal:8',
        'price_per_week'  => 'decimal:8',
        'price_per_month' => 'decimal:8',
        'allowance_km'    => 'double',
        'allowance_price_per_km' => 'decimal:8',
        'daily_insurance'        => 'decimal:8',
        'deductible_insurance'   => 'decimal:8',
        'status'          => 'integer',
        'approval'        => 'integer',
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
        return $this->belongsTo(Branch::class, 'branch_id');
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
        $image = $this->image;
        if (!$image && $this->carModel) {
            $image = $this->carModel->image;
        }
        if (!$image) {
            return asset('backend/images/default/default.webp');
        }
        if (is_string($image) && (\Illuminate\Support\Str::startsWith($image, "[") || \Illuminate\Support\Str::startsWith($image, '"'))) {
            $decoded = json_decode($image, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $image = $decoded[0];
            }
        }
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }
        if (!$image) {
            return asset('backend/images/default/default.webp');
        }
        if (\Illuminate\Support\Str::startsWith($image, 'http')) {
            return $image;
        }
        return files_asset_path('car-models') . '/' . $image;
    }

    /**
     * Get delivery settings for this car's branch and vendor
     */
    public function getDeliverySettingAttribute()
    {
        if (!$this->branch_id || !$this->vendor_id) {
            return null;
        }
        return BranchDeliverySetting::where('branch_id', $this->branch_id)
            ->where('vendor_id', $this->vendor_id)
            ->first();
    }

    /**
     * Check if delivery is available for this car.
     * Delivery requires: setting enabled + branch currently open.
     */
    public function isDeliveryAvailable()
    {
        $setting = $this->delivery_setting;
        if (!$setting || !$setting->delivery_available) {
            return false;
        }

        // If the branch has working hours defined, delivery is only available during open hours
        if ($this->branch && $this->branch->workingHours()->exists()) {
            return $this->branch->isCurrentlyOpen();
        }

        return true;
    }

    /**
     * Get delivery price for this car
     */
    public function getDeliveryPrice()
    {
        $setting = $this->delivery_setting;
        return $setting ? $setting->delivery_price : 0;
    }
}
