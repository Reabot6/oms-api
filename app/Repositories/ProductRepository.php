<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Get all products.
     */
    public function all(): Collection
    {
        return Product::get();
    }

    /**
     * Paginate products.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['inventory'])->latest()->paginate($perPage);
    }

    /**
     * Find product by ID.
     */
    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Find product by slug.
     */
    public function findBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)->first();
    }

    /**
     * Find product by SKU.
     */
    public function findBySku(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update a product.
     */
    public function update(int $id, array $data): bool
    {
        return Product::where('id', $id)->update($data);
    }

    /**
     * Delete a product.
     */
    public function delete(int $id): bool
    {
        return Product::where('id', $id)->delete() > 0;
    }

    /**
     * Search products (all vendors).
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['inventory'])->search($query)->paginate($perPage);
    }

    /**
     * Search products scoped to a specific vendor.
     */
    public function searchByVendor(string $query, int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['inventory'])
            ->where('vendor_id', $vendorId)
            ->search($query)
            ->paginate($perPage);
    }

    /**
     * Get products by vendor.
     */
    public function getByVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['inventory'])->where('vendor_id', $vendorId)->latest()->paginate($perPage);
    }

    /**
     * Get active products.
     */
    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['inventory'])->active()->latest()->paginate($perPage);
    }
}
