<?php

namespace App\Domains\Order\Models;

use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Payments\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $customer_profile_id
 * @property string $number
 * @property string $status
 * @property string $currency
 * @property int $subtotal
 * @property int $tax_total
 * @property int $shipping_total
 * @property int $grand_total
 * @property string $customer_email
 * @property string|null $customer_full_name
 * @property string|null $customer_phone
 * @property array<array-key, mixed>|null $shipping_address
 * @property array<array-key, mixed>|null $billing_address
 * @property bool $guest_checkout
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $reserved_until
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Order\Models\OrderNote> $notes
 * @property-read CustomerProfile|null $customerProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read int|null $items_count
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Order\Models\OrderStatusHistory> $statusHistory
 * @property-read int|null $status_history_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order byCustomerEmail(string $email)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order byOrderNumber(string $number)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order byStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order dateRange(?string $from, ?string $to)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order expiredReservations()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order guest()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order registered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order reserved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereBillingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereGuestCheckout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereReservedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereShippingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereShippingTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTaxTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order withRelations()
 * @mixin \Eloquent
 */
class Order extends Model
{
    // ==========================================
    // CONSTANTS - Status Definitions
    // ==========================================
    
    // Existing constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    // New constants for order management
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_REFUNDED = 'refunded'; 

    // ==========================================
    // MODEL CONFIGURATION
    // ==========================================
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
        'notes', // New field
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

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customerProfile()
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class);
    }

    // ==========================================
    // STATUS CHECK METHODS (Existing)
    // ==========================================
    public function isGuest(): bool
    {
        return $this->guest_checkout === true;
    }

    public function hasCustomerProfile(): bool
    {
        return !is_null($this->customer_profile_id);
    }

    public function canBePaid(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
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

    // ==========================================
    // NEW STATUS CHECK METHODS
    // ==========================================
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isFulfilled(): bool
    {
        return $this->status === self::STATUS_FULFILLED;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    // ==========================================
    // STATUS TRANSITION METHODS (Existing)
    // ==========================================
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

    // ==========================================
    // NEW STATUS TRANSITION VALIDATION
    // ==========================================

    /**
     * Check if the order can transition to a new status
     * This implements a state machine pattern
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = [
            self::STATUS_DRAFT => [self::STATUS_RESERVED, self::STATUS_CANCELLED],
            self::STATUS_RESERVED => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID => [self::STATUS_PROCESSING, self::STATUS_REFUNDED, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_FULFILLED, self::STATUS_CANCELLED],
            self::STATUS_FULFILLED => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
            self::STATUS_DELIVERED => [self::STATUS_REFUNDED],
            self::STATUS_CANCELLED => [],
            self::STATUS_REFUNDED => [],
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    /**
     * Transition to a new status with validation and history tracking
     */
    public function transitionTo(string $newStatus, ?string $note = null, ?int $userId = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        // Record status change in history
        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'note' => $note,
            'user_id' => $userId,
        ]);

        return true;
    }

    // ==========================================
    // NEW STATUS TRANSITION HELPER METHODS
    // ==========================================
    public function markAsProcessing(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(self::STATUS_PROCESSING, $note, $userId);
    }

    public function markAsFulfilled(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(self::STATUS_FULFILLED, $note, $userId);
    }

    public function markAsShipped(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(self::STATUS_SHIPPED, $note, $userId);
    }

    public function markAsDelivered(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(self::STATUS_DELIVERED, $note, $userId);
    }

    public function markAsCancelled(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(self::STATUS_CANCELLED, $note, $userId);
    }

    public function markAsRefunded(?string $note = null, ?int $userId = null): bool
    {
        return $this->transitionTo(self::STATUS_REFUNDED, $note, $userId);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================


    public function scopeReserved($query)
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    public function scopeExpiredReservations($query)
    {
        return $query->where('status', self::STATUS_RESERVED)
            ->where('reserved_until', '<', now());
    }

    public function scopeGuest($query)
    {
        return $query->where('guest_checkout', true);
    }

    public function scopeRegistered($query)
    {
        return $query->where('guest_checkout', false)
            ->whereNotNull('customer_profile_id');
    }

    // ==========================================
    // QUERY SCOPES FOR FILTERING
    // ==========================================

    /**
     * Scope: Filter by specific status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by customer email (with LIKE for partial match)
     */
    public function scopeByCustomerEmail($query, string $email)
    {
        return $query->where('customer_email', 'like', "%{$email}%");
    }

    /**
     * Scope: Filter by order number (with LIKE for partial match)
     */
    public function scopeByOrderNumber($query, string $number)
    {
        return $query->where('number', 'like', "%{$number}%");
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where(
                'created_at',
                '>=',
                Carbon::createFromFormat('Y-m-d', $from)->startOfDay()
            );
        }

        if ($to) {
            $query->where(
                'created_at',
                '<=',
                Carbon::createFromFormat('Y-m-d', $to)->endOfDay()
            );
        }

        return $query;
    }

    /**
     * Scope: Eager load common relations
     */
    public function scopeWithRelations($query)
    {
        return $query->with([
            'items.productVariant.product',
            'customerProfile',
            'payments',
            'statusHistory',
        ]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get human-readable status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_FULFILLED => 'Fulfilled',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
        };
    }

    /**
     * Get color for status badge (for UI)
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_RESERVED => 'yellow',
            self::STATUS_PAID => 'green',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_FULFILLED => 'indigo',
            self::STATUS_SHIPPED => 'purple',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_REFUNDED => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get all available order statuses
     */
    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_RESERVED,
            self::STATUS_PAID,
            self::STATUS_PROCESSING,
            self::STATUS_FULFILLED,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ];
    }
}