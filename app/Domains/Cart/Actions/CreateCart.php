<?php

namespace App\Domains\Cart\Actions;

use App\Domains\Cart\Models\Cart;
use Illuminate\Support\Str;

class CreateCart
{
    public function execute(string $currency): Cart 
    {
        return Cart::create([
            'token' => Str::uuid(),
            'currency' => $currency,
            'status' => Cart::STATUS_ACTIVE,
        ]);
    }
}
