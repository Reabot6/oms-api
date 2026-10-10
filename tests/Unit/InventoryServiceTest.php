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
use App\Models\Product;
use App\Models\Inventory;
use App\Services\InventoryService;
use App\Repositories\InventoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new InventoryRepository(new Inventory());
        $this->service = new InventoryService($this->repository);
    }

    /** @test */
    public function it_can_update_inventory()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;

        $this->service->updateInventory($inventory->id, [
            'quantity' => 150,
            'reserved_quantity' => 20
        ]);

        $inventory = $inventory->fresh();
        $this->assertEquals(150, $inventory->quantity);
        $this->assertEquals(20, $inventory->reserved_quantity);
    }

    /** @test */
    public function it_can_get_low_stock_items()
    {
        Product::factory()->create()->inventory->update(['quantity' => 5]);
        Product::factory()->create()->inventory->update(['quantity' => 8]);
        Product::factory()->create()->inventory->update(['quantity' => 15]);
        Product::factory()->create()->inventory->update(['quantity' => 50]);

        $lowStock = $this->service->getLowStockInventory(10);

        $this->assertCount(2, $lowStock);
    }

    /** @test */
    public function it_can_get_low_stock_with_custom_threshold()
    {
        Product::factory()->create()->inventory->update(['quantity' => 5]);
        Product::factory()->create()->inventory->update(['quantity' => 12]);
        Product::factory()->create()->inventory->update(['quantity' => 18]);

        $lowStock = $this->service->getLowStockInventory(10);
        $this->assertCount(1, $lowStock);

        $lowStock = $this->service->getLowStockInventory(15);
        $this->assertCount(2, $lowStock);

        $lowStock = $this->service->getLowStockInventory(20);
        $this->assertCount(3, $lowStock);
    }

    /** @test */
    public function it_can_reserve_inventory()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 10]);

        $inventory->reserve(25);

        $this->assertEquals(35, $inventory->fresh()->reserved_quantity);
    }

    /** @test */
    public function it_can_release_inventory()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 40]);

        $inventory->release(15);

        $this->assertEquals(25, $inventory->fresh()->reserved_quantity);
    }

    /** @test */
    public function it_can_deduct_inventory()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 30]);

        $inventory->deduct(20);

        $inventory = $inventory->fresh();
        $this->assertEquals(80, $inventory->quantity);
        $this->assertEquals(10, $inventory->reserved_quantity);
    }

    /** @test */
    public function it_can_restore_inventory()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 25]);

        $inventory->restore(15);

        $inventory = $inventory->fresh();
        $this->assertEquals(115, $inventory->quantity);
        $this->assertEquals(25, $inventory->reserved_quantity);
    }

    /** @test */
    public function available_quantity_calculation_is_correct()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 35]);

        $availableQty = $inventory->quantity - $inventory->reserved_quantity;

        $this->assertEquals(65, $availableQty);
    }

    /** @test */
    public function it_detects_insufficient_stock()
    {
        $product = Product::factory()->create();
        $inventory = $product->inventory;
        $inventory->update(['quantity' => 50, 'reserved_quantity' => 40]);

        $availableQty = $inventory->quantity - $inventory->reserved_quantity;

        $this->assertEquals(10, $availableQty);
        $this->assertLessThan(15, $availableQty);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
