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

use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository implements InventoryRepositoryInterface
{
    /**
     * Get all inventory.
     */
    public function all(): Collection
    {
        return Inventory::get();
    }

    /**
     * Find inventory by ID.
     */
    public function find(int $id): ?Inventory
    {
        return Inventory::find($id);
    }

    /**
     * Find inventory by product.
     */
    public function findByProduct(int $productId, ?int $variantId = null): ?Inventory
    {
        $query = Inventory::where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        return $query->first();
    }

    /**
     * Create new inventory.
     */
    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    /**
     * Update inventory.
     */
    public function update(int $id, array $data): bool
    {
        return Inventory::where('id', $id)->update($data);
    }

    /**
     * Delete inventory.
     */
    public function delete(int $id): bool
    {
        return Inventory::where('id', $id)->delete() > 0;
    }

    /**
     * Get low stock inventory.
     * Returns items where available quantity is less than or equal to threshold.
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return Inventory::whereRaw('(quantity - reserved_quantity) <= ?', [$threshold])->get();
    }
}
