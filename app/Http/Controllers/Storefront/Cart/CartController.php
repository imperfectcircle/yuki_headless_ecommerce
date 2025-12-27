<?php

namespace App\Http\Controllers\Storefront\Cart;

use App\Domains\Storefront\Queries\GetStorefrontCart;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *   name="Storefront Cart",
 *   description="Cart management for storefront"
 * )
 */
class CartController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/storefront/v1/cart",
     *   tags={"Storefront Cart"},
     *   summary="Get current cart",
     *   @OA\Parameter(
     *     name="X-Cart-Token",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Cart data"
     *   )
     * )
     */
    public function show(
        Request $request,
        GetStorefrontCart $query
    ): JsonResponse {
        $token = $request->header('X-Cart-Token');

        return response()->json([
            'data' => $query->execute($token)->toArray()
        ]);
    }
}
