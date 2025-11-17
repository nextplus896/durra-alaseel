<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'channel',
        'verification_sid',
        'status',
        'attempts',
        'verified_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the messages for the verification.
     */
    public function messages()
    {
        return $this->hasMany(TwilioMessage::class, 'verification_id');
    }

    /**
     * Check if verification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if verification is verified
     */
    public function isVerified(): bool
    {
        return $this->status === 'approved' && $this->verified_at !== null;
    }
}
