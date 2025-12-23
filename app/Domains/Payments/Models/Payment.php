<?php

namespace App\Domains\Payments\Models;

use App\Domains\Order\Models\Order;
use DomainException;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'status',
        'amount',
        'currency',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canBePaid(): bool
    {
        return $this->isPending();
    }

    public function canBeFailed(): bool
    {
        return $this->isPending();
    }

    public function markAsPaid(): void{
        if (!$this->canBePaid()) {
            throw new DomainException('Payment cannot be marked ad paid.');
        }

        $this->update(['status' => self::STATUS_PAID]);
    }

    public function markAsFailed(): void{
        if (!$this->canBeFailed()) {
            throw new DomainException('Payment cannot be marked as failed.');
        }
        
        $this->update(['status' => self::STATUS_FAILED]);
    }
}
