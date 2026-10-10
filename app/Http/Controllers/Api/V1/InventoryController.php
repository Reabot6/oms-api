<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Display the specified inventory.
     */
    public function show(int $id): JsonResponse
    {
        $inventory = $this->inventoryService->getInventoryById($id);

        if (!$inventory) {
            return response()->json(['message' => 'Inventory not found'], 404);
        }

        $inventory->load(['product', 'productVariant']);

        return response()->json([
            'message' => 'Inventory retrieved successfully',
            'data' => new InventoryResource($inventory),
        ]);
    }

    /**
     * Update the specified inventory.
     */
    public function update(InventoryRequest $request, int $id): JsonResponse
    {
        $inventory = $this->inventoryService->getInventoryById($id);

        if (!$inventory) {
            return response()->json(['message' => 'Inventory not found'], 404);
        }

        $this->inventoryService->updateInventory($id, $request->validated());
        $inventory->refresh();

        return response()->json([
            'message' => 'Inventory updated successfully',
            'data' => new InventoryResource($inventory),
        ]);
    }

    /**
     * Get low stock inventory.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->input('threshold');
        $lowStock = $this->inventoryService->getLowStockInventory($threshold);
        
        // Filter by vendor if user is a vendor
        $user = $request->user();
        if ($user && $user->role && $user->role->slug === 'vendor') {
            $lowStock = $lowStock->filter(function ($inventory) use ($user) {
                return $inventory->product && $inventory->product->vendor_id === $user->id;
            })->values();
        }
        
        $lowStock->load(['product', 'productVariant']);

        return response()->json([
            'data' => InventoryResource::collection($lowStock),
        ]);
    }
}
