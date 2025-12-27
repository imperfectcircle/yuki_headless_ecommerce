<?php

namespace App\Domains\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProviderConfig extends Model
{
    protected $table = 'payment_providers';

    protected $fillable = [
        'code',
        'enabled',
        'position',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
