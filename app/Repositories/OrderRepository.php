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

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Get all orders.
     */
    public function all(): Collection
    {
        return Order::get();
    }

    /**
     * Paginate orders.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('customer')->latest()->paginate($perPage);
    }

    /**
     * Find order by ID.
     */
    public function find(int $id): ?Order
    {
        return Order::find($id);
    }

    /**
     * Find order by order number.
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::where('order_number', $orderNumber)->first();
    }

    /**
     * Create a new order.
     */
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    /**
     * Update an order.
     */
    public function update(int $id, array $data): bool
    {
        return Order::where('id', $id)->update($data);
    }

    /**
     * Delete an order.
     */
    public function delete(int $id): bool
    {
        return Order::where('id', $id)->delete() > 0;
    }

    /**
     * Get orders by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('customer')->where('customer_id', $customerId)->latest()->paginate($perPage);
    }

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('customer')->where('status', $status)->latest()->paginate($perPage);
    }

    /**
     * Get orders by vendor (filtered items loaded by Resource when needed).
     */
    public function getByVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('customer')
            ->whereHas('items.product', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->latest()
            ->paginate($perPage);
    }
}
