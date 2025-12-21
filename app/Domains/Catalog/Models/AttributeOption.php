<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Models\Attribute;
use Illuminate\Database\Eloquent\Model;

class AttributeOption extends Model
{
    protected $fillable = ['attribute_id', 'code', 'label', 'position'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
