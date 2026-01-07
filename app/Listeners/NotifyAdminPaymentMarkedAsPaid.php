<?php

namespace App\Listeners;

use App\Events\PaymentMarkedAsPaid;
use App\Services\AdminNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminPaymentMarkedAsPaid implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected AdminNotificationService $notificationService) {}

    public function handle(PaymentMarkedAsPaid $event): void
    {
        $this->notificationService->notifyAdminPaymentMarkedAsPaid($event->payment);
    }
}
