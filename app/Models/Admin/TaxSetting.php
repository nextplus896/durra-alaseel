<?php

namespace App\Models\Admin;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'percentage' => 'decimal:2',
        'status' => 'boolean',
        'last_edit_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who last edited this tax setting
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'last_edit_by', 'id');
    }

    /**
     * Scope for active tax settings
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Get the current active tax percentage
     *
     * @return float
     */
    public static function getActivePercentage()
    {
        $setting = self::where('status', true)->first();
        return $setting ? $setting->percentage : 0;
    }

    /**
     * Calculate tax amount for a given amount
     *
     * @param float $amount
     * @return float
     */
    public static function calculateTax($amount)
    {
        $percentage = self::getActivePercentage();
        return ($amount * $percentage) / 100;
    }

    /**
     * Calculate total amount including tax
     *
     * @param float $amount
     * @return float
     */
    public static function calculateTotalWithTax($amount)
    {
        return $amount + self::calculateTax($amount);
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
