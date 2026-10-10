<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Actions;

use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductsAction
{
    public function __construct(
        private ProductService $productService,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Execute the action.
     */
    public function execute(string $filePath, int $vendorId): array
    {
        $data = Excel::toArray([], $filePath)[0];

        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            // Skip header row
            array_shift($data);

            foreach ($data as $index => $row) {
                try {
                    $this->importRow($row, $vendorId, $index);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Import single row.
     */
    private function importRow(array $row, int $vendorId, int $index): void
    {
        // Expected columns: name, description, price, sku, quantity
        $data = [
            'vendor_id' => $vendorId,
            'name' => $row[0] ?? null,
            'description' => $row[1] ?? null,
            'price' => $row[2] ?? null,
            'sku' => $row[3] ?? null,
            'quantity' => $row[4] ?? 0,
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $quantity = $data['quantity'];
        unset($data['quantity']);

        $product = $this->productService->createProduct($data);

        // Create inventory
        $this->inventoryService->createInventory([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 10,
        ]);
    }
}
