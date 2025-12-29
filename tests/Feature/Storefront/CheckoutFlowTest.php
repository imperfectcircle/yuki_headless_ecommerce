<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;
use App\Domains\Cart\Models\Cart;
use App\Domains\Pricing\Models\Price;
use App\Domains\Catalog\Models\Product;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Payments\Models\PaymentProviderConfig;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup payment provider
        PaymentProviderConfig::create([
            'code' => 'stripe',
            'enabled' => true,
            'position' => 1,
        ]);
    }

    /** @test */
    public function it_can_create_a_new_cart()
    {
        $response = $this->postJson('/api/storefront/v1/carts', [
            'currency' => 'EUR',
        ]);

        $response->assertCreated()
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
    }

    /** @test */
    public function it_can_complete_a_full_checkout_flow()
    {
        // Create product with variant
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

        // Create price
        Price::create([
            'priceable_type' => ProductVariant::class,
            'priceable_id' => $variant->id,
            'currency' => 'EUR',
            'amount' => 1999, // €19.99
            'vat_rate' => 22,
            'is_active' => true,
        ]);

        // Create inventory
        Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved' => 0,
        ]);

        // Create cart
        $cart = Cart::create([
            'token' => 'test-cart-token-' . uniqid(),
            'status' => 'active',
            'currency' => 'EUR',
        ]);

        // Add item to cart
        $response = $this->postJson('/api/storefront/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ], [
            'X-Cart-Token' => $cart->token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.totals.subtotal', 3998);

        // Verify cart
        $response = $this->getJson('/api/storefront/v1/cart', [
            'X-Cart-Token' => $cart->token,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'token',
                    'currency',
                    'items',
                    'totals',
                ]
            ]);

        // Proceed to checkout
        $response = $this->postJson('/api/storefront/v1/checkout', [
            'provider' => 'stripe',
        ], [
            'X-Cart-Token' => $cart->token,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'order_id',
                'status',
                'payment' => [
                    'provider',
                    'reference',
                    'redirect_url',
                ],
            ]);

        // Verify inventory is reserved
        $inventory = Inventory::where('product_variant_id', $variant->id)->first();
        $this->assertEquals(10, $inventory->quantity);
        $this->assertEquals(2, $inventory->reserved);

        // Verify cart is converted
        $cart->refresh();
        $this->assertEquals('converted', $cart->status);
    }

    /** @test */
    public function it_requires_cart_token_for_protected_endpoints()
    {
        $response = $this->getJson('/api/storefront/v1/cart');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cart token is required.',
                'error' => 'missing_cart_token',
            ]);
    }

    /** @test */
    public function it_can_update_cart_item_quantity()
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        
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
        $this->assertEquals(5, $item->quantity);
    }

    /** @test */
    public function it_can_remove_cart_item()
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

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
    }
}
