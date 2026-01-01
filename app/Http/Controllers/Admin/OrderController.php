<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Order\Actions\CancelOrder;
use App\Domains\Order\Actions\CompleteOrder;
use App\Domains\Order\Actions\FulfillOrder;
use App\Domains\Order\Actions\ProcessOrder;
use App\Domains\Order\Actions\RefundOrder;
use App\Domains\Order\Actions\ShipOrder;
use App\Domains\Order\Actions\UpdateOrderStatus;
use App\Domains\Order\Dtos\OrderFiltersDto;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Queries\GetOrderDetail;
use App\Domains\Order\Queries\GetOrdersForAdmin;
use App\Domains\Order\Queries\GetOrderStatistics;
use App\Domains\Order\Transformers\OrderListTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// TODO Refactor methods with DRY principal
class OrderController extends Controller
{
    /**
     * Display a listing of orders (Inertia Page)
     */
    public function index(Request $request, GetOrdersForAdmin $query): Response
    {
        $filters = OrderFiltersDTO::fromRequest($request->all());
        $orders = $query->execute($filters);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => [
                'data' => OrderListTransformer::transformCollection($orders->getCollection()),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ],
            'filters' => [
                'statuses' => Order::getAllStatuses(),
                'current' => $filters,
            ],
        ]);
    }

    /**
     * Display order detail (Inertia Page)
     */
    public function show(int $id, GetOrderDetail $query): Response
    {
        $orderDetail = $query->execute($id);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $orderDetail->toArray(),
            'available_statuses' => $this->getAvailableStatusTransitions($orderDetail->status),
        ]);
    }

    /**
     * Update order status (API endpoint for Inertia forms)
     */
    public function updateStatus(
        int $id,
        Request $request,
        UpdateOrderStatus $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Order::getAllStatuses())],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $action->execute(
                order: $order,
                newStatus: $validated['status'],
                note: $validated['note'] ?? null,
                userId: auth()->id(),
            );

            return redirect()
                ->back()
                ->with('success', 'Order status updated successfully');
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Process order (API endpoint for Inertia)
     */
    public function process(int $id, ProcessOrder $action): RedirectResponse
    {
        $order = Order::findOrFail($id);

        try {
            $action->execute($order, auth()->id());

            return redirect()->back()->with('success', 'Order processing started');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Fulfill order (processing -> fulfilled)
     */
    public function fulfill(int $id, FulfillOrder $action): RedirectResponse
    {
        $order = Order::findOrFail($id);

        try {
            $action->execute($order, auth()->id());

            return redirect()->back()->with('success', 'Order fulfilled');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ship order (fulfilled -> shipped)
     */
    public function ship(int $id, Request $request, ShipOrder $action): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'carrier' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $action->execute(
                order: $order,
                trackingNumber: $validated['tracking_number'] ?? null,
                carrier: $validated['carrier'] ?? null,
                userId: auth()->id(),
            );

            return redirect()->back()->with('success', 'Order marked as shipped');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete order (shipped -> delivered)
     */
    public function complete(int $id, CompleteOrder $action): RedirectResponse
    {
        $order = Order::findOrFail($id);

        try {
            $action->execute($order, auth()->id());

            return redirect()->back()->with('success', 'Order marked as delivered');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancel(int $id, Request $request, CancelOrder $action): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $action->execute(
                order: $order,
                reason: $validated['reason'] ?? null,
                userId: auth()->id(),
            );

            return redirect()->back()->with('success', 'Order cancelled successfully');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Refund order
    */
    public function refund(int $id, Request $request, RefundOrder $action): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'restock_inventory' => ['nullable', 'boolean'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $action->execute(
                order: $order,
                reason: $validated['reason'] ?? null,
                userId: auth()->id(),
                restockInventory: $validated['restock_inventory'] ?? true,
            );

            return redirect()->back()->with('success', 'Order refunded successfully');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(GetOrderStatistics $query): Response
    {
        $stats = $query->execute();

        return Inertia::render('Admin/Orders/Statistics', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get available status transitions for current status
     */
    private function getAvailableStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            Order::STATUS_DRAFT => [Order::STATUS_RESERVED, Order::STATUS_CANCELLED],
            Order::STATUS_RESERVED => [Order::STATUS_PAID, Order::STATUS_CANCELLED],
            Order::STATUS_PAID => [Order::STATUS_PROCESSING, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED],
            Order::STATUS_PROCESSING => [Order::STATUS_FULFILLED, Order::STATUS_CANCELLED],
            Order::STATUS_FULFILLED => [Order::STATUS_SHIPPED, Order::STATUS_CANCELLED],
            Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED],
            Order::STATUS_DELIVERED => [Order::STATUS_REFUNDED],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_REFUNDED => [],
        ];

        return $transitions[$currentStatus] ?? [];
    }
}
