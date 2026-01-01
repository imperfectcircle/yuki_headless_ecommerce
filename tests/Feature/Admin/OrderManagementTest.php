<?php
// tests/Feature/Admin/OrderManagementTest.php

use App\Domains\Cart\Models\Cart;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\ProductVariant;
use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Inventory\Models\Inventory;
use App\Domains\Order\Actions\CreateOrderFromCart;
use App\Domains\Order\Actions\ReserveOrder;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderStatusHistory;
use App\Domains\Payments\Models\PaymentProviderConfig;
use App\Domains\Pricing\Models\Price;
use App\Domains\Storefront\DTOs\Checkout\BillingAddressDTO;
use App\Domains\Storefront\DTOs\Checkout\CheckoutRequestDTO;
use App\Domains\Storefront\DTOs\Checkout\ShippingAddressDTO;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create admin user
    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
    ]);
    
    // Setup payment provider
    PaymentProviderConfig::create([
        'code' => 'stripe',
        'enabled' => true,
        'position' => 1,
    ]);
});

function createTestOrder(): Order
{
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product-' . Str::uuid(),
        'status' => 'published',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-001-' . Str::uuid(),
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
        'token' => 'test-cart-token-' . Str::uuid(),
        'status' => 'active',
        'currency' => 'EUR',
    ]);

    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'unit_price' => 1999,
        'quantity' => 2,
    ]);

    $checkoutData = new CheckoutRequestDTO(
        email: 'customer@example.com',
        firstName: 'John',
        lastName: 'Doe',
        phone: '+1234567890',
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
    );

    $order = app(CreateOrderFromCart::class)->execute($cart, $checkoutData);
    $order = app(ReserveOrder::class)->execute($order);
    
    // Mark as paid
    $order->markAsPaid();
    
    return $order->fresh();
}

// ==========================================
// INERTIA PAGE TESTS
// ==========================================

test('admin can view orders index page', function () {
    $order1 = createTestOrder();
    $order2 = createTestOrder();

    $response = $this->actingAs($this->admin)
        ->get('/admin/orders');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->has('orders.data', 2)
            ->has('orders.meta')
            ->has('filters.statuses')
        );
});

test('admin can view order detail page', function () {
    $order = createTestOrder();

    $response = $this->actingAs($this->admin)
        ->get("/admin/orders/{$order->id}");

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Show')
            ->has('order')
            ->where('order.id', $order->id)
            ->where('order.number', $order->number)
            ->has('order.items')
            ->has('order.timeline')
            ->has('available_statuses')
        );
});

test('guest cannot access admin orders page', function () {
    $response = $this->get('/admin/orders');

    $response->assertRedirect('/login');
});

// ==========================================
// ORDER ACTIONS TESTS
// ==========================================

test('admin can process order', function () {
    $order = createTestOrder();
    expect($order->status)->toBe(Order::STATUS_PAID);

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/process");

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PROCESSING);

    // Check status history was created
    expect($order->statusHistory()->count())->toBe(1);
    
    $history = $order->statusHistory()->first();
    expect($history->from_status)->toBe(Order::STATUS_PAID);
    expect($history->to_status)->toBe(Order::STATUS_PROCESSING);
    expect($history->user_id)->toBe($this->admin->id);
});

test('admin can fulfill order', function () {
    $order = createTestOrder();
    $order->markAsProcessing('Processing started', $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/fulfill");

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_FULFILLED);
});

test('admin can ship order with tracking info', function () {
    $order = createTestOrder();
    $order->markAsProcessing(null, $this->admin->id);
    $order->markAsFulfilled(null, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/ship", [
            'tracking_number' => 'TRACK123456',
            'carrier' => 'FedEx',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_SHIPPED);

    // Check tracking info is in status history
    $statusHistory = $order->statusHistory()
        ->where('to_status', Order::STATUS_SHIPPED)
        ->first();
    
    expect($statusHistory->note)->toContain('TRACK123456');
    expect($statusHistory->note)->toContain('FedEx');
});

test('admin can mark order as delivered', function () {
    $order = createTestOrder();
    $order->markAsProcessing(null, $this->admin->id);
    $order->markAsFulfilled(null, $this->admin->id);
    $order->markAsShipped(null, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/complete");

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_DELIVERED);
});

test('admin can cancel order and release inventory', function () {
    $order = createTestOrder();
    expect($order->status)->toBe(Order::STATUS_PAID);

    // Check inventory is reserved
    $item = $order->items->first();
    $inventory = $item->productVariant->inventory;
    $initialReserved = $inventory->reserved;
    expect($initialReserved)->toBe(2);

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Customer requested cancellation',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);

    // Check inventory was released
    $inventory->refresh();
    expect($inventory->reserved)->toBe($initialReserved - 2);
    
    // Check reason in history
    $history = $order->statusHistory()
        ->where('to_status', Order::STATUS_CANCELLED)
        ->first();
    expect($history->note)->toBe('Customer requested cancellation');
});

