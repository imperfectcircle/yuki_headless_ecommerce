<?php

namespace App\Domains\Order\Actions;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderNote;

class AddOrderNote
{
    public function execute(Order $order, string $note, ?int $userId = null, bool $isInternal = true): OrderNote
    {
        return OrderNote::create([
            'order_id' => $order->id,
            'note' => $note,
            'user_id' => $userId,
            'is_internal' => $isInternal,
        ]);
    }
}
