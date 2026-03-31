<?php

namespace App\Models;

use App\Models\Vendor\Cars\Car;
use App\Models\Admin\Branch;
use App\Models\CarBookingTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarBooking extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'id'                => 'integer',
        'trip_id'           => 'string',
        'car_id'            => 'integer',
        'user_id'           => 'integer',
        'branch_id'         => 'integer',
        'slug'              => 'string',
        'rental_days'       => 'integer',
        'allowance_km'      => 'integer',
        'car_model'         => 'string',
        'car_number'        => 'string',
        'location'          => 'string',
        'pickup_latitude'   => 'double',
        'pickup_longitude'  => 'double',
        'pickup_address'    => 'string',
        'pickup_time'       => 'string',
        'pickup_date'       => 'string',
        'round_pickup_time' => 'string',
        'round_pickup_date' => 'string',
        'destination'       => 'string',
        'phone'             => 'string',
        'email'             => 'string',
        'type'              => 'string',
        'message'           => 'string',
        'status'            => 'integer',
        'payment_type'      => 'string',
        'trx_id'            => 'string',
        'amount'            => 'double',
        'charges'           => 'double',
        'delivery_fee'      => 'double',
        'tax_amount'        => 'double',
        'tax_percentage'    => 'double',
        'subtotal'          => 'double',
        'total_amount'      => 'double',
        'distance'          => 'double',
        'balance_deducted'  => 'double',
        'is_delivery'          => 'boolean',
        'paid_from_balance'    => 'boolean',
        'return_date'          => 'date',
        'original_rental_days' => 'integer',
        'extension_count'      => 'integer',
        'total_extension_days' => 'integer',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
    public function cars()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'trx_id', 'trx_id');
    }
    public function balanceTransaction()
    {
        return $this->hasOne(BalanceTransaction::class, 'booking_id');
    }

    public function extensions()
    {
        return $this->hasMany(CarBookingTransaction::class, 'car_booking_id')
            ->where('type', CarBookingTransaction::TYPE_EXTENSION)
            ->latest('transacted_at');
    }

    public function bookingTransactions()
    {
        return $this->hasMany(CarBookingTransaction::class, 'car_booking_id')->latest('transacted_at');
    }

    // -----------------------------------------------------------------
    // Helper Methods
    // -----------------------------------------------------------------

    /**
     * Calculate the computed return date from pickup_date + rental_days.
     * Useful before return_date column is populated.
     */
    public function calculateReturnDate(): Carbon
    {
        return Carbon::parse($this->pickup_date)->addDays((int) $this->rental_days);
    }

    /**
     * Whether this booking is eligible for extension (ONGOING + return date in future).
     */
    public function getIsExtendableAttribute(): bool
    {
        if ((int) $this->status !== 2) {
            return false;
        }

        $returnDate = $this->return_date
            ? Carbon::parse($this->return_date)
            : $this->calculateReturnDate();

        // Must have at least 2 hours remaining
        return $returnDate->isAfter(now()->addHours(2));
    }

    /**
     * Days remaining until return, floored to zero.
     */
    public function getDaysRemainingAttribute(): int
    {
        $returnDate = $this->return_date
            ? Carbon::parse($this->return_date)
            : $this->calculateReturnDate();

        $diff = (int) now()->diffInDays($returnDate, false);
        return max(0, $diff);
    }

    /**
     * Total cost of all approved extensions (sourced from booking transactions).
     */
    public function getTotalExtensionCostAttribute(): float
    {
        return (float) $this->bookingTransactions()->where('type', CarBookingTransaction::TYPE_EXTENSION)->sum('total');
    }

    /**
     * Check if the car is available for the given extension window.
     * Returns true if there IS a conflict (car not available).
     */
    public static function hasConflictForExtension(
        int    $carId,
        string $currentReturnDate,
        string $newReturnDate,
        int    $excludeBookingId
    ): bool {
        return self::where('car_id', $carId)
            ->where('id', '!=', $excludeBookingId)
            ->where('pickup_date', '<=', $newReturnDate)
            ->where(function ($q) use ($currentReturnDate) {
                $q->whereNotNull('return_date')
                    ->where('return_date', '>=', $currentReturnDate);
            })
            ->whereIn('status', [0, 1, 2]) // PENDING, BOOKED, ONGOING
            ->lockForUpdate()
            ->exists();
    }
}
