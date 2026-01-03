<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

class ClaimGuestOrders
{
    public function execute(CustomerProfile $profile): int
    {
        return DB::transaction(function () use ($profile) {
            // Find guest orders with same email
            $orders = Order::where('customer_email', $profile->email)
                ->where('guest_checkout', true)
                ->whereNull('customer_profile_id')
                ->get();

            if ($orders->isEmpty()) {
                return 0;
            }

            // Claim orders
            foreach ($orders as $order) {
                $order->update([
                    'customer_profile_id' => $profile->id,
                    'guest_checkout' => false,
                ]);
            }

            return $orders->count();
        });
    }
}