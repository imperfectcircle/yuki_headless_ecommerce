<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Models\Attribute;
use Illuminate\Database\Eloquent\Model;

class AttributeOption extends Model
{
    protected $fillable = [
        'attribute_id',
        'code',
        'label',
        'position'
    ];

    protected $casts = [
        'attribute_id' => 'integer',
        'position' => 'integer',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
