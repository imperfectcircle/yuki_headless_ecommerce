<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = [
        'currency',
        'amount',
        'vat_rate',
        'valid_from',
        'valid_to',
    ];
}
