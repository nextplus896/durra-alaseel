<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtension extends Model
{
    const STATUS_PENDING   = 0;
    const STATUS_APPROVED  = 1;
    const STATUS_CANCELLED = 2;

    protected $fillable = [
        'car_booking_id',
        'user_id',
        'extension_days',
        'previous_return_date',
        'new_return_date',
        'daily_rate',
        'rental_fees',
        'tax_percentage',
        'tax_amount',
        'total_cost',
        'payment_type',
        'paid_from_balance',
        'status',
        'balance_transaction_id',
    ];

    protected $casts = [
        'extension_days'       => 'integer',
        'previous_return_date' => 'date',
        'new_return_date'      => 'date',
        'daily_rate'           => 'float',
        'rental_fees'          => 'float',
        'tax_percentage'       => 'float',
        'tax_amount'           => 'float',
        'total_cost'           => 'float',
        'paid_from_balance'    => 'boolean',
        'status'               => 'integer',
    ];

    public function carBooking(): BelongsTo
    {
        return $this->belongsTo(CarBooking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
