<?php

namespace App\Models;

use App\Models\Vendor\Cars\Car;
use App\Models\Admin\Branch;
use App\Models\BookingExtension;
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
        'insurance_type'       => 'string',
        'daily_insurance'      => 'double',
        'insurance_total'      => 'double',
        'deductible_insurance' => 'double',
        'is_delivery'          => 'boolean',
        'paid_from_balance'    => 'boolean',
        'return_date'          => 'date',
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

    public function bookingExtensions()
    {
        return $this->hasMany(BookingExtension::class, 'car_booking_id')->latest();
    }

    public function bookingTransactions()
    {
        return $this->hasMany(CarBookingTransaction::class, 'car_booking_id')->latest('transacted_at');
    }

    // -----------------------------------------------------------------
    // Helper Methods
    // -----------------------------------------------------------------

    /**
     * Calculate the computed return date.
     * Formula: pickup_date + pickup_time + rental_days + total_extension_days
     */
    public function calculateReturnDate(): Carbon
    {
        $date = Carbon::parse($this->pickup_date);

        if ($this->pickup_time) {
            $date->setTimeFromTimeString($this->pickup_time);
        }

        return $date->addDays((int) $this->rental_days + (int) $this->total_extension_days);
    }

    /**
     * Whether this booking is eligible for extension (ONGOING + return date in future).
     */
    public function getIsExtendableAttribute(): bool
    {
        if ((int) $this->status !== 2) {
            return false;
        }

        $returnDate = $this->calculateReturnDate();

        // Must have at least 2 hours remaining
        return $returnDate->isAfter(now()->addHours(2));
    }

    /**
     * Days remaining until return, floored to zero.
     */
    public function getDaysRemainingAttribute(): int
    {
        $returnDate = $this->calculateReturnDate();

        $diff = (int) now()->diffInDays($returnDate, false);
        return max(0, $diff);
    }

    /**
     * Total cost of all approved extensions.
     */
    public function getTotalExtensionCostAttribute(): float
    {
        return (float) $this->bookingExtensions()->sum('total_cost');
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
