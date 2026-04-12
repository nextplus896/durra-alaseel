<?php

namespace App\Models\Admin;

use App\Models\Admin\Admin;
use App\Models\Vendor\Cars\Car;
use App\Models\BranchDeliverySetting;
use App\Models\BranchWorkingHour;
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
        'delivery_enabled' => 'boolean',
        'delivery_radius_km' => 'decimal:2',
        'phone' => 'string',
        'email' => 'string',
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
     * Get working hours for this branch
     */
    public function workingHours()
    {
        return $this->hasMany(BranchWorkingHour::class, 'branch_id');
    }

    /**
     * Check if the branch is currently open based on working hours.
     * Uses Asia/Riyadh timezone (all branches are in KSA).
     */
    public function isCurrentlyOpen(): bool
    {
        $now = now('Asia/Riyadh');
        $dayOfWeek = $now->dayOfWeek; // 0=Sunday, 6=Saturday
        $currentTime = $now->format('H:i:s');

        return $this->workingHours()
            ->enabled()
            ->forDay($dayOfWeek)
            ->where('open_time', '<=', $currentTime)
            ->where('close_time', '>', $currentTime)
            ->exists();
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
     * Check if given coordinates are within the branch delivery radius
     *
     * @param float $lat
     * @param float $lng
     * @return bool
     */
    public function isWithinDeliveryRadius($lat, $lng)
    {
        if (!$this->delivery_enabled || !$this->delivery_radius_km) {
            return false;
        }
        return $this->calculateDistance($lat, $lng) <= $this->delivery_radius_km;
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
