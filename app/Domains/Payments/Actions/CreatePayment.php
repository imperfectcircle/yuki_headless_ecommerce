<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Order\Models\Order;
use App\Domains\Payments\Models\Payment;
use DomainException;

class CreatePayment
{
    public function execute(Order $order, string $provider): Payment
    {
        if ($order->status !== Order::STATUS_RESERVED) {
            throw new DomainException(
                'Payments can only be created for reserved orders.'
            );
        }

        return Payment::create([
            'order_id' => $order->id,
            'provider' => $provider,
            'status' => 'pending',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
        ]);
    }
}
