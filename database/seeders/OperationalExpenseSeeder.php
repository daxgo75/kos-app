<?php

namespace Database\Seeders;

use App\Models\OperationalExpense;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OperationalExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $expenses = [
            [
                'name' => 'Pembayaran Air',
                'description' => 'Tagihan air bulanan untuk seluruh kos',
                'amount' => 500000,
                'due_date' => Carbon::now()->addDays(5),
                'notification_enabled' => true,
                'notification_time' => '09:00',
                'status' => 'pending',
            ],
            [
                'name' => 'Pembayaran Listrik',
                'description' => 'Tagihan listrik bulanan',
                'amount' => 1500000,
                'due_date' => Carbon::now()->addDays(10),
                'notification_enabled' => true,
                'notification_time' => '10:00',
                'status' => 'pending',
            ],
            [
                'name' => 'Pembayaran Internet',
                'description' => 'Biaya internet untuk seluruh kos',
                'amount' => 300000,
                'due_date' => Carbon::now()->addDays(15),
                'notification_enabled' => true,
                'notification_time' => '09:30',
                'status' => 'pending',
            ],
            [
                'name' => 'Pemeliharaan Ruang Umum',
                'description' => 'Biaya pembersihan dan pemeliharaan ruang umum',
                'amount' => 400000,
                'due_date' => Carbon::now()->addDays(20),
                'notification_enabled' => true,
                'notification_time' => '11:00',
                'status' => 'pending',
            ],
            [
                'name' => 'Asuransi Bangunan',
                'description' => 'Asuransi untuk bangunan kos',
                'amount' => 750000,
                'due_date' => Carbon::now()->addDays(25),
                'notification_enabled' => true,
                'notification_time' => '08:00',
                'status' => 'pending',
            ],
        ];

        foreach ($expenses as $expense) {
            OperationalExpense::create($expense);
        }
    }
}
