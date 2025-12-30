<?php

namespace App\Http\Controllers\Storefront\Checkout;

use App\Domains\Cart\Models\Cart;
use App\Domains\Order\Actions\CreateOrderFromCart;
use App\Domains\Order\Actions\ReserveOrder;
use App\Domains\Payments\Actions\CreatePaymentFromOrder;
use App\Domains\Payments\Contracts\PaymentProvider;
use App\Domains\Payments\Resolvers\PaymentProviderResolver;
use App\Domains\Storefront\DTOs\Checkout\BillingAddressDTO;
use App\Domains\Storefront\DTOs\Checkout\CheckoutRequestDTO;
use App\Domains\Storefront\DTOs\Checkout\ShippingAddressDTO;
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

            'customer_profile_id' => ['nullable', 'integer', 'exists:customer_profiles,id'],

            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],

            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string', 'max:255'],
            'shipping_address.company' => ['nullable', 'string', 'max:255'],
            'shipping_address.address_line_1' => ['required', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],

            'billing_address' => ['required', 'array'],
            'billing_address.name' => ['required', 'string', 'max:255'],
            'billing_address.company' => ['nullable', 'string', 'max:255'],
            'billing_address.address_line_1' => ['required', 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.postal_code' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['required', 'string', 'size:2'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $cart = Cart::where('token', $request->header('X-Cart-Token'))
            ->with('items.productVariant.product')
            ->firstOrFail();

        $shippingAddress = new ShippingAddressDTO(
            name: $validated['shipping_address']['name'],
            company: $validated['shipping_address']['company'] ?? null,
            addressLine1: $validated['shipping_address']['address_line_1'],
            addressLine2: $validated['shipping_address']['address_line_2'] ?? null,
            city: $validated['shipping_address']['city'],
            state: $validated['shipping_address']['state'] ?? null,
            postalCode: $validated['shipping_address']['postal_code'],
            country: $validated['shipping_address']['country'],
            phone: $validated['shipping_address']['phone'] ?? null,
        );

        $billingAddress = new BillingAddressDTO(
            name: $validated['billing_address']['name'],
            company: $validated['billing_address']['company'] ?? null,
            addressLine1: $validated['billing_address']['address_line_1'],
            addressLine2: $validated['billing_address']['address_line_2'] ?? null,
            city: $validated['billing_address']['city'],
            state: $validated['billing_address']['state'] ?? null,
            postalCode: $validated['billing_address']['postal_code'],
            country: $validated['billing_address']['country'],
            phone: $validated['billing_address']['phone'] ?? null,
        );

        $checkoutData = new CheckoutRequestDTO(
            email: $validated['email'],
            firstName: $validated['first_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            phone: $validated['phone'] ?? null,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            paymentProvider: $validated['provider'] ?? null,
            customerProfileId: $validated['customer_profile_id'] ?? null,
        );

        $order = $createOrderFromCart->execute($cart, $checkoutData);
        $order = $reserveOrder->execute($order);

        $payment = $createPaymentFromOrder->execute($order);

        $providerCode = $checkoutData->paymentProvider;
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
            'order_number' => $order->number,
            'status' => $order->status,
            'guest_checkout' => $order->guest_checkout,
            'customer' => [
                'email' => $order->customer_email,
                'full_name' => $order->customer_full_name,
                'phone' => $order->customer_phone,
            ],
            'payment' => [
                'provider' => $intent->provider,
                'reference' => $intent->providerReference,
                'redirect_url' => $intent->redirectUrl,
            ],
        ], 201);
    }
}
