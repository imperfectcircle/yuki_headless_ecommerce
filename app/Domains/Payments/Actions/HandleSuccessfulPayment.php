<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Inventory\Actions\ConfirmOrderInventory;
use App\Domains\Order\Actions\MarkOrderAsPaid;
use App\Domains\Payments\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

class HandleSuccessfulPayment
{
    public function __construct(
        protected ConfirmOrderInventory $confirmOrderInventory,
        protected MarkOrderAsPaid $markOrderAsPaid,
    ) {}
    public function execute(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {

            if ($payment->isPaid()) {
                return;
            }

            if (!$payment->isPending()) {
                throw new DomainException('Payment is not in a payable state.');
            }

            $payment->markAsPaid();

            $this->confirmOrderInventory->execute($payment->order);

            $this->markOrderAsPaid->execute($payment->order);
        });
    }
}
