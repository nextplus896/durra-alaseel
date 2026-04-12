<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';
    protected $guarded = ['id'];

    // Status constants
    const STATUS_PENDING  = 0;
    const STATUS_PAID     = 1;
    const STATUS_FAILED   = 2;
    const STATUS_REFUNDED = 3;

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'invoice_id' => 'string',
        'payment_id' => 'string',
        'amount'     => 'decimal:8',
        'status'     => 'integer',
        'provider'   => 'string',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function markPaid(string $paymentId): void
    {
        $this->update([
            'status'     => self::STATUS_PAID,
            'payment_id' => $paymentId,
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    public function markRefunded(): void
    {
        $this->update(['status' => self::STATUS_REFUNDED]);
    }
}
