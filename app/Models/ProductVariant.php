<?php

namespace App\Models;

use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['sku', 'attributes', 'is_active'];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }
}
