<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwilioMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_sid',
        'account_sid',
        'to',
        'from',
        'channel',
        'status',
        'direction',
        'body',
        'error_code',
        'error_message',
        'price',
        'price_unit',
        'verification_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'price' => 'decimal:6',
    ];

    /**
     * Get the verification that owns the message.
     */
    public function verification()
    {
        return $this->belongsTo(PhoneVerification::class, 'verification_id');
    }
}
