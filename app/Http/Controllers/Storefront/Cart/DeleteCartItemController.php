<?php

namespace App\Http\Controllers\Storefront\Cart;

use App\Domains\Cart\Actions\RemoveCartItem;
use App\Domains\Cart\Models\Cart;
use App\Domains\Storefront\Queries\GetStorefrontCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Cart\DeleteCartItemRequest;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Delete(
 *   path="/api/storefront/v1/cart/items/{itemId}",
 *   tags={"Storefront Cart"},
 *   summary="Remove item from cart",
 *   @OA\Parameter(
 *     name="itemId",
 *     in="path",
 *     required=true,
 *     @OA\Schema(type="integer")
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="Updated cart"
 *   )
 * )
 */
class DeleteCartItemController extends Controller
{
    public function __invoke(
        DeleteCartItemRequest $request,
        int $itemId,
        RemoveCartItem $action,
        GetStorefrontCart $getCart
    ):JsonResponse {
        $cart = Cart::where('token', $request->header('X-Cart-Token'))
            ->with('items')
            ->firstOrFail();

        $item = $cart->items()->where('id', $itemId)->first();

        if (!$item) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        $action->execute($cart, $item);

        return response()->json([
            'data' => $getCart->execute($cart->token)->toArray()
        ]);
    }
}
