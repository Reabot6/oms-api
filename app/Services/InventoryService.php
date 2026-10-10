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

use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InventoryService
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository
    ) {
    }

    /**
     * Get inventory by ID.
     */
    public function getInventoryById(int $id): ?Inventory
    {
        return $this->inventoryRepository->find($id);
    }

    /**
     * Get inventory by product.
     */
    public function getInventoryByProduct(int $productId, ?int $variantId = null): ?Inventory
    {
        return $this->inventoryRepository->findByProduct($productId, $variantId);
    }

    /**
     * Create inventory.
     */
    public function createInventory(array $data): Inventory
    {
        return $this->inventoryRepository->create($data);
    }

    /**
     * Update inventory.
     */
    public function updateInventory(int $id, array $data): bool
    {
        return $this->inventoryRepository->update($id, $data);
    }

    /**
     * Delete inventory.
     */
    public function deleteInventory(int $id): bool
    {
        return $this->inventoryRepository->delete($id);
    }

    /**
     * Get low stock inventory.
     */
    public function getLowStockInventory(?int $threshold = null): Collection
    {
        $threshold = $threshold ?? config('inventory.low_stock_threshold', 10);
        return $this->inventoryRepository->getLowStock($threshold);
    }

    /**
     * Check if inventory has sufficient quantity.
     */
    public function hasSufficientQuantity(int $productId, int $quantity, ?int $variantId = null): bool
    {
        $inventory = $this->getInventoryByProduct($productId, $variantId);

        if (!$inventory) {
            return false;
        }

        return $inventory->hasSufficientQuantity($quantity);
    }
}
