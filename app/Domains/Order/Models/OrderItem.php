<?php

namespace App\Domains\Order\Models;

use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Catalog\Models\ProductVariant;

class OrderItem extends Model
{
    protected $fillable = [
        'product_variant_id',
        'sku',
        'name',
        'attributes',
        'unit_price',
        'quantity',
        'total',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
