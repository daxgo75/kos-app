<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPaymentReceived implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        Log::info('Payment received', [
            'payment_id' => $event->payment->id,
            'tenant_id' => $event->payment->tenant_id,
            'amount_received' => $event->amountReceived,
            'new_status' => $event->payment->status,
            'remaining_amount' => $event->payment->remaining_amount,
            'timestamp' => now(),
        ]);
    }
}
