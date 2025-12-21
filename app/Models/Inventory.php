<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_variant_id',
        'quantity',
        'reserved',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }
}
