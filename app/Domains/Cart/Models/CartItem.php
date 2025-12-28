<?php

namespace App\Domains\Cart\Models;

use App\Domains\Cart\Models\Cart;
use App\Domains\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'unit_price',
        'quantity',
    ];

    protected $casts = [
        'cart_id' => 'integer',
        'product_variant_id' => 'integer',
        'unit_price' => 'integer',
        'quantity' => 'integer',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getTotalAttribute(): int
    {
        return $this->unit_price * $this->quantity;
    }
}
