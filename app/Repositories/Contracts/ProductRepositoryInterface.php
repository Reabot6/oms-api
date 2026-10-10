<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function findBySku(string $sku): ?Product;

    public function create(array $data): Product;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    public function searchByVendor(string $query, int $vendorId, int $perPage = 15): LengthAwarePaginator;

    public function searchByVendor(string $query, int $vendorId, int $perPage = 15): LengthAwarePaginator;

    public function getByVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator;

    public function getActive(int $perPage = 15): LengthAwarePaginator;
}
