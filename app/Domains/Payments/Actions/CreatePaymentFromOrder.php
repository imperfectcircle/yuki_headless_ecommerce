<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Order\Models\Order;
use App\Domains\Payments\Events\PaymentCreated;
use App\Domains\Payments\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreatePaymentFromOrder
{
    public function execute(Order $order): Payment
    {
        if (!$order->canBePaid()) {
            throw new DomainException('Order cannot be paid in its current state.');
        }

        return DB::transaction(function () use ($order) {
            
            // idempotency: avoid duplicate pending payments
            $existingPayment = Payment::where('order_id', $order->id)
                ->where('status', Payment::STATUS_PENDING)
                ->first();

            if ($existingPayment) {
                return $existingPayment;
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => Payment::STATUS_PENDING,
            ]);

            PaymentCreated::dispatch($payment);

            return $payment;
        });
    }
}
