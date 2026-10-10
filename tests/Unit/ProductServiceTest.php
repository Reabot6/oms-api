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
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = new ProductRepository();
        $this->service = new ProductService($repository);
    }

    public function test_can_create_product_with_slug_generation(): void
    {
        $vendor = User::factory()->vendor()->create();
        
        $productData = [
            'vendor_id' => $vendor->id,
            'name' => 'Test Product Name',
            'description' => 'Description',
            'price' => 99.99,
            'sku' => 'TEST-SKU',
        ];

        $product = $this->service->createProduct($productData);

        $this->assertEquals('test-product-name', $product->slug);
        $this->assertEquals('Test Product Name', $product->name);
    }

    public function test_can_check_product_management_permission(): void
    {
        $vendor1 = User::factory()->vendor()->create();
        $vendor2 = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor1->id]);

        // Admin can manage any product
        $canManage = $this->service->canManageProduct($product, 999, 'admin');
        $this->assertTrue($canManage);

        // Vendor can manage own product
        $canManage = $this->service->canManageProduct($product, $vendor1->id, 'vendor');
        $this->assertTrue($canManage);

        // Vendor cannot manage other's product
        $canManage = $this->service->canManageProduct($product, $vendor2->id, 'vendor');
        $this->assertFalse($canManage);

        // Customer cannot manage product
        $canManage = $this->service->canManageProduct($product, $vendor1->id, 'customer');
        $this->assertFalse($canManage);
    }
}
