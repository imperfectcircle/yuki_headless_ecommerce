<?php

namespace App\Domains\Order\Queries;

use App\Domains\Order\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class GetOrdersForCustomer
{
    public function execute(int $customerProfileId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_profile_id', $customerProfileId)
            ->with(['items'])
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function executeByEmail(string $email, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_email', $email)
            ->where('guest_checkout', true)
            ->with(['items'])
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
