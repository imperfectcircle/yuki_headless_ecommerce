<?php

namespace App\Domains\Order\Queries;

use App\Domains\Order\DTOs\OrderFiltersDTO;
use App\Domains\Order\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class GetOrdersForAdmin
{
    public function execute(OrderFiltersDTO $filters): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['items', 'customerProfile'])
            ->withCount('items');

        // Apply filters
        if ($filters->status) {
            $query->byStatus($filters->status);
        }

        if ($filters->customerEmail) {
            $query->byCustomerEmail($filters->customerEmail);
        }

        if ($filters->orderNumber) {
            $query->byOrderNumber($filters->orderNumber);
        }

        if ($filters->dateFrom || $filters->dateTo) {
            $query->dateRange($filters->dateFrom, $filters->dateTo);
        }

        if ($filters->guestOnly !== null) {
            if ($filters->guestOnly) {
                $query->guest();
            } else {
                $query->registered();
            }
        } 

        // Apply sorting
        $validSortFields = ['created_at', 'grand_total', 'status', 'number'];
        $sortBy = in_array($filters->sortBy, $validSortFields) ? $filters->sortBy : 'created_at';
        $sortDirection = in_array(strtolower($filters->sortDirection), ['asc', 'desc'])
            ? $filters->sortDirection
            : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($filters->perPage);
    }
}