test('admin can refund order and restock inventory', function () {
    $order = createTestOrder();
    
    $item = $order->items->first();
    $inventory = $item->productVariant->inventory;
    $initialQuantity = $inventory->quantity;

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", [
            'reason' => 'Defective product',
            'restock_inventory' => true,
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_REFUNDED);

    // Check inventory was restocked
    $inventory->refresh();
    expect($inventory->quantity)->toBe($initialQuantity + 2);
});

test('admin can refund order without restocking inventory', function () {
    $order = createTestOrder();
    
    $item = $order->items->first();
    $inventory = $item->productVariant->inventory;
    $initialQuantity = $inventory->quantity;

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", [
            'reason' => 'Customer dissatisfied',
            'restock_inventory' => false,
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_REFUNDED);

    // Check inventory was NOT restocked
    $inventory->refresh();
    expect($inventory->quantity)->toBe($initialQuantity);
});

test('cannot transition order to invalid status', function () {
    $order = createTestOrder(); // Status: paid

    // Try to ship directly (invalid - must go through processing and fulfilled)
    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/ship");

    $response->assertRedirect()
        ->assertSessionHas('error');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PAID); // Status unchanged
});

test('admin can update order status with generic endpoint', function () {
    $order = createTestOrder();

    $response = $this->actingAs($this->admin)
        ->patch("/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_PROCESSING,
            'note' => 'Started processing via status update',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
    
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_PROCESSING);
});

// ==========================================
// FILTERING TESTS
// ==========================================

test('admin can filter orders by status', function () {
    $order1 = createTestOrder(); // paid
    $order2 = createTestOrder();
    $order2->markAsProcessing(null, $this->admin->id); // processing

    $response = $this->actingAs($this->admin)
        ->get('/admin/orders?status=' . Order::STATUS_PROCESSING);

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.status', Order::STATUS_PROCESSING)
        );
});

test('admin can search orders by customer email', function () {
    $order = createTestOrder(); // email: customer@example.com

    $response = $this->actingAs($this->admin)
        ->get('/admin/orders?customer_email=customer');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->where('orders.meta.total', 1)
        );
});

test('admin can filter orders by date range', function () {
    // Create order yesterday
    $oldOrder = createTestOrder();

    $oldOrder->created_at = now()->subDays(2);
    $oldOrder->save();
    
    // Create order today
    $newOrder = createTestOrder();

    $response = $this->actingAs($this->admin)
        ->get('/admin/orders?date_from=' . now()->startOfDay()->toDateString());
    
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->where('orders.meta.total', 1)
        );
});

// ==========================================
// AUTHORIZATION TESTS
// ==========================================

test('non authenticated user cannot access admin orders', function () {
    $order = createTestOrder();

    $this->get('/admin/orders')->assertRedirect('/login');
    $this->get("/admin/orders/{$order->id}")->assertRedirect('/login');
    $this->post("/admin/orders/{$order->id}/process")->assertRedirect('/login');
});

// ==========================================
// VALIDATION TESTS
// ==========================================

test('shipping requires valid tracking data', function () {
    $order = createTestOrder();
    $order->markAsProcessing(null, $this->admin->id);
    $order->markAsFulfilled(null, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/ship", [
            'tracking_number' => str_repeat('a', 300), // Too long
        ]);

    $response->assertSessionHasErrors('tracking_number');
});

test('status update requires valid status', function () {
    $order = createTestOrder();

    $response = $this->actingAs($this->admin)
        ->patch("/admin/orders/{$order->id}/status", [
            'status' => 'invalid_status',
        ]);

    $response->assertSessionHasErrors('status');
});

// ==========================================
// STATUS HISTORY TESTS
// ==========================================

test('status changes are tracked in history', function () {
    $order = createTestOrder();
    
    expect($order->statusHistory()->count())->toBe(0);

    // Process order
    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/process");

    $order->refresh();
    expect($order->statusHistory()->count())->toBe(1);

    // Fulfill order
    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/fulfill");

    $order->refresh();
    expect($order->statusHistory()->count())->toBe(2);

    // Check history details
    $history = $order->statusHistory()->orderBy('id', 'asc')->get(); // It was created_at instead of id
    
    expect($history[0]->from_status)->toBe(Order::STATUS_PAID);
    expect($history[0]->to_status)->toBe(Order::STATUS_PROCESSING);
    expect($history[0]->user_id)->toBe($this->admin->id);
    
    expect($history[1]->from_status)->toBe(Order::STATUS_PROCESSING);
    expect($history[1]->to_status)->toBe(Order::STATUS_FULFILLED);
    expect($history[1]->user_id)->toBe($this->admin->id);
});

test('status history includes user information', function () {
    $order = createTestOrder();

    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/process");

    $history = $order->statusHistory()->first();
    
    expect($history->user_id)->toBe($this->admin->id);
    expect($history->user)->not->toBeNull();
    expect($history->user->email)->toBe($this->admin->email);
});