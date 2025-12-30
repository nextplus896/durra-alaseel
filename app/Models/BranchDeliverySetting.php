<?php

namespace App\Models;

use App\Models\User;
use App\Models\Admin\Branch;
use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BranchDeliverySetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'integer',
        'vendor_id' => 'integer',
        'delivery_price' => 'decimal:8',
        'delivery_available' => 'boolean',
        'vendor_price' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the branch for this delivery setting
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the vendor who owns this delivery setting
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Scope for available delivery
     */
    public function scopeAvailable($query)
    {
        return $query->where('delivery_available', true);
    }

    /**
     * Get or create delivery setting for a branch-vendor combination
     *
     * @param int $branchId
     * @param int $vendorId
     * @return BranchDeliverySetting
     */
    public static function getOrCreate($branchId, $vendorId)
    {
        return self::firstOrCreate(
            [
                'branch_id' => $branchId,
                'vendor_id' => $vendorId,
            ],
            [
                'delivery_price' => 0,
                'delivery_available' => true,
                'vendor_price' => 0,
            ]
        );
    }
}
