<?php

namespace App\Domains\Order\Models;

use App\Domains\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

use function Symfony\Component\Clock\now;

class Order extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    
    protected $fillable = [
        'number',
        'status',
        'currency',
        'subtotal',
        'tax_total',
        'shipping_total',
        'grand_total',
        'customer_email',
        'customer_name',
        'reserved_until'
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'shipping_total' => 'integer',
        'grand_total' => 'integer',
        'reserved_until' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function canBePaid(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    public function canBeFailed(): bool
    {
        return in_array($this->status, [self::STATUS_RESERVED, self::STATUS_DRAFT]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED, 'reserved_until' => null
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function hasReservationExpired(): bool
    {
        if (!$this->isReserved()) {
            return false;
        }

        if (!$this->reserved_until) {
            return false;
        }

        return $this->reserved_until->isPast();
    }

    public function scopeReserved($query)
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    public function scopeExpiredReservations($query)
    {
        return $query->where('status', self::STATUS_RESERVED)
            ->where('reserved_until', '<', now());
    }
}
