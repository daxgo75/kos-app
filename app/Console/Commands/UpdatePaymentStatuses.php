<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class UpdatePaymentStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing payment statuses to match new enum values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating payment statuses...');

        // Update 'unpaid' to 'pending'
        $unpaidCount = Payment::where('status', 'unpaid')->update(['status' => 'pending']);
        $this->info("Updated {$unpaidCount} payments from 'unpaid' to 'pending'");

        // Check for overdue payments
        $overdueCount = Payment::where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
        $this->info("Marked {$overdueCount} payments as 'overdue'");

        $this->info('Payment status update completed!');
    }
}
