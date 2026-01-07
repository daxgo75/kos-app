<?php

namespace App\Jobs;

use App\Models\OperationalExpense;
use App\Services\AdminNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOperationalExpenseNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected OperationalExpense $expense,
    ) {}

    public function handle(AdminNotificationService $notificationService): void
    {
        if (!$this->expense->notification_enabled) {
            Log::info('Notification disabled for expense', ['id' => $this->expense->id]);
            return;
        }

        if ($this->expense->wasNotifiedToday()) {
            Log::info('Notification already sent today', ['id' => $this->expense->id]);
            return;
        }

        // Send notification to all admin users
        $notificationService->notifyAdminOperationalExpenseDue($this->expense);

        // Mark as notified
        $this->expense->markAsNotified('admin', true);

        Log::info('Operational expense notification sent to admins', [
            'expense_id' => $this->expense->id,
            'expense_name' => $this->expense->name,
        ]);
    }
}
