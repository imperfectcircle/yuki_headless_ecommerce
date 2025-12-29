<?php

namespace App\Http\Controllers\Storefront\Cart;

use App\Domains\Cart\Actions\CreateCart;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Post(
 *   path="/api/storefront/v1/carts",
 *   tags={"Storefront Cart"},
 *   summary="Create a new cart",
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"currency"},
 *       @OA\Property(property="currency", type="string", example="EUR")
 *     )
 *   ),
 *   @OA\Response(
 *     response=201,
 *     description="Cart created successfully"
 *   )
 * )
 */
class CreateCartController extends Controller
{
    public function __invoke(
        Request $request,
        CreateCart $action
    ): JsonResponse 
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3', 'in:EUR,USD,GBP'],
        ]);

        $cart = $action->execute($validated['currency']);

        return response()->json([
            'data' => [
                'token' => $cart->token,
                'currency' => $cart->currency,
            ]
        ], 201);
    }
}
