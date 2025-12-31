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
use App\Domains\Order\Transformers\OrderDetailTransformer;
use App\Domains\Order\Transformers\OrderListTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// TODO Refactor methods with DRY principal
class OrderController extends Controller
{
    /**
     * Display a listing of orders
     * 
     * @OA\Get(
     *   path="/api/admin/orders",
     *   tags={"Admin Orders"},
     *   summary="List all orders with filters",
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="customer_email", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="order_number", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Paginated orders list")
     * )
     */
    public function index(Request $request, GetOrdersForAdmin $query): Response|JsonResponse
    {
        $filters = OrderFiltersDTO::fromRequest($request->all());
        $orders = $query->execute($filters);

        $data = [
            'orders' => [
                'data' => OrderListTransformer::transformCollection($orders->items()),
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
        ];

        // Return JSON for API, Inertia for web
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Orders/Index', $data);
    }

    /**
     * Display order detail
     * 
     * @OA\Get(
     *   path="/api/admin/orders/{id}",
     *   tags={"Admin Orders"},
     *   summary="Get order detail",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Order detail")
     * )
     */
    public function show(int $id, GetOrderDetail $query, Request $request): Response|JsonResponse
    {
        $orderDetail = $query->execute($id);

        $data = [
            'order' => $orderDetail->toArray(),
            'available_statuses' => $this->getAvailableStatusTransitions($orderDetail->status),
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $data]);
        }

        return Inertia::render('Admin/Orders/Show');
    }

    /**
     * Update order status
     * 
     * @OA\Patch(
     *   path="/api/admin/orders/{id}/status",
     *   tags={"Admin Orders"},
     *   summary="Update order status",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="status", type="string"),
     *       @OA\Property(property="note", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Status updated")
     * )
     */
    public function updateStatus(
        int $id,
        Request $request,
        UpdateOrderStatus $action,
    ): JsonResponse {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in' . implode(',', Order::getAllStatuses())],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute(
                order: $order,
                newStatus: $validated['status'],
                note: $validated['note'],
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Order status updated successfully',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments'
                ]))->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process order (paid -> processing)
     * 
     * @OA\Post(
     *   path="/api/admin/orders/{id}/process",
     *   tags={"Admin Orders"},
     *   summary="Start processing order",
     *   @OA\Response(response=200, description="Order processing started")
     * )
     */
    public function process(int $id, ProcessOrder $action): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute($order, auth()->id());

            return response()->json([
                'message' => 'Order processing started',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments',
                ]))->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Fulfill order (processing -> fulfilled)
     */
    public function fulfill(int $id, FulfillOrder $action): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute($order, auth()->id());

            return response()->json([
                'message' => 'Order fulfilled',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments',
                ]))->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Ship order (fulfilled -> shipped)
     * 
     * @OA\Post(
     *   path="/api/admin/orders/{id}/ship",
     *   tags={"Admin Orders"},
     *   summary="Mark order as shipped",
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="tracking_number", type="string"),
     *       @OA\Property(property="carrier", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Order shipped")
     * )
     */
    public function ship(int $id, Request $request, ShipOrder $action): JsonResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'carrier' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute(
                order: $order,
                trackingNumber: $validated['tracking_number'] ?? null,
                carrier: $validated['carrier'] ?? null,
                userId: auth()->id,
            );

            return response()->json([
                'message' => 'Order marked as shipped',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments',
                ]))->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Complete order (shipped -> delivered)
     */
    public function complete(int $id, CompleteOrder $action): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute($order, auth()->id());

            return response()->json([
                'message' => 'Order marked as delivered',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments'
                ]))->toArray()
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel order
     * 
     * @OA\Post(
     *   path="/api/admin/orders/{id}/cancel",
     *   tags={"Admin Orders"},
     *   summary="Cancel order",
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="reason", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Order cancelled")
     * )
     */
    public function cancel(int $id, Request $request, CancelOrder $action): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute(
                order: $order,
                reason: $validated['reason'] ?? null,
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Order cancelled successfully',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments',
                ]))->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Refund order
     * 
     * @OA\Post(
     *   path="/api/admin/orders/{id}/refund",
     *   tags={"Admin Orders"},
     *   summary="Refund order",
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(property="reason", type="string"),
     *       @OA\Property(property="restock_inventory", type="boolean")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Order refunded")
     * )
     */
    public function refund(int $id, Request $request, RefundOrder $action): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'restock_inventory' => ['nullable', 'boolean'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $action->execute(
                order: $order,
                reason: $validated['reason'] ?? null,
                userId: auth()->id(),
                restockInventory: $validated['restock_inventory'] ?? true,
            );

            return response()->json([
                'message' => 'Order refunded successfully',
                'data' => OrderDetailTransformer::transform($updatedOrder->load([
                    'items', 'statusHistory', 'payments',
                ]))->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(GetOrderStatistics $query): JsonResponse
    {
        return response()->json([
            'data' => $query->execute(),
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
