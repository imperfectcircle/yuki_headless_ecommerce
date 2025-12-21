<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Pricing\Models\Price;
use App\Domains\Catalog\Models\Product;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Catalog\Models\AttributeOption;
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

    public function options()
    {
        return $this->belongsToMany(AttributeOption::class, 'product_variant_option');
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Get current active price for a currency
     */
    public function currentPrice(string $currency): ?Price
    {
        return $this->prices()
            ->active()
            ->validAt()
            ->where('currency', $currency)
            ->orderByDesc('valid_from')
            ->first();
    }
}
