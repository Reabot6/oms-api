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
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $vendor;
    protected $customer;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->admin()->create();
        $this->vendor = User::factory()->vendor()->create();
        $this->customer = User::factory()->customer()->create();
        
        $this->product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
    }

    /** @test */
    public function admin_can_view_inventory()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/inventory/{$this->product->inventory->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'product_id', 'quantity', 'reserved_quantity', 'available_quantity']
            ]);
    }

    /** @test */
    public function vendor_can_view_inventory()
    {
        $response = $this->actingAs($this->vendor)
            ->getJson("/api/v1/inventory/{$this->product->inventory->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_view_inventory()
    {
        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/inventory/{$this->product->inventory->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_inventory()
    {
        $inventory = $this->product->inventory;

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/inventory/{$inventory->id}", [
                'quantity' => 150,
                'reserved_quantity' => 10
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inventory', [
            'id' => $inventory->id,
            'quantity' => 150,
            'reserved_quantity' => 10
        ]);
    }

    /** @test */
    public function vendor_can_update_inventory()
    {
        $inventory = $this->product->inventory;

        $response = $this->actingAs($this->vendor)
            ->putJson("/api/v1/inventory/{$inventory->id}", [
                'quantity' => 200,
                'reserved_quantity' => 5
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_update_inventory()
    {
        $inventory = $this->product->inventory;

        $response = $this->actingAs($this->customer)
            ->putJson("/api/v1/inventory/{$inventory->id}", [
                'quantity' => 100
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_list_low_stock_items()
    {
        Product::factory()->create()->inventory->update(['quantity' => 5]);
        Product::factory()->create()->inventory->update(['quantity' => 8]);
        Product::factory()->create()->inventory->update(['quantity' => 50]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/inventory/low-stock/list?threshold=10');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function vendor_can_see_their_low_stock_items()
    {
        $vendorProduct1 = Product::factory()->create(['vendor_id' => $this->vendor->id]);
        $vendorProduct1->inventory->update(['quantity' => 5]);

        $otherVendor = User::factory()->vendor()->create();
        $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id]);
        $otherProduct->inventory->update(['quantity' => 3]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/api/v1/inventory/low-stock/list?threshold=10');

        $response->assertStatus(200);
        $productIds = collect($response->json('data'))->pluck('product_id')->toArray();
        
        $this->assertContains($vendorProduct1->id, $productIds);
        $this->assertNotContains($otherProduct->id, $productIds);
    }

    /** @test */
    public function inventory_calculates_available_quantity_correctly()
    {
        $inventory = $this->product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 25]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/inventory/{$inventory->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.available_quantity', 75);
    }

    /** @test */
    public function cannot_set_negative_quantity()
    {
        $inventory = $this->product->inventory;

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/inventory/{$inventory->id}", [
                'quantity' => -10
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function cannot_set_negative_reserved_quantity()
    {
        $inventory = $this->product->inventory;

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/inventory/{$inventory->id}", [
                'quantity' => 100,
                'reserved_quantity' => -5
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reserved_quantity']);
    }

    /** @test */
    public function inventory_model_can_reserve_quantity()
    {
        $inventory = $this->product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 10]);

        $inventory->reserve(20);

        $this->assertEquals(30, $inventory->fresh()->reserved_quantity);
    }

    /** @test */
    public function inventory_model_can_release_quantity()
    {
        $inventory = $this->product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 30]);

        $inventory->release(20);

        $this->assertEquals(10, $inventory->fresh()->reserved_quantity);
    }

    /** @test */
    public function inventory_model_can_deduct_quantity()
    {
        $inventory = $this->product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 20]);

        $inventory->deduct(15);

        $inventory = $inventory->fresh();
        $this->assertEquals(85, $inventory->quantity);
        $this->assertEquals(5, $inventory->reserved_quantity);
    }

    /** @test */
    public function inventory_model_can_restore_quantity()
    {
        $inventory = $this->product->inventory;
        $inventory->update(['quantity' => 100, 'reserved_quantity' => 20]);

        $inventory->restore(10);

        $inventory = $inventory->fresh();
        $this->assertEquals(110, $inventory->quantity); // Restores by adding to quantity
        $this->assertEquals(20, $inventory->reserved_quantity); // Reserved stays same
    }

    /** @test */
    public function low_stock_threshold_can_be_customized()
    {
        Product::factory()->create()->inventory->update(['quantity' => 15]);
        Product::factory()->create()->inventory->update(['quantity' => 8]);
        Product::factory()->create()->inventory->update(['quantity' => 3]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/inventory/low-stock/list?threshold=10');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/inventory/low-stock/list?threshold=20');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_inventory()
    {
        $response = $this->getJson("/api/v1/inventory/{$this->product->inventory->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function inventory_is_created_automatically_with_product()
    {
        $newProduct = Product::factory()->create();

        $this->assertNotNull($newProduct->inventory);
        $this->assertInstanceOf(Inventory::class, $newProduct->inventory);
    }

    /** @test */
    public function available_quantity_is_never_negative()
    {
        $inventory = $this->product->inventory;
        $inventory->update(['quantity' => 50, 'reserved_quantity' => 60]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/inventory/{$inventory->id}");

        $availableQty = $response->json('data.available_quantity');
        $this->assertGreaterThanOrEqual(0, $availableQty);
    }
}
