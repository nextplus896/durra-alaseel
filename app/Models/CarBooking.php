<?php

namespace App\Models;

use App\Models\Vendor\Cars\Car;
use App\Models\Admin\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarBooking extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'id'                => 'integer',
        'trip_id'           => 'integer',
        'car_id'            => 'integer',
        'user_id'           => 'integer',
        'branch_id'         => 'integer',
        'slug'              => 'string',
        'car_model'         => 'string',
        'car_number'        => 'string',
        'location'          => 'string',
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
        'is_delivery'       => 'boolean',
        'paid_from_balance' => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
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
}
