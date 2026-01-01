<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Catalog\Models\ProductVariant;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $quantity
 * @property int $reserved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $available
 * @property-read ProductVariant $variant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory lowStock(int $threshold = 10)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory outOfStock()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereReserved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Inventory extends Model
{
    protected $fillable = [
        'product_variant_id',
        'quantity',
        'reserved',
    ];

    protected $casts = [
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'reserved' => 'integer',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }

    public function hasSufficientStock(int $requestedQuantity): bool
    {
        return $this->available >= $requestedQuantity;
    }

    public function hasReservedStock(): bool
    {
        return $this->reserved > 0;
    }

    public function scopeLowStock($query, int $threshold = 10) {
        return $query->whereRaw('(quantity - reserved) <= ?', [$threshold]);
    }

    public function scopeOutOfStock($query) {
        return $query->whereRaw('(quantity - reserved) <= 0');
    }
}
