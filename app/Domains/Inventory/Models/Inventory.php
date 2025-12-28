<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Catalog\Models\ProductVariant;

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
