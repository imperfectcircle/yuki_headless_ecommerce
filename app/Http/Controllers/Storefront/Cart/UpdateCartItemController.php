<?php

namespace App\Http\Controllers\Storefront\Cart;

use App\Domains\Cart\Actions\UpdateCartItem;
use App\Domains\Cart\Models\Cart;
use App\Domains\Storefront\Queries\GetStorefrontCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Cart\UpdateCartItemRequest;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Patch(
 *   path="/api/storefront/v1/cart/items/{itemId}",
 *   tags={"Storefront Cart"},
 *   summary="Update cart item quantity",
 *   @OA\Parameter(
 *     name="X-Cart-Token",
 *     in="header",
 *     required=true,
 *     @OA\Schema(type="string")
 *   ),
 *   @OA\Parameter(
 *     name="itemId",
 *     in="path",
 *     required=true,
 *     @OA\Schema(type="integer")
 *   ),
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"quantity"},
 *       @OA\Property(property="quantity", type="integer", example=2)
 *     )
 *   ),
 *   @OA\Response(
 *     response=204,
 *     description="Cart item updated"
 *   )
 * )
 */
class UpdateCartItemController extends Controller
{
    public function __invoke(
        UpdateCartItemRequest $request,
        int $itemId,
        UpdateCartItem $action,
        GetStorefrontCart $getCart
    ):JsonResponse {
        $cart = Cart::where('token', $request->header('X-Cart-Token'))
            ->firstOrFail();

        $action->execute(
            cart: $cart,
            itemId: $itemId,
            quantity: (int) $request->input('quantity')
        );

        return response()->json([
            'data' => $getCart->execute($cart->token)->toArray()
        ]);
    }
}
