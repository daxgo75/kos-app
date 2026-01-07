<?php

namespace App\Listeners;

use App\Events\PaymentMarkedAsPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPaymentPaid implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentMarkedAsPaid $event): void
    {
        Log::info('Payment marked as paid', [
            'payment_id' => $event->payment->id,
            'tenant_id' => $event->payment->tenant_id,
            'amount' => $event->payment->amount_due,
            'timestamp' => now(),
        ]);
    }
}
