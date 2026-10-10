<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Get all products.
     */
    public function getAllProducts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    /**
     * Get active products.
     */
    public function getActiveProducts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getActive($perPage);
    }

    /**
     * Get product by ID.
     */
    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    /**
     * Get product by slug.
     */
    public function getProductBySlug(string $slug): ?Product
    {
        return $this->productRepository->findBySlug($slug);
    }

    /**
     * Get products by vendor.
     */
    public function getProductsByVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getByVendor($vendorId, $perPage);
    }

    /**
     * Search products (all vendors — admin only).
     */
    public function searchProducts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->search($query, $perPage);
    }

    /**
     * Search products scoped to a specific vendor.
     */
    public function searchProductsByVendor(string $query, int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->searchByVendor($query, $vendorId, $perPage);
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data): Product
    {
        $data['slug'] = Str::slug($data['name']);

        return $this->productRepository->create($data);
    }

    /**
     * Update a product.
     */
    public function updateProduct(int $id, array $data): bool
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $this->productRepository->update($id, $data);
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    /**
     * Check if user can manage product.
     */
    public function canManageProduct(Product $product, int $userId, string $userRole): bool
    {
        if ($userRole === 'admin') {
            return true;
        }

        if ($userRole === 'vendor' && $product->vendor_id === $userId) {
            return true;
        }

        return false;
    }
}
