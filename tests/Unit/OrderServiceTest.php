<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository(new Order());
        $inventoryRepository = new \App\Repositories\InventoryRepository(new \App\Models\Inventory());
        $inventoryService = new \App\Services\InventoryService($inventoryRepository);
        $this->service = new OrderService($this->repository, $inventoryService);
    }

    /** @test */
    public function it_can_create_order_with_items()
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create(['price' => 100.00]);

        $data = [
            'customer_id' => $customer->id,
            'subtotal' => 200.00,
            'tax' => 0,
            'shipping' => 0,
            'total' => 200.00,
            'shipping_address' => '123 Test St',
            'billing_address' => '123 Test St',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                    'subtotal' => 200.00,
                ]
            ]
        ];

        $order = $this->service->createOrder($data);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals(200.00, $order->total);
        $this->assertEquals('pending', $order->status);
        $this->assertCount(1, $order->items);
    }

    /** @test */
    public function it_generates_unique_order_number()
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'subtotal' => 50.00,
            'tax' => 0,
            'shipping' => 0,
            'total' => 50.00,
            'shipping_address' => '123 Test St',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 50.00,
                    'subtotal' => 50.00,
                ]
            ]
        ];

        $order1 = $this->service->createOrder($data);
        $order2 = $this->service->createOrder($data);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertStringStartsWith('ORD-', $order1->order_number);
        $this->assertStringStartsWith('ORD-', $order2->order_number);
    }

    /** @test */
    public function it_can_update_order_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $updated = $this->service->updateOrderStatus($order->id, 'processing');

        $this->assertTrue($updated);
        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    /** @test */
    public function it_can_cancel_order()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $cancelled = $this->service->cancelOrder($order->id);

        $this->assertTrue($cancelled);
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /** @test */
    public function it_filters_orders_by_customer()
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();

        Order::factory()->count(3)->create(['customer_id' => $customer1->id]);
        Order::factory()->count(2)->create(['customer_id' => $customer2->id]);

        $orders = $this->service->getOrdersByCustomer($customer1->id, 100);

        $this->assertCount(3, $orders);
    }

    /** @test */
    public function it_filters_orders_by_status()
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'processing']);
        Order::factory()->count(1)->create(['status' => 'delivered']);

        $pendingOrders = $this->service->getOrdersByStatus('pending', 100);
        $processingOrders = $this->service->getOrdersByStatus('processing', 100);

        $this->assertCount(3, $pendingOrders);
        $this->assertCount(2, $processingOrders);
    }

    /** @test */
    public function it_calculates_order_total_correctly()
    {
        $customer = User::factory()->customer()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'subtotal' => 190.00,
            'tax' => 0,
            'shipping' => 0,
            'total' => 190.00,
            'shipping_address' => '123 Test St',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'subtotal' => 100.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'unit_price' => 30.00,
                    'subtotal' => 90.00,
                ],
            ]
        ];

        $order = $this->service->createOrder($data);

        $this->assertEquals(190.00, $order->total);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
