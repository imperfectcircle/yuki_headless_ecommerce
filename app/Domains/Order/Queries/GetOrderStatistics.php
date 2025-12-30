<?php

namespace App\Domains\Order\Queries;

use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

class GetOrderStatistics
{
    public function execute(): array
    {
        $statusCount = Order::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalRevenue = Order::query()
            ->whereIn('status', [
                Order::STATUS_PAID,
                Order::STATUS_PROCESSING,
                Order::STATUS_FULFILLED,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
            ])
            ->sum('grand_total');

        $todayOrders = Order::query()
            ->whereDate('created_at', today())
            ->count();

        $todayRevenue = Order::query()
            ->whereDate('created_at', today())
            ->whereIn('status', [
                Order::STATUS_PAID,
                Order::STATUS_PROCESSING,
                Order::STATUS_FULFILLED,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
            ])
            ->sum('grand_total');

        return [
            'status_counts' => $statusCount,
            'total_revenue' => $totalRevenue,
            'today_orders' => $todayOrders,
            'today_revenue' => $todayRevenue,
            'pending_fulfillment' => Order::where('status', Order::STATUS_PAID)->count(),
            'in_progress' => Order::where('status', Order::STATUS_PROCESSING)->count(),
        ];
    }
}
