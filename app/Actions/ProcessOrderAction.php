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

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProcessOrderAction
{
    public function __construct(
        private OrderService $orderService,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Execute the action.
     */
    public function execute(array $data): Order
    {
        $this->validate($data);

        return DB::transaction(function () use ($data) {
            // Calculate totals — price always fetched from DB to prevent price injection
            $subtotal = 0;
            foreach ($data['items'] as &$item) {
                if (!empty($item['product_variant_id'])) {
                    $variant = ProductVariant::findOrFail($item['product_variant_id']);
                    $item['unit_price'] = $variant->price;
                } else {
                    $product = Product::findOrFail($item['product_id']);
                    $item['unit_price'] = $product->price;
                }
                $item['subtotal'] = $item['quantity'] * $item['unit_price'];
                $subtotal += $item['subtotal'];
            }

            $data['subtotal'] = $subtotal;
            $data['tax'] = $data['tax'] ?? 0;
            $data['shipping'] = $data['shipping'] ?? 0;
            $data['total'] = $subtotal + $data['tax'] + $data['shipping'];

            // Create order
            return $this->orderService->createOrder($data);
        });
    }

    /**
     * Validate order data.
     */
    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'customer_id' => 'required|exists:users,id',
            'shipping_address' => 'required|string',
            'billing_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'tax' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'sometimes|numeric|min:0', // ignored — price set server-side
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check inventory availability
        foreach ($data['items'] as $item) {
            if (!$this->inventoryService->hasSufficientQuantity(
                $item['product_id'],
                $item['quantity'],
                $item['product_variant_id'] ?? null
            )) {
                throw new \Exception('Insufficient inventory for product ID: ' . $item['product_id']);
            }
        }
    }
}
