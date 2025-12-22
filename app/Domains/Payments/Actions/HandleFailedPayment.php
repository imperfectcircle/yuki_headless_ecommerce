<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Inventory\Actions\ReleaseOrderInventory;
use App\Domains\Order\Actions\MarkOrderAsFailed;
use App\Domains\Payments\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

class HandleFailedPayment
{
    public function __construct(
        protected ReleaseOrderInventory $releaseOrderInventory,
        protected MarkOrderAsFailed $markOrderAsFailed,
    ) {}
    public function execute(string $provider, string $providerReference): void
    {
        DB::transaction(function () use ($provider, $providerReference) {

            $payment = Payment::where('provider', $provider)
                ->where('provider_reference', $providerReference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->isFailed()) {
                return;
            }
            if (!$payment->isPending()) {
                throw new DomainException('Payment is not in a fail-able state.');
        }

            $payment->markAsFailed();

            $this->releaseOrderInventory->execute($payment->order);

            $this->markOrderAsFailed->execute($payment->order);
        });
    }
}
