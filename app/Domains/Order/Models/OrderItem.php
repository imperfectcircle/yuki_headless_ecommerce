<?php

namespace App\Domains\Order\Models;

use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Catalog\Models\ProductVariant;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'sku',
        'name',
        'attributes',
        'unit_price',
        'quantity',
        'total',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_variant_id' => 'integer',
        'attributes' => 'array',
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'total' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function recalculateTotal(): int
    {
        return $this->unit_price * $this->quantity;
    }
}
