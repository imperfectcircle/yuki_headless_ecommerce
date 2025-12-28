<?php

namespace App\Http\Controllers\Storefront\Cart;

use App\Domains\Cart\Actions\AddItemToCart;
use App\Domains\Cart\Models\Cart;
use App\Domains\Storefront\Queries\GetStorefrontCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Cart\AddCartItemRequest;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Post(
 *   path="/api/storefront/v1/cart/items",
 *   tags={"Storefront Cart"},
 *   summary="Add item to cart",
 *   @OA\Parameter(
 *     name="X-Cart-Token",
 *     in="header",
 *     required=true,
 *     @OA\Schema(type="string")
 *   ),
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"variant_id","quantity"},
 *       @OA\Property(property="variant_id", type="integer"),
 *       @OA\Property(property="quantity", type="integer")
 *     )
 *   ),
 *   @OA\Response(
 *     response=204,
 *     description="Item added to cart"
 *   )
 * )
 */
class AddCartItemController extends Controller
{
    public function __invoke(
        AddCartItemRequest $request,
        AddItemToCart $action,
        GetStorefrontCart $getCart,
    ): JsonResponse {
        $cart = Cart::where('token', $request->header('X-Cart-Token'))
            ->firstOrFail();

        $action->execute(
            cart: $cart,
            variantId: (int) $request->input('variant_id'),
            quantity: (int) $request->input('quantity')
        );

        return response()->json([
            'data' => $getCart->execute($cart->token)->toArray()
        ]);
    }
}
