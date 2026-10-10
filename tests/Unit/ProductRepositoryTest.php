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

use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ProductRepository();
    }

    public function test_can_create_product(): void
    {
        $vendor = User::factory()->create();

        $productData = [
            'vendor_id' => $vendor->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'price' => 99.99,
            'sku' => 'TEST-001',
        ];

        $product = $this->repository->create($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertDatabaseHas('products', ['sku' => 'TEST-001']);
    }

    public function test_can_find_product_by_id(): void
    {
        $product = Product::factory()->create();

        $found = $this->repository->find($product->id);

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();

        $updated = $this->repository->update($product->id, [
            'name' => 'Updated Name',
        ]);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $deleted = $this->repository->delete($product->id);

        $this->assertTrue($deleted);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
