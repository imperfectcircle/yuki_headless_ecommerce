<?php

namespace App\Http\Controllers\Storefront\Checkout;

use App\Domains\Cart\Models\Cart;
use App\Domains\Order\Actions\CreateOrderFromCart;
use App\Domains\Payments\Actions\CreatePaymentFromOrder;
use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\Resolvers\PaymentProviderResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Post(
 *   path="/api/storefront/v1/checkout",
 *   tags={"Storefront Checkout"},
 *   summary="Checkout cart",
 *   description="Creates an order from the cart and returns payment intent",
 *   @OA\Parameter(
 *     name="X-Cart-Token",
 *     in="header",
 *     required=true,
 *     @OA\Schema(type="string")
 *   ),
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"payment_provider"},
 *       @OA\Property(property="payment_provider", type="string", example="stripe")
 *     )
 *   ),
 *   @OA\Response(
 *     response=201,
 *     description="Checkout successful"
 *   )
 * )
 */
class CheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        CreateOrderFromCart $createOrderFromCart,
        CreatePaymentFromOrder $createPaymentFromOrder,
        PaymentProviderResolver $resolver
    ): JsonResponse
    {
        $cart = Cart::where('token', $request->header('X-Cart-Token'))
        ->firstOrFail();

    $order = $createOrderFromCart->execute($cart);
    $payment = $createPaymentFromOrder->execute($order);

    $providerCode = $request->input('provider'); // opzionale
    $provider = $providerCode
        ? $resolver->resolve($providerCode)
        : $resolver->default();

    $intent = $provider->createPayment($order);

    return response()->json([
        'order_id' => $order->id,
        'status' => $order->status,
        'payment' => [
            'provider' => $intent->provider,
            'reference' => $intent->providerReference,
            'redirect_url' => $intent->redirectUrl,
        ],
    ], 201);
    }
}
