<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\AdminNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAdminPaymentReceived implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected AdminNotificationService $notificationService) {}

    public function handle(PaymentReceived $event): void
    {
        $this->notificationService->notifyAdminPaymentReceived($event->payment, $event->amountReceived);
    }
}
