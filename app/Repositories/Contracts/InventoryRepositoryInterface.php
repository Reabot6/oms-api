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

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Inventory;

    public function findByProduct(int $productId, ?int $variantId = null): ?Inventory;

    public function create(array $data): Inventory;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function getLowStock(int $threshold = 10): Collection;
}
