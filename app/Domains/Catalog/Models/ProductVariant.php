<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Pricing\Models\Price;
use App\Domains\Catalog\Models\Product;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Catalog\Models\AttributeOption;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'attributes',
        'is_active'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean'
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
        return $this->belongsToMany(
            AttributeOption::class,
            'product_variant_option',
            'product_variant_id',
            'attribute_option_id'
        );
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_variant_id');
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
    
    /**
     * Get price for a specific currency code
     */
    public function priceForCurrency(string $currencyCode): ?Price
    {
        return $this->prices()
            ->where('currency', strtoupper($currencyCode))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->orderByDesc('valid_from')
            ->first();
    }

    /**
     * Check if variant is purchasable (has active price and inventory)
     */
    public function isPurchasable(string $currency): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $price = $this->priceForCurrency($currency);
        if (!$price) {
            return false;
        }

        if ($this->product->backorder_enabled) {
            return true;
        }

        $inventory = $this->inventory;
        if (!$inventory) {
            return false;
        }

        return ($inventory->quantity - $inventory->reserved) > 0;
    }
}
