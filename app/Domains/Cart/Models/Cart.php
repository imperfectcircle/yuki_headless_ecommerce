<?php

namespace App\Domains\Cart\Models;

use App\Domains\Cart\Models\CartItem;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'token',
        'status',
        'currency',
    ];

    protected $casts = [
        'token' => 'string',
        'status' => 'string',
        'currency' => 'string',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function markAsConverted(): void
    {
        $this->update(['status' => self::STATUS_CONVERTED]);
    }

    public function markAsAbandoned(): void
    {
        $this->update(['status' => self::STATUS_ABANDONED]);
    }
}
