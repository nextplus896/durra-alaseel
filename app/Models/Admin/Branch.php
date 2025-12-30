<?php

namespace App\Models\Admin;

use App\Models\Admin\Admin;
use App\Models\Vendor\Cars\Car;
use App\Models\BranchDeliverySetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'address' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'service_radius_km' => 'decimal:2',
        'status' => 'boolean',
        'last_edit_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who last edited this branch
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'last_edit_by', 'id');
    }

    /**
     * Get all cars assigned to this branch
     */
    public function cars()
    {
        return $this->hasMany(Car::class, 'branch_id');
    }

    /**
     * Get delivery settings for this branch
     */
    public function deliverySettings()
    {
        return $this->hasMany(BranchDeliverySetting::class, 'branch_id');
    }

    /**
     * Scope for active branches
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Calculate distance from given coordinates to this branch (in km)
     * Uses Haversine formula
     *
     * @param float $lat
     * @param float $lng
     * @return float
     */
    public function calculateDistance($lat, $lng)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if given coordinates are within service range
     *
     * @param float $lat
     * @param float $lng
     * @return bool
     */
    public function isWithinServiceRange($lat, $lng)
    {
        $distance = $this->calculateDistance($lat, $lng);
        return $distance <= $this->service_radius_km;
    }

    /**
     * Get string status attribute
     */
    public function getStringStatusAttribute()
    {
        if ($this->status) {
            return (object)[
                'class' => 'badge badge--success',
                'value' => __('Active'),
            ];
        }
        return (object)[
            'class' => 'badge badge--danger',
            'value' => __('Inactive'),
        ];
    }
}
