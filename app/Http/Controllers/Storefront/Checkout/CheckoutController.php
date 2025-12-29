<?php

namespace App\Http\Controllers\Storefront\Checkout;

use App\Domains\Cart\Models\Cart;
use App\Domains\Order\Actions\CreateOrderFromCart;
use App\Domains\Order\Actions\ReserveOrder;
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
 *     required=false,
 *     @OA\JsonContent(
 *       @OA\Property(property="provider", type="string", example="stripe")
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
        ReserveOrder $reserveOrder,
        CreatePaymentFromOrder $createPaymentFromOrder,
        PaymentProviderResolver $resolver
    ): JsonResponse
    {
    $validated = $request->validate([
        'provider' => ['nullable', 'string', 'in:stripe,paypal'],
    ]);

    $cart = Cart::where('token', $request->header('X-Cart-Token'))
        ->with('items.productVariant.product')
        ->firstOrFail();

    $order = $createOrderFromCart->execute($cart);
    $order = $reserveOrder->execute($order);

    $payment = $createPaymentFromOrder->execute($order);

    $providerCode = $validated['provider'] ?? null;
    $provider = $providerCode
        ? $resolver->resolve($providerCode)
        : $resolver->default();

    $intent = $provider->createPayment($order);

    $payment->update([
        'provider' => $intent->provider,
        'provider_reference' => $intent->providerReference,
    ]);

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
