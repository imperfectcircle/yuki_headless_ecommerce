<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Cart\Models\Cart;
use App\Domains\Pricing\Models\Price;
use App\Domains\Catalog\Models\Product;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Payments\Models\PaymentProviderConfig;

uses(RefreshDatabase::class);

beforeEach(function () {
    PaymentProviderConfig::create([
        'code' => 'stripe',
        'enabled' => true,
        'position' => 1,
    ]);
});

it('can create a new cart', function () {
    $response = $this->postJson('/api/storefront/v1/carts', [
        'currency' => 'EUR',
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'token',
                'currency',
            ]
        ]);

    $this->assertDatabaseHas('carts', [
        'currency' => 'EUR',
        'status' => 'active',
    ]);
});

it('can complete a full checkout flow', function () {
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'status' => 'published',
        'backorder_enabled' => false,
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

    $response = $this->postJson('/api/storefront/v1/cart/items', [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.totals.subtotal', 3998);

    $response = $this->getJson('/api/storefront/v1/cart', [
        'X-Cart-Token' => $cart->token,
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'token',
                'currency',
                'items',
                'totals',
            ]
        ]);

    $response = $this->postJson('/api/storefront/v1/checkout', [
        'provider' => 'stripe',
    ], [
        'X-Cart-Token' => $cart->token,
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'order_id',
            'status',
            'payment' => [
                'provider',
                'reference',
                'redirect_url',
            ],
        ]);

    $inventory = Inventory::where('product_variant_id', $variant->id)->first();

    expect($inventory->quantity)->toBe(10);
    expect($inventory->reserved)->toBe(2);

    $cart->refresh();
    expect($cart->status)->toBe('converted');
});

it('requires cart token for protected endpoints', function () {
    $response = $this->getJson('/api/storefront/v1/cart');

    $response
        ->assertStatus(400)
        ->assertJson([
            'message' => 'Cart token is required.',
            'error' => 'missing_cart_token',
        ]);
});

it('can update cart item quantity', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
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

it('can remove cart item', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
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