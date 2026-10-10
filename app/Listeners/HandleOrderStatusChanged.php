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

use App\Events\OrderStatusChanged;
use App\Jobs\SendOrderEmailJob;

class HandleOrderStatusChanged
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Send status update email
        SendOrderEmailJob::dispatch($event->order, 'status_changed');
    }
}
