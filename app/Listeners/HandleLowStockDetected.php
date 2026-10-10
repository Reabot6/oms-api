<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Jobs\CheckLowStockJob;
use Illuminate\Support\Facades\Log;

class HandleLowStockDetected
{
    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        Log::warning('Low stock detected', [
            'product_id' => $event->inventory->product_id,
            'variant_id' => $event->inventory->product_variant_id,
            'available_quantity' => $event->inventory->available_quantity,
            'threshold' => $event->inventory->low_stock_threshold,
        ]);

        // You can dispatch additional jobs here like:
        // - Send notification to admins/vendors
        // - Auto-reorder if enabled
        // - Update analytics
    }
}
