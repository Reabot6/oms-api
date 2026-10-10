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

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Get all orders.
     */
    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($perPage);
    }

    /**
     * Get order by ID.
     */
    public function getOrderById(int $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    /**
     * Get order by order number.
     */
    public function getOrderByNumber(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByOrderNumber($orderNumber);
    }

    /**
     * Get orders by customer.
     */
    public function getOrdersByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByCustomer($customerId, $perPage);
    }

    /**
     * Get orders by vendor.
     */
    public function getOrdersByVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByVendor($vendorId, $perPage);
    }

    /**
     * Get orders by status.
     */
    public function getOrdersByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->getByStatus($status, $perPage);
    }

    /**
     * Create a new order.
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $orderData = [
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $data['customer_id'],
                'status' => 'pending',
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'] ?? 0,
                'shipping' => $data['shipping'] ?? 0,
                'total' => $data['total'],
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                'notes' => $data['notes'] ?? null,
            ];

            $order = $this->orderRepository->create($orderData);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Reserve inventory
                $inventory = $this->inventoryService->getInventoryByProduct(
                    $item['product_id'],
                    $item['product_variant_id'] ?? null
                );

                if ($inventory) {
                    $inventory->reserve($item['quantity']);
                }
            }

            event(new OrderCreated($order));

            return $order;
        });
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(int $id, string $status): bool
    {
        $order = $this->getOrderById($id);

        if (!$order) {
            return false;
        }

        $oldStatus = $order->status;

        $updated = $this->orderRepository->update($id, [
            'status' => $status,
            'confirmed_at' => $status === 'processing' ? now() : $order->confirmed_at,
            'shipped_at' => $status === 'shipped' ? now() : $order->shipped_at,
            'delivered_at' => $status === 'delivered' ? now() : $order->delivered_at,
            'cancelled_at' => $status === 'cancelled' ? now() : $order->cancelled_at,
        ]);

        if ($updated) {
            $order->refresh();
            event(new OrderStatusChanged($order, $oldStatus));
        }

        return $updated;
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(int $id): bool
    {
        $order = $this->getOrderById($id);

        if (!$order || !$order->canBeCancelled()) {
            return false;
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $inventory = $this->inventoryService->getInventoryByProduct(
                    $item->product_id,
                    $item->product_variant_id
                );

                if ($inventory) {
                    // release() returns reserved stock to available pool.
                    // restore() must NOT be called here — no stock physically moved.
                    // Only call restore() after a confirmed post-delivery return.
                    $inventory->release($item->quantity);
                }
            }

            return $this->updateOrderStatus($order->id, 'cancelled');
        });
    }

    /**
     * Confirm an order (deduct inventory).
     */
    public function confirmOrder(int $id): bool
    {
        $order = $this->getOrderById($id);

        if (!$order || !$order->isPending()) {
            return false;
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $inventory = $this->inventoryService->getInventoryByProduct(
                    $item->product_id,
                    $item->product_variant_id
                );

                if ($inventory) {
                    $inventory->deduct($item->quantity);
                }
            }

            return $this->updateOrderStatus($order->id, 'processing');
        });
    }

    /**
     * Generate unique order number.
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(uniqid());
        } while ($this->orderRepository->findByOrderNumber($orderNumber));

        return $orderNumber;
    }

    /**
     * Check if user can access order.
     */
    public function canAccessOrder(Order $order, int $userId, string $userRole): bool
    {
        if ($userRole === 'admin') {
            return true;
        }

        if ($userRole === 'customer' && $order->customer_id === $userId) {
            return true;
        }

        if ($userRole === 'vendor') {
            return $order->items()->whereHas('product', function ($query) use ($userId) {
                $query->where('vendor_id', $userId);
            })->exists();
        }

        return false;
    }
}
