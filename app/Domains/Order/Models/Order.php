<?php

namespace App\Domains\Order\Models;

use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

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
        'customer_profile_id',
        'customer_email',
        'customer_full_name',
        'customer_phone',
        'shipping_address',
        'billing_address',
        'guest_checkout',
        'reserved_until',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'shipping_total' => 'integer',
        'grand_total' => 'integer',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'guest_checkout' => 'boolean',
        'reserved_until' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get customer profile (if exists)
     */
    public function customerProfile()
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    /**
     * Check if order is from guest
     */
    public function isGuest(): bool
    {
        return $this->guest_checkout === true;
    }

    /**
     * Check if order has customer profile
     */
    public function hasCustomerProfile(): bool
    {
        return !is_null($this->customer_profile_id);
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
            'status' => self::STATUS_CANCELLED,
            'reserved_until' => null,
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

    /**
     * Scope: guest orders only
     */
    public function scopeGuest($query)
    {
        return $query->where('guest_checkout', true);
    }

    /**
     * Scope: registered customer orders only
     */
    public function scopeRegistered($query)
    {
        return $query->where('guest_checkout', false)
            ->whereNotNull('customer_profile_id');
    }
}