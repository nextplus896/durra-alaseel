<?php

namespace App\Models\Admin;

use App\Constants\CancellationPolicyConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Global cancellation policy model.
 *
 * Single-record architecture — the admin configures one global policy.
 * Always retrieve via: CancellationPolicy::first() or CancellationPolicy::getActive()
 *
 * @property int         $id
 * @property int         $cancellation_window_hours
 * @property string      $deduction_type            none|fixed|percentage|day
 * @property float       $deduction_value
 * @property string      $service_fee_type          none|fixed|percentage
 * @property float       $service_fee_value
 * @property bool        $is_active
 * @property int|null    $last_edit_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CancellationPolicy extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id'                        => 'integer',
        'cancellation_window_hours' => 'integer',
        'deduction_type'            => 'string',
        'deduction_value'           => 'decimal:2',
        'service_fee_type'          => 'string',
        'service_fee_value'         => 'decimal:2',
        'is_active'                 => 'boolean',
        'last_edit_by'              => 'integer',
        'created_at'                => 'datetime',
        'updated_at'                => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The admin who last modified this policy.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'last_edit_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Filter to only active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Static Helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the single global active policy.
     *
     * Returns null if no policy has been configured yet.
     */
    public static function getActive(): ?static
    {
        return static::where('is_active', true)->first();
    }

    // -------------------------------------------------------------------------
    // Computed Accessors
    // -------------------------------------------------------------------------

    /**
     * Human-readable label for the deduction type.
     */
    public function getDeductionTypeLabelAttribute(): string
    {
        return CancellationPolicyConst::DEDUCTION_TYPE_LABELS[$this->deduction_type] ?? $this->deduction_type;
    }

    /**
     * Human-readable label for the service fee type.
     */
    public function getServiceFeeLabelAttribute(): string
    {
        return CancellationPolicyConst::FEE_TYPE_LABELS[$this->service_fee_type] ?? $this->service_fee_type;
    }
}
