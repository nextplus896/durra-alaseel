<?php

namespace App\Models;

use App\Models\User;
use App\Models\CarBooking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BalanceTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Transaction types
    const TYPE_RECHARGE = 'recharge';
    const TYPE_BOOKING_DEDUCTION = 'booking_deduction';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';

    // Status values
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'trx_id' => 'string',
        'type' => 'string',
        'amount' => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after' => 'decimal:8',
        'payment_method' => 'string',
        'booking_id' => 'integer',
        'description' => 'string',
        'status' => 'integer',
        'details' => 'object',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who owns this transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the booking associated with this transaction (if any)
     */
    public function booking()
    {
        return $this->belongsTo(CarBooking::class, 'booking_id');
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for recharge transactions
     */
    public function scopeRecharge($query)
    {
        return $query->where('type', self::TYPE_RECHARGE);
    }

    /**
     * Scope for booking deduction transactions
     */
    public function scopeBookingDeduction($query)
    {
        return $query->where('type', self::TYPE_BOOKING_DEDUCTION);
    }

    /**
     * Scope for refund transactions
     */
    public function scopeRefund($query)
    {
        return $query->where('type', self::TYPE_REFUND);
    }

    /**
     * Get string status attribute
     */
    public function getStringStatusAttribute()
    {
        switch ($this->status) {
            case self::STATUS_SUCCESS:
                return (object)[
                    'class' => 'badge badge--success',
                    'value' => __('Success'),
                ];
            case self::STATUS_PENDING:
                return (object)[
                    'class' => 'badge badge--warning',
                    'value' => __('Pending'),
                ];
            case self::STATUS_FAILED:
                return (object)[
                    'class' => 'badge badge--danger',
                    'value' => __('Failed'),
                ];
            default:
                return (object)[
                    'class' => 'badge badge--secondary',
                    'value' => __('Unknown'),
                ];
        }
    }

    /**
     * Get string type attribute
     */
    public function getStringTypeAttribute()
    {
        switch ($this->type) {
            case self::TYPE_RECHARGE:
                return (object)[
                    'class' => 'badge badge--success',
                    'value' => __('Recharge'),
                ];
            case self::TYPE_BOOKING_DEDUCTION:
                return (object)[
                    'class' => 'badge badge--warning',
                    'value' => __('Booking Deduction'),
                ];
            case self::TYPE_REFUND:
                return (object)[
                    'class' => 'badge badge--info',
                    'value' => __('Refund'),
                ];
            case self::TYPE_ADJUSTMENT:
                return (object)[
                    'class' => 'badge badge--secondary',
                    'value' => __('Adjustment'),
                ];
            default:
                return (object)[
                    'class' => 'badge badge--secondary',
                    'value' => __('Unknown'),
                ];
        }
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('trx_id', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('username', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
        });
    }
}
