<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * PaymentService - Menangani semua operasi pembayaran
 * 
 * Service ini menyediakan metode untuk:
 * - Membuat catatan pembayaran baru
 * - Menambah pembayaran (cicilan)
 * - Menandai pembayaran sebagai lunas
 * - Membuat laporan pembayaran
 * - Tracking status pembayaran
 */
class PaymentService
{
    /**
     * Buat catatan pembayaran baru untuk penyewa
     */
    public function createPayment(Tenant $tenant, ?Carbon $dueDate = null): Payment
    {
        $room = $tenant->room;
        
        return Payment::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'amount_due' => $room->monthly_rate,
            'amount_paid' => 0,
            'remaining_amount' => $room->monthly_rate,
            'due_date' => $dueDate ?? now()->addMonth()->day(25),
            'status' => 'unpaid',
        ]);
    }

    /**
     * Tambahkan pembayaran (cicilan) ke pembayaran yang ada
     */
    public function addPayment(Payment $payment, float $paidAmount, ?string $paymentMethod = null, ?string $notes = null): void
    {
        $payment->addPayment($paidAmount, $paymentMethod);
        
        if ($notes) {
            $payment->update(['notes' => $notes]);
        }
    }

    /**
     * Tandai pembayaran sebagai lunas
     */
    public function markAsPaid(Payment $payment, ?string $paymentMethod = null): void
    {
        $payment->markAsPaid($paymentMethod);
    }

    /**
     * Dapatkan laporan pembayaran berdasarkan status
     */
    public function getPaymentsByStatus(string $status = 'unpaid'): Collection
    {
        return Payment::where('status', $status)
            ->with(['tenant', 'room'])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Dapatkan pembayaran yang belum lunas untuk penyewa
     */
    public function getUnpaidPaymentsForTenant(Tenant $tenant): Collection
    {
        return $tenant->unpaidPayments()->get();
    }

    /**
     * Dapatkan pembayaran yang belum lunas untuk kamar
     */
    public function getUnpaidPaymentsForRoom(Room $room): Collection
    {
        return $room->unpaidPayments()->get();
    }

    /**
     * Dapatkan pembayaran yang telah overdue (jatuh tempo)
     */
    public function getOverduePayments(): Collection
    {
        return Payment::where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->with(['tenant', 'room'])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Dapatkan ringkasan pembayaran per kamar
     */
    public function getRoomSummary(Room $room): array
    {
        $payments = $room->payments;
        $unpaidPayments = $room->unpaidPayments()->get();

        return [
            'room_number' => $room->room_number,
            'monthly_rate' => $room->monthly_rate,
            'active_tenant' => $room->activeTenant(),
            'total_payments' => $payments->count(),
            'paid_payments' => $payments->where('status', 'paid')->count(),
            'unpaid_count' => $unpaidPayments->count(),
            'total_outstanding' => $unpaidPayments->sum('remaining_amount'),
            'unpaid_payments' => $unpaidPayments,
        ];
    }

    /**
     * Dapatkan ringkasan pembayaran per penyewa
     */
    public function getTenantSummary(Tenant $tenant): array
    {
        $unpaidPayments = $tenant->unpaidPayments()->get();
        $allPayments = $tenant->payments;

        return [
            'tenant_name' => $tenant->name,
            'room_number' => $tenant->room->room_number,
            'check_in_date' => $tenant->check_in_date,
            'total_months_occupied' => $tenant->check_in_date->diffInMonths(now()),
            'total_paid' => $tenant->getTotalPaidAmount(),
            'total_due' => $tenant->getTotalAmountDue(),
            'total_remaining' => $tenant->getTotalRemainingAmount(),
            'unpaid_count' => $unpaidPayments->count(),
            'has_unpaid' => $tenant->hasUnpaidPayments(),
            'unpaid_payments' => $unpaidPayments,
        ];
    }

    /**
     * Dapatkan laporan pembayaran keseluruhan
     */
    public function getOverallReport(): array
    {
        $totalPayments = Payment::count();
        $paidPayments = Payment::where('status', 'paid')->get();
        $unpaidPayments = Payment::where('status', '!=', 'paid')->get();
        $overduePayments = $this->getOverduePayments();

        return [
            'total_payments' => $totalPayments,
            'paid_count' => $paidPayments->count(),
            'unpaid_count' => $unpaidPayments->count(),
            'overdue_count' => $overduePayments->count(),
            'total_paid_amount' => $paidPayments->sum('amount_due'),
            'total_outstanding' => $unpaidPayments->sum('remaining_amount'),
            'total_due_amount' => Payment::sum('amount_due'),
            'payment_percentage' => $totalPayments > 0 
                ? round(($paidPayments->count() / $totalPayments) * 100, 2)
                : 0,
        ];
    }

    /**
     * Cek apakah penyewa memiliki pembayaran tertunggak
     */
    public function hasPendingPayments(Tenant $tenant): bool
    {
        return $tenant->hasUnpaidPayments();
    }

    /**
     * Cek apakah pembayaran sudah overdue
     */
    public function isPaymentOverdue(Payment $payment): bool
    {
        return $payment->isOverdue();
    }

    /**
     * Generate laporan CSV untuk pembayaran
     */
    public function generatePaymentReport(?string $status = null): Collection
    {
        $query = Payment::with(['tenant', 'room']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->map(function (Payment $payment) {
            return [
                'Penyewa' => $payment->tenant->name,
                'Kamar' => $payment->room->room_number,
                'Jumlah Tagihan' => $payment->amount_due,
                'Jumlah Dibayar' => $payment->amount_paid,
                'Sisa Pembayaran' => $payment->remaining_amount,
                'Tanggal Jatuh Tempo' => $payment->due_date->format('d-m-Y'),
                'Status' => $payment->status,
                'Telat' => $payment->isOverdue() ? 'Ya' : 'Tidak',
            ];
        });
    }
}
