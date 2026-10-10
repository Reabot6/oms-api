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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = $this->order->load(['customer', 'items.product', 'items.productVariant']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
        ]);

        $filename = 'invoices/' . $this->order->order_number . '.pdf';

        Storage::put($filename, $pdf->output());
    }
}
