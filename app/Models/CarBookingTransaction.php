<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarBookingTransaction extends Model
{
    use HasFactory;

    protected $table = 'car_booking_transactions';

    protected $guarded = ['id'];

    // -----------------------------------------------------------------
    // Transaction type constants
    // -----------------------------------------------------------------

    const TYPE_RENTAL    = 'rental';
    const TYPE_EXTENSION = 'extension';
    const TYPE_DELIVERY  = 'delivery';

    // -----------------------------------------------------------------
    // Casts
    // -----------------------------------------------------------------

    protected $casts = [
        'id'                       => 'integer',
        'car_booking_id'           => 'integer',
        'type'                     => 'string',
        'description'              => 'string',
        'amount'                   => 'double',
        'tax_percentage'           => 'double',
        'tax_amount'               => 'double',
        'total'                    => 'double',
        'daily_rate'               => 'decimal:2',
        'extension_days'           => 'integer',
        'previous_return_date'     => 'date',
        'new_return_date'          => 'date',
        'transacted_at'            => 'datetime',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function booking()
    {
        return $this->belongsTo(CarBooking::class, 'car_booking_id');
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    /**
     * Human-readable translated label for this transaction type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_RENTAL    => __('Rental'),
            self::TYPE_EXTENSION => __('Extension'),
            self::TYPE_DELIVERY  => __('Delivery'),
            default              => ucfirst($this->type),
        };
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRentals($query)
    {
        return $query->where('type', self::TYPE_RENTAL);
    }

    public function scopeExtensions($query)
    {
        return $query->where('type', self::TYPE_EXTENSION);
    }

    public function scopeDeliveries($query)
    {
        return $query->where('type', self::TYPE_DELIVERY);
    }
}
