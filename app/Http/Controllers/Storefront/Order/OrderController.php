<?php

namespace App\Http\Controllers\Storefront\Order;

use App\Domains\Order\Queries\GetOrderDetail;
use App\Domains\Order\Queries\GetOrdersForCustomer;
use App\Domains\Order\Transformers\OrderListTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *   name="Storefront Orders",
 *   description="Order management for customers"
 * )
 */
class OrderController extends Controller
{
    /**
     * Get customer's orders
     * 
     * @OA\Get(
     *   path="/api/storefront/v1/orders",
     *   tags={"Storefront Orders"},
     *   summary="Get customer order history",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     @OA\Schema(type="integer", default=10)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Paginated order list"
     *   )
     * )
     */
    public function index(Request $request, GetOrdersForCustomer $query): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = $validated['per_page'] ?? 10;

        // If user is authenticated via Sanctum
        if ($user = $request->user('sanctum')) {
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'message' => 'Customer profile not found.'
                ], 404);
            }

            $orders = $query->execute($profile->id, $perPage);
        } else {
            // Guest checkout - require email
            $validated = $request->validate([
                'email' => ['required', 'email'],
            ]);

            $orders = $query->executeByEmail($validated['email'], $perPage);
        }

        return response()->json([
            'data' => OrderListTransformer::transformCollection($orders->getCollection()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
        ]);
    }

    /**
     * Get order detail by ID (for authenticated users)
     * 
     * @OA\Get(
     *   path="/api/storefront/v1/orders/{id}",
     *   tags={"Storefront Orders"},
     *   summary="Get order detail by ID",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Order detail"
     *   )
     * )
     */
    public function show(int $id, Request $request, GetOrderDetail $query): JsonResponse
    {
        // If user is authenticated
        if ($user = $request->user('sanctum')) {
            $profile = $user->profile;

            if(!$profile) {
                return response()->json([
                    'message' => 'Customer profile not found',
                ], 404);
            }

            try {
                $orderDetail = $query->executeForCustomer($id, $profile->id);

                return response()->json([
                    'data' => $orderDetail->toArray(),
                ]);
            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'Order not found',
                ], 404);
            }
        }

        return response()->json([
            'message' => 'Authentication requires',
        ], 401);
    }

    /**
     * Get order detail by number (for guest checkout)
     * 
     * @OA\Get(
     *   path="/api/storefront/v1/orders/lookup/{orderNumber}",
     *   tags={"Storefront Orders"},
     *   summary="Get order detail by order number (guest)",
     *   @OA\Parameter(
     *     name="orderNumber",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="email",
     *     in="query",
     *     required=true,
     *     @OA\Schema(type="string", format="email")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Order detail"
     *   )
     * )
     */
    public function showByNumber(string $orderNumber, Request $request, GetOrderDetail $query): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $orderDetail = $query->executeForGuestByNumberAndEmail(
                $orderNumber,
                $validated['email'],
            );

            return response()->json([
                'data' => $orderDetail->toArray(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found or email does not match', 
            ], 404);
        }
    }

    /**
     * Track order status
     * 
     * @OA\Get(
     *   path="/api/storefront/v1/orders/{id}/tracking",
     *   tags={"Storefront Orders"},
     *   summary="Get order tracking information",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Order tracking info"
     *   )
     * )
     */
    public function tracking(int $id, Request $request, GetOrderDetail $query): JsonResponse
    {
        if ($user = $request->user('sanctum')) {
            $profile = $user->profile;

            if (!$profile) {
                return response()->json([
                    'message' => 'Customer profile not found',
                ], 404);
            }

            try {
                $orderDetail = $query->executeForCustomer($id, $profile->id);

                // Extract only tracking-relevant information
                return response()->json([
                    'data' => [
                        'order_number' => $orderDetail->number,
                        'status' => $orderDetail->status,
                        'timeline' => array_map(
                            fn($event) => $event->toArray(),
                            $orderDetail->timeline
                        ),
                        'estimated_delivery' => null // TODO: implement when shipping is added
                    ],
                ]);
            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'Order not found'
                ], 404);
            }
        }

        return response()->json([
            'message' => 'Authentication required',
        ], 401);
    }
}
