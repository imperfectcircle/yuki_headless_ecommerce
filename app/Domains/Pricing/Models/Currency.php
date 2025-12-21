<?php

namespace App\Domains\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'symbol',
        'name',
        'precision',
        'is_active',
    ];
}
