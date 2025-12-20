<?php

namespace App\Models;

use App\Models\AttributeOption;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['code', 'name', 'type', 'is_variant'];

    public function options()
    {
        return $this->hasMany(AttributeOption::class);
    }
}
