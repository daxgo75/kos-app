<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\OperationalExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AdminNotificationService
{
    public function notifyAdminPaymentMarkedAsPaid($payment): void
    {
        $adminUsers = $this->getAdminUsers();

        foreach ($adminUsers as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'payment_id' => $payment->id,
                'type' => 'payment_marked_paid',
                'title' => 'Pembayaran Lunas',
                'message' => sprintf(
                    '%s (Kamar %s) telah menyelesaikan pembayaran sebesar Rp %s',
                    $payment->tenant->name,
                    $payment->room->room_number,
                    number_format($payment->amount_due, 0, ',', '.')
                ),
                'data' => [
                    'tenant_id' => $payment->tenant_id,
                    'room_id' => $payment->room_id,
                    'amount' => $payment->amount_due,
                    'payment_method' => $payment->payment_method,
                ],
            ]);
        }
    }

    public function notifyAdminPaymentReceived($payment, float $amountReceived): void
    {
        $adminUsers = $this->getAdminUsers();
        $status = $payment->status;
        $statusLabel = $this->getStatusLabel($status);

        foreach ($adminUsers as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'payment_id' => $payment->id,
                'type' => 'payment_received',
                'title' => "Pembayaran Diterima - $statusLabel",
                'message' => sprintf(
                    '%s (Kamar %s) telah membayar Rp %s. Status: %s',
                    $payment->tenant->name,
                    $payment->room->room_number,
                    number_format($amountReceived, 0, ',', '.'),
                    $statusLabel
                ),
                'data' => [
                    'tenant_id' => $payment->tenant_id,
                    'room_id' => $payment->room_id,
                    'amount_received' => $amountReceived,
                    'remaining_amount' => $payment->remaining_amount,
                    'status' => $status,
                ],
            ]);
        }
    }

    /**
     * Notify admin about operational expense due date
     */
    public function notifyAdminOperationalExpenseDue(OperationalExpense $expense): void
    {
        $adminUsers = $this->getAdminUsers();
        $dueDate = $expense->due_date->format('d/m/Y');
        $amountFormatted = number_format($expense->amount, 0, ',', '.');

        foreach ($adminUsers as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'operational_expense_id' => $expense->id,
                'type' => 'operational_expense_reminder',
                'title' => "Pengingat Tagihan Operasional: {$expense->name}",
                'message' => sprintf(
                    'Tagihan operasional "%s" sebesar Rp %s akan jatuh tempo pada %s. Mohon segera lakukan pembayaran.',
                    $expense->name,
                    $amountFormatted,
                    $dueDate
                ),
                'data' => [
                    'expense_id' => $expense->id,
                    'expense_name' => $expense->name,
                    'amount' => $expense->amount,
                    'due_date' => $dueDate,
                    'description' => $expense->description,
                ],
            ]);
        }
    }

    public function getAdminNotifications(User $admin, ?string $type = null): Collection
    {
        $query = AdminNotification::where('user_id', $admin->id);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->recent()->get();
    }

    public function getUnreadCount(User $admin): int
    {
        return AdminNotification::where('user_id', $admin->id)
            ->unread()
            ->count();
    }

    public function markAsRead(AdminNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(User $admin): void
    {
        AdminNotification::where('user_id', $admin->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function deleteNotification(AdminNotification $notification): void
    {
        $notification->delete();
    }

    public function deleteOldNotifications(int $days = 30): int
    {
        return AdminNotification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    private function getAdminUsers(): Collection
    {
        return User::where('role', 'admin')->get();
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            'pending' => 'Tertunda',
            'overdue' => 'Jatuh Tempo',
            default => $status,
        };
    }
}
