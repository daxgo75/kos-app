<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 5 rooms
        $rooms = Room::factory()
            ->count(5)
            ->create();

        // Update rooms dengan nomor dan status
        foreach ($rooms as $index => $room) {
            $room->update([
                'room_number' => 'Kamar ' . ($index + 1),
                'status' => $index < 3 ? 'occupied' : 'available',
            ]);
        }

        // Create tenants for occupied rooms
        $tenants = collect();
        for ($i = 0; $i < 3; $i++) {
            $tenant = Tenant::factory()->create([
                'room_id' => $rooms[$i]->id,
                'status' => 'active',
            ]);
            $tenants->push($tenant);
        }

        // Create payments for each tenant (3 months history)
        $tenants->each(function (Tenant $tenant) {
            $room = $tenant->room;
            
            // Create 3 payment records (past 3 months)
            for ($i = 3; $i >= 1; $i--) {
                $dueDate = now()->subMonths($i)->startOfMonth()->addDay(25);
                
                if ($i > 1) {
                    // Past months are paid
                    Payment::create([
                        'tenant_id' => $tenant->id,
                        'room_id' => $room->id,
                        'amount_due' => $room->monthly_rate,
                        'amount_paid' => $room->monthly_rate,
                        'remaining_amount' => 0,
                        'due_date' => $dueDate,
                        'paid_date' => $dueDate->addDays(5),
                        'status' => 'paid',
                        'payment_method' => 'cash',
                        'notes' => 'Pembayaran bulan ' . $dueDate->format('F Y'),
                    ]);
                } else {
                    // Current month - mixed status for demo
                    if ($tenant->id === 1) {
                        // Fully paid
                        Payment::create([
                            'tenant_id' => $tenant->id,
                            'room_id' => $room->id,
                            'amount_due' => $room->monthly_rate,
                            'amount_paid' => $room->monthly_rate,
                            'remaining_amount' => 0,
                            'due_date' => $dueDate,
                            'paid_date' => now(),
                            'status' => 'paid',
                            'payment_method' => 'bank_transfer',
                            'notes' => 'Pembayaran bulan ' . $dueDate->format('F Y'),
                        ]);
                    } elseif ($tenant->id === 2) {
                        // Partial payment
                        $paidAmount = $room->monthly_rate * 0.5;
                        Payment::create([
                            'tenant_id' => $tenant->id,
                            'room_id' => $room->id,
                            'amount_due' => $room->monthly_rate,
                            'amount_paid' => $paidAmount,
                            'remaining_amount' => $room->monthly_rate - $paidAmount,
                            'due_date' => $dueDate,
                            'paid_date' => null,
                            'status' => 'partial',
                            'payment_method' => 'cash',
                            'notes' => 'Pembayaran cicilan bulan ' . $dueDate->format('F Y'),
                        ]);
                    } else {
                        // Unpaid
                        Payment::create([
                            'tenant_id' => $tenant->id,
                            'room_id' => $room->id,
                            'amount_due' => $room->monthly_rate,
                            'amount_paid' => 0,
                            'remaining_amount' => $room->monthly_rate,
                            'due_date' => $dueDate,
                            'paid_date' => null,
                            'status' => 'unpaid',
                            'payment_method' => null,
                            'notes' => 'Belum ada pembayaran bulan ' . $dueDate->format('F Y'),
                        ]);
                    }
                }
            }
        });
    }
}
