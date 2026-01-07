<?php

namespace App\Utils;

use App\Models\OperationalExpense;
use App\Jobs\SendOperationalExpenseNotification;

class OperationalExpenseHelper
{
    /**
     * Dispatch notification for an expense
     */
    public static function dispatchNotification(OperationalExpense $expense): void
    {
        \App\Jobs\SendOperationalExpenseNotification::dispatch($expense);
    }

    /**
     * Mark expense as paid
     */
    public static function markAsPaid(OperationalExpense $expense): void
    {
        $expense->update(['status' => 'paid']);
    }

    /**
     * Mark expense as overdue
     */
    public static function markAsOverdue(OperationalExpense $expense): void
    {
        if ($expense->due_date->isPast() && $expense->status !== 'paid') {
            $expense->update(['status' => 'overdue']);
        }
    }

    /**
     * Get pending expenses count
     */
    public static function getPendingCount(): int
    {
        return OperationalExpense::where('status', 'pending')->count();
    }

    /**
     * Get overdue expenses count
     */
    public static function getOverdueCount(): int
    {
        return OperationalExpense::where('status', 'overdue')->count();
    }

    /**
     * Get total pending amount
     */
    public static function getTotalPendingAmount(): float
    {
        return (float) OperationalExpense::where('status', 'pending')
            ->sum('amount');
    }
}
