<?php

namespace App\Domains\Order\Models;

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
        'customer_email',
        'customer_name',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
