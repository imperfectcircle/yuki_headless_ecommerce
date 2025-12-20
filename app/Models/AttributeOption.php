<?php

namespace App\Models;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Model;

class AttributeOption extends Model
{
    protected $fillable = ['attribute_id', 'code', 'label', 'position'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
