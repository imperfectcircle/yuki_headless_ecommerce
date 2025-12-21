<?php

namespace App\Domains\Order\Models;

use App\Domains\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
