<?php

use App\Domains\Cart\Models\Cart;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Order\Actions\CreateOrderFromCart;
use App\Domains\Order\Actions\ReserveOrder;
use App\Domains\Order\Models\Order;
use App\Domains\Payments\Models\PaymentProviderConfig;
use App\Domains\Pricing\Models\Price;
use App\Domains\Storefront\DTOs\Checkout\BillingAddressDTO;
use App\Domains\Storefront\DTOs\Checkout\CheckoutRequestDTO;
use App\Domains\Storefront\DTOs\Checkout\ShippingAddressDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup payment provider
    PaymentProviderConfig::create([
        'code' => 'stripe',
        'enabled' => true,
        'position' => 1,
    ]);
});

function createRegisteredCustomerWithOrder(): array
{
    // Create user and profile
    $user = StorefrontUser::create([
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
        'email_verified' => true,
    ]);

    $profile = CustomerProfile::create([
        'storefront_user_id' => $user->id,
        'email' => $user->email,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+1234567890',
    ]);

    // Create product
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

    // Create cart and order
    $cart = Cart::create([
        'token' => 'test-cart-token',
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1999,
        'quantity' => 2,
    ]);

    $checkoutData = new CheckoutRequestDTO(
        email: $profile->email,
        firstName: $profile->first_name,
        lastName: $profile->last_name,
        phone: $profile->phone,
        shippingAddress: new ShippingAddressDTO(
            name: 'John Doe',
            company: null,
            addressLine1: '123 Main St',
            addressLine2: null,
            city: 'New York',
            state: 'NY',
            postalCode: '10001',
            country: 'US',
            phone: '+1234567890',
        ),
        billingAddress: new BillingAddressDTO(
            name: 'John Doe',
            company: null,
            addressLine1: '123 Main St',
            addressLine2: null,
            city: 'New York',
            state: 'NY',
            postalCode: '10001',
            country: 'US',
            phone: '+1234567890',
        ),
        customerProfileId: $profile->id,
    );

    $order = app(CreateOrderFromCart::class)->execute($cart, $checkoutData);
    $order = app(ReserveOrder::class)->execute($order);
    $order->markAsPaid();

    $order->payments()->create([
        'provider' => 'stripe',
        'provider_reference' => 'ch_test_' . \Illuminate\Support\Str::random(24),
        'status' => 'paid',
        'amount' => $order->grand_total,
        'currency' => $order->currency,
        'payload' => [],
    ]);

    return [$user, $profile, $order->fresh()];
}

function createGuestOrder(): Order
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
        'token' => 'guest-cart-token',
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1999,
        'quantity' => 1,
    ]);

    $checkoutData = new CheckoutRequestDTO(
        email: 'guest@example.com',
        firstName: 'Jane',
        lastName: 'Smith',
        phone: '+9876543210',
        shippingAddress: new ShippingAddressDTO(
            name: 'Jane Smith',
            company: null,
            addressLine1: '456 Oak Ave',
            addressLine2: null,
            city: 'Los Angeles',
            state: 'CA',
            postalCode: '90001',
            country: 'US',
            phone: '+9876543210',
        ),
        billingAddress: new BillingAddressDTO(
            name: 'Jane Smith',
            company: null,
            addressLine1: '456 Oak Ave',
            addressLine2: null,
            city: 'Los Angeles',
            state: 'CA',
            postalCode: '90001',
            country: 'US',
            phone: '+9876543210',
        ),
    );

    $order = app(CreateOrderFromCart::class)->execute($cart, $checkoutData);
    $order = app(ReserveOrder::class)->execute($order);
    $order->markAsPaid();

    return $order->fresh();
}

// ==========================================
// AUTHENTICATED CUSTOMER TESTS
// ==========================================

test('authenticated customer can view their orders', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    // Create another order for same customer
    $cart = Cart::create([
        'token' => 'another-cart',
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $variant = ProductVariant::first();
    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1999,
        'quantity' => 1,
    ]);

    $checkoutData = new CheckoutRequestDTO(
        email: $profile->email,
        firstName: $profile->first_name,
        lastName: $profile->last_name,
        phone: $profile->phone,
        shippingAddress: ShippingAddressDTO::fromArray([
            'name' => 'John Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'country' => 'US',
        ]),
        billingAddress: BillingAddressDTO::fromArray([
            'name' => 'John Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'country' => 'US',
        ]),
        customerProfileId: $profile->id,
    );

    $order2 = app(CreateOrderFromCart::class)->execute($cart, $checkoutData);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/storefront/v1/orders');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'number',
                    'status',
                    'customer',
                    'total',
                    'items_count',
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'total',
            ],
        ])
        ->assertJsonCount(2, 'data');
});

test('authenticated customer can view order detail by ID', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/storefront/v1/orders/{$order->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'number',
                'status',
                'totals',
                'customer',
                'addresses',
                'items',
                'timeline',
            ],
        ])
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.number', $order->number);
});

test('customer cannot view other customers orders', function () {
    [$user1, $profile1, $order1] = createRegisteredCustomerWithOrder();
    
    // Create another customer
    $user2 = StorefrontUser::create([
        'email' => 'other@example.com',
        'password' => bcrypt('password'),
        'email_verified' => true,
    ]);

    Sanctum::actingAs($user2);

    $response = $this->getJson("/api/storefront/v1/orders/{$order1->id}");

    $response->assertNotFound();
});

