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
use App\Services\OrderService;

class CancelOrderAction
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    /**
     * Execute the action.
     */
    public function execute(int $orderId): bool
    {
        $order = $this->orderService->getOrderById($orderId);

        if (!$order) {
            throw new \Exception('Order not found');
        }

        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in current status: ' . $order->status);
        }

        return $this->orderService->cancelOrder($orderId);
    }
}
