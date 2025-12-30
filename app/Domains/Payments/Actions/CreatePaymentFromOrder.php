<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Order\Models\Order;
use App\Domains\Payments\Events\PaymentCreated;
use App\Domains\Payments\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreatePaymentFromOrder
{
    public function execute(Order $order, string $provider): Payment
    {
        if (!$order->canBePaid()) {
            throw new DomainException('Order cannot be paid in its current state.');
        }

        return DB::transaction(function () use ($order, $provider) {
            $order->lockForUpdate();
            
            // idempotency: avoid duplicate pending payments
            $payment = Payment::where('order_id', $order->id)
                ->where('provider', $provider)
                ->where('status', Payment::STATUS_PENDING)
                ->first();

            if ($payment) {
                return $payment;
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => $provider,
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => Payment::STATUS_PENDING,
            ]);

            PaymentCreated::dispatch($payment);

            return $payment;
        });
    }
}
