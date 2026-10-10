<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $type
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customer = $this->order->customer;

        $subject = match ($this->type) {
            'created' => 'Order Confirmation - ' . $this->order->order_number,
            'status_changed' => 'Order Status Update - ' . $this->order->order_number,
            default => 'Order Update - ' . $this->order->order_number,
        };

        $data = [
            'order' => $this->order->load(['customer', 'items.product', 'items.productVariant']),
            'customer' => $customer,
            'type' => $this->type,
        ];

        Mail::send('emails.order', $data, function ($message) use ($customer, $subject) {
            $message->to($customer->email, $customer->name)
                ->subject($subject);
        });
    }
}
