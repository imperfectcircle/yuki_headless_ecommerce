<?php

use App\Domains\Cart\Models\Cart;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Customer\Models\Address;
use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Payments\Models\PaymentProviderConfig;
use App\Domains\Pricing\Models\Price;
use Illuminate\Support\Facades\App;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Mockery;

use function Pest\Laravel\postJson;

beforeEach(function () {
    // Setup payment provider
    PaymentProviderConfig::create([
        'code' => 'stripe',
        'enabled' => true,
        'position' => 1,
    ]);

    // Creiamo il mock della sessione Stripe
    $mockSession = Mockery::mock();
    $mockSession->id = 'cs_test_123';
    $mockSession->url = 'https://example.com/success';
    $mockSession->shouldReceive('toArray')
        ->andReturn([
            'id' => 'cs_test_123',
            'url' => 'https://example.com/success',
        ]);

    // Creiamo il mock del client Stripe
    $mockStripeClient = Mockery::mock(StripeClient::class);

    // Mock delle sessioni di checkout
    $mockStripeClient->checkout = Mockery::mock();
    $mockStripeClient->checkout->sessions = Mockery::mock();
    $mockStripeClient->checkout->sessions
        ->shouldReceive('create')
        ->andReturn($mockSession);

    // Iniettiamo il mock nel container Laravel
    App::instance(StripeClient::class, $mockStripeClient);
});

function createCartWithProduct(): array
{
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'status' => 'published',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-001',
        'is_active' => true,
    ]);

    Price::create([
        'priceable_type' => ProductVariant::class,
        'priceable_id' => $variant->id,
        'currency' => 'EUR',
        'amount' => 1999,
        'vat_rate' => 22,
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
        'reserved' => 0,
    ]);

    $cart = Cart::create([
        'token' => 'test-cart-token-' . uniqid(),
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1999,
        'quantity' => 2,
    ]);

    return [$cart, $variant];
}

test('can create a new cart', function () {
    $response = postJson('/api/storefront/v1/carts', [
        'currency' => 'EUR',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'token',
                'currency',
            ]
        ]);

    expect($response->json('data.currency'))->toBe('EUR');
});

test('can complete guest checkout', function () {
    [$cart, $variant] = createCartWithProduct();

    $response = postJson('/api/storefront/v1/checkout', [
        'email' => 'guest@example.com',
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'phone' => '+39 123 456 7890',
        'shipping_address' => [
            'name' => 'Mario Rossi',
            'company' => null,
            'address_line_1' => 'Via Roma 123',
            'address_line_2' => null,
            'city' => 'Milano',
            'state' => 'MI',
            'postal_code' => '20100',
            'country' => 'IT',
            'phone' => '+39 123 456 7890',
        ],
        'billing_address' => [
            'name' => 'Mario Rossi',
            'company' => null,
            'address_line_1' => 'Via Roma 123',
            'address_line_2' => null,
            'city' => 'Milano',
            'state' => 'MI',
            'postal_code' => '20100',
            'country' => 'IT',
            'phone' => '+39 123 456 7890',
        ],
        'provider' => 'stripe',
    ], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'order_id',
            'order_number',
            'status',
            'guest_checkout',
            'customer',
            'payment',
        ]);

    expect($response->json('guest_checkout'))->toBeTrue()
        ->and($response->json('customer.email'))->toBe('guest@example.com');

    // Verify order in database
    $this->assertDatabaseHas('orders', [
        'customer_email' => 'guest@example.com',
        'guest_checkout' => true,
        'customer_profile_id' => null,
    ]);

    // Verify inventory reserved
    $inventory = Inventory::where('product_variant_id', $variant->id)->first();
    expect($inventory->reserved)->toBe(2);
});

test('can complete registered user checkout', function () {
    [$cart, $variant] = createCartWithProduct();

    // Create user and profile
    $user = StorefrontUser::create([
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
        'email_verified' => true,
    ]);

    $profile = CustomerProfile::create([
        'storefront_user_id' => $user->id,
        'email' => $user->email,
        'first_name' => 'Luigi',
        'last_name' => 'Bianchi',
        'phone' => '+39 987 654 3210',
    ]);

    $shippingAddress = Address::create([
        'customer_profile_id' => $profile->id,
        'type' => 'shipping',
        'name' => 'Luigi Bianchi',
        'address_line_1' => 'Corso Italia 789',
        'city' => 'Roma',
        'postal_code' => '00100',
        'country' => 'IT',
        'is_default' => true,
    ]);

    $billingAddress = Address::create([
        'customer_profile_id' => $profile->id,
        'type' => 'billing',
        'name' => 'Luigi Bianchi',
        'address_line_1' => 'Corso Italia 789',
        'city' => 'Roma',
        'postal_code' => '00100',
        'country' => 'IT',
        'is_default' => true,
    ]);

    // Registered user checkout
    $response = postJson('/api/storefront/v1/checkout', [
        'customer_profile_id' => $profile->id,
        'email' => $profile->email,
        'first_name' => $profile->first_name,
        'last_name' => $profile->last_name,
        'phone' => $profile->phone,
        'shipping_address' => $shippingAddress->toOrderSnapshot(),
        'billing_address' => $billingAddress->toOrderSnapshot(),
        'provider' => 'stripe',
    ], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response->assertCreated();

    expect($response->json('guest_checkout'))->toBeFalse()
        ->and($response->json('customer.email'))->toBe('user@example.com');

    // Verify order
    $this->assertDatabaseHas('orders', [
        'customer_email' => 'user@example.com',
        'guest_checkout' => false,
        'customer_profile_id' => $profile->id,
    ]);
});

test('requires cart token for protected endpoints', function () {
    $response = $this->getJson('/api/storefront/v1/cart');

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Cart token is required.',
            'error' => 'missing_cart_token',
        ]);
});

test('can add item to cart', function () {
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'status' => 'published',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-001',
        'is_active' => true,
    ]);

    Price::create([
        'priceable_type' => ProductVariant::class,
        'priceable_id' => $variant->id,
        'currency' => 'EUR',
        'amount' => 1999,
        'vat_rate' => 22,
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $variant->id,
        'quantity' => 10,
        'reserved' => 0,
    ]);

    $cart = Cart::create([
        'token' => 'test-cart-token',
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $response = postJson('/api/storefront/v1/cart/items', [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response->assertOk();

    expect($response->json('data.items.0.quantity'))->toBe(2)
        ->and($response->json('data.totals.subtotal'))->toBe(3998);
});

test('can update cart item quantity', function () {
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'status' => 'published',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-001',
        'is_active' => true,
    ]);

    Price::create([
        'priceable_type' => ProductVariant::class,
        'priceable_id' => $variant->id,
        'currency' => 'EUR',
        'amount' => 1000,
        'vat_rate' => 22,
        'is_active' => true,
    ]);

    $cart = Cart::create([
        'token' => 'test-token',
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $item = $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1000,
        'quantity' => 1,
    ]);

    $response = $this->patchJson("/api/storefront/v1/cart/items/{$item->id}", [
        'quantity' => 5,
    ], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response->assertOk();

    $item->refresh();
    expect($item->quantity)->toBe(5);
});

test('can remove cart item', function () {
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'status' => 'published',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-001',
        'is_active' => true,
    ]);

    $cart = Cart::create([
        'token' => 'test-token',
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $item = $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1000,
        'quantity' => 1,
    ]);

    $response = $this->deleteJson("/api/storefront/v1/cart/items/{$item->id}", [], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response->assertOk();

    $this->assertDatabaseMissing('cart_items', [
        'id' => $item->id,
    ]);
});
