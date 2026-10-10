<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $vendor;
    protected $admin;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->admin()->create();
        $this->vendor = User::factory()->vendor()->create();
        $this->customer = User::factory()->customer()->create();
        
        $this->product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'price' => 99.99
        ]);
    }

    /** @test */
    public function customer_can_create_order()
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test Street, Test City',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'unit_price' => 99.99
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'order_number', 'total', 'status']
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 99.99,
            'subtotal' => 199.98
        ]);
    }

    /** @test */
    public function order_creation_reserves_inventory()
    {
        $initialQuantity = $this->product->inventory->quantity;
        $initialReserved = $this->product->inventory->reserved_quantity;

        $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                        'unit_price' => 99.99
                    ]
                ]
            ]);

        $this->product->inventory->refresh();
        $this->assertEquals($initialReserved + 3, $this->product->inventory->reserved_quantity);
        $this->assertEquals($initialQuantity, $this->product->inventory->quantity);
    }

    /** @test */
    public function cannot_create_order_with_insufficient_inventory()
    {
        $this->product->inventory->update(['quantity' => 1, 'reserved_quantity' => 0]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'unit_price' => 99.99
                    ]
                ]
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function customer_can_list_their_orders()
    {
        Order::factory()->count(3)->create(['customer_id' => $this->customer->id]);
        Order::factory()->count(2)->create(['customer_id' => User::factory()->customer()->create()->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function vendor_can_see_orders_with_their_products()
    {
        $vendorProduct = Product::factory()->create(['vendor_id' => $this->vendor->id]);
        $otherProduct = Product::factory()->create(['vendor_id' => User::factory()->vendor()->create()->id]);

        $vendorOrder = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $vendorOrder->id,
            'product_id' => $vendorProduct->id
        ]);

        $otherOrder = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $otherOrder->id,
            'product_id' => $otherProduct->id
        ]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $orderIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($vendorOrder->id, $orderIds);
        $this->assertNotContains($otherOrder->id, $orderIds);
    }

    /** @test */
    public function admin_can_see_all_orders()
    {
        Order::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function customer_can_view_their_order_details()
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    /** @test */
    public function customer_cannot_view_other_customer_order()
    {
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->vendor)
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'processing'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing'
        ]);
    }

    /** @test */
    public function admin_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'shipped'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped'
        ]);
    }

    /** @test */
    public function customer_cannot_update_order_status()
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer)
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'processing'
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_can_cancel_pending_order()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'pending'
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);
        
        $this->product->inventory->update(['reserved_quantity' => 2]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function order_cancellation_restores_inventory()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'pending'
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);
        
        $this->product->inventory->update(['reserved_quantity' => 3]);
        $initialReserved = $this->product->inventory->reserved_quantity;

        $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $this->product->inventory->refresh();
        $this->assertEquals($initialReserved - 3, $this->product->inventory->reserved_quantity);
    }

    /** @test */
    public function customer_can_cancel_processing_order()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'processing'
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_cancel_shipped_order()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'shipped'
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Order cancellation failed']);
    }

    /** @test */
    public function customer_cannot_cancel_delivered_order()
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'delivered'
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_cancel_any_order()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200);
    }

    /** @test */
    public function order_requires_at_least_one_item()
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => []
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function order_item_requires_valid_product()
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [
                    [
                        'product_id' => 99999,
                        'quantity' => 1,
                        'unit_price' => 99.99
                    ]
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    /** @test */
    public function order_calculates_total_correctly()
    {
        $product1 = Product::factory()->create(['price' => 50.00]);
        $product2 = Product::factory()->create(['price' => 75.50]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2, 'unit_price' => 50.00],
                    ['product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 75.50],
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total', '175.50');
    }

    /** @test */
    public function order_generates_unique_order_number()
    {
        $response1 = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 99.99]]
            ]);

        $response2 = $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 99.99]]
            ]);

        $orderNumber1 = $response1->json('data.order_number');
        $orderNumber2 = $response2->json('data.order_number');

        $this->assertNotEquals($orderNumber1, $orderNumber2);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_order()
    {
        $response = $this->postJson('/api/v1/orders', [
            'shipping_address' => '123 Test St, City, State 12345',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 99.99]]
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function vendor_cannot_create_order()
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Test St, City, State 12345',
                'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 99.99]]
            ]);

        $response->assertStatus(403);
    }
}