test('authenticated customer can track order', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/storefront/v1/orders/{$order->id}/tracking");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'order_number',
                'status',
                'timeline',
            ],
        ])
        ->assertJsonPath('data.order_number', $order->number)
        ->assertJsonPath('data.status', $order->status);
});

test('unauthenticated user cannot view orders list without email', function () {
    $response = $this->getJson('/api/storefront/v1/orders');

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('guest can view orders with email', function () {
    $order = createGuestOrder();
    
    $response = $this->getJson('/api/storefront/v1/orders?email=guest@example.com');
    
    $response->assertOk();
});

test('unauthenticated user cannot view order detail by ID', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    $response = $this->getJson("/api/storefront/v1/orders/{$order->id}");

    $response->assertStatus(401);
});

// ==========================================
// GUEST ORDER LOOKUP TESTS
// ==========================================

test('guest can lookup order by number and email', function () {
    $order = createGuestOrder();
    expect($order->guest_checkout)->toBeTrue();

    $response = $this->getJson("/api/storefront/v1/orders/lookup/{$order->number}?email=guest@example.com");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'number',
                'status',
                'totals',
                'customer',
                'items',
            ],
        ])
        ->assertJsonPath('data.number', $order->number);
});

test('guest cannot lookup order with wrong email', function () {
    $order = createGuestOrder();

    $response = $this->getJson("/api/storefront/v1/orders/lookup/{$order->number}?email=wrong@example.com");

    $response->assertNotFound();
});

test('guest cannot lookup order without email', function () {
    $order = createGuestOrder();

    $response = $this->getJson("/api/storefront/v1/orders/lookup/{$order->number}");

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('guest cannot lookup registered customer order', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();
    expect($order->guest_checkout)->toBeFalse();

    $response = $this->getJson("/api/storefront/v1/orders/lookup/{$order->number}?email={$order->customer_email}");

    $response->assertNotFound();
});

// ==========================================
// PAGINATION TESTS
// ==========================================

test('orders are paginated', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    // Create 15 more orders
    for ($i = 0; $i < 15; $i++) {
        $cart = Cart::create([
            'token' => "cart-{$i}",
            'status' => 'active',
            'currency' => 'EUR',
        ]);

        $variant = ProductVariant::first();
        $cart->items()->create([
            'product_variant_id' => $variant->id,
            'unit_price' => 1999,
            'quantity' => 1,
        ]);

        $checkoutData = new CheckoutRequestDTO(
            email: $profile->email,
            firstName: $profile->first_name,
            lastName: $profile->last_name,
            phone: $profile->phone,
            shippingAddress: ShippingAddressDTO::fromArray([
                'name' => 'John Doe',
                'address_line_1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ]),
            billingAddress: BillingAddressDTO::fromArray([
                'name' => 'John Doe',
                'address_line_1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ]),
            customerProfileId: $profile->id,
        );

        app(CreateOrderFromCart::class)->execute($cart, $checkoutData);
    }

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/storefront/v1/orders?per_page=5');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 16);
});

test('customer can request specific page', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    // Create more orders
    for ($i = 0; $i < 10; $i++) {
        $cart = Cart::create([
            'token' => "cart-{$i}",
            'status' => 'active',
            'currency' => 'EUR',
        ]);

        $variant = ProductVariant::first();
        $cart->items()->create([
            'product_variant_id' => $variant->id,
            'unit_price' => 1999,
            'quantity' => 1,
        ]);

        $checkoutData = new CheckoutRequestDTO(
            email: $profile->email,
            firstName: $profile->first_name,
            lastName: $profile->last_name,
            phone: $profile->phone,
            shippingAddress: ShippingAddressDTO::fromArray([
                'name' => 'John Doe',
                'address_line_1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ]),
            billingAddress: BillingAddressDTO::fromArray([
                'name' => 'John Doe',
                'address_line_1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ]),
            customerProfileId: $profile->id,
        );

        app(CreateOrderFromCart::class)->execute($cart, $checkoutData);
    }

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/storefront/v1/orders?per_page=5&page=2');

    $response->assertOk()
        ->assertJsonPath('meta.current_page', 2);
});

// ==========================================
// ORDER TIMELINE TESTS
// ==========================================

test('order timeline includes status changes', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    // Make some status changes
    $order->markAsProcessing('Processing started');
    $order->markAsFulfilled('Ready to ship');

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/storefront/v1/orders/{$order->id}");

    $response->assertOk()
        ->assertJsonCount(4, 'data.timeline'); // creation + 2 status changes + payment

    $timeline = $response->json('data.timeline');
    
    expect($timeline[0]['type'])->toBe('order_created');
    expect(collect($timeline)->where('type', 'status_change')->count())->toBeGreaterThan(0);
});

// ==========================================
// VALIDATION TESTS
// ==========================================

test('per_page parameter is validated', function () {
    [$user, $profile, $order] = createRegisteredCustomerWithOrder();

    Sanctum::actingAs($user);

    // Too large
    $response = $this->getJson('/api/storefront/v1/orders?per_page=100');
    $response->assertStatus(422);

    // Invalid type
    $response = $this->getJson('/api/storefront/v1/orders?per_page=abc');
    $response->assertStatus(422);
});

