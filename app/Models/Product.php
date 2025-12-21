<?php

namespace App\Models;

use App\Models\Attribute;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status', 'backorder_enabled'];

    protected $casts = [
        'backorder_enabled' => 'boolean',
    ];

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute');
    }
    
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
