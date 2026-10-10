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

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->vendor = User::factory()->vendor()->create();
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'sku'],
                ],
            ]);
    }

    public function test_vendor_can_create_product(): void
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'sku' => 'TEST-SKU-001',
        ];

        $response = $this->actingAs($this->vendor, 'api')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'price', 'sku'],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
        ]);
    }

    public function test_can_view_single_product(): void
    {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'price', 'sku'],
            ]);
    }
}
