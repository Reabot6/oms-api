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

use App\Events\OrderCreated;
use App\Jobs\GenerateInvoicePdfJob;
use App\Jobs\SendOrderEmailJob;

class HandleOrderCreated
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        // Send order confirmation email
        SendOrderEmailJob::dispatch($event->order, 'created');

        // Generate invoice PDF
        GenerateInvoicePdfJob::dispatch($event->order);
    }
}
