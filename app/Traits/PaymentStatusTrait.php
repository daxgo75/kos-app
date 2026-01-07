<?php

namespace App\Traits;

use Carbon\Carbon;

/**
 * PaymentStatusTrait
 * 
 * Trait untuk menangani status dan perhitungan pembayaran
 * Bisa digunakan di multiple models
 */
trait PaymentStatusTrait
{
    /**
     * Dapatkan label status dalam format readable
     */
    public function getStatusLabel(): string
    {
        return match ($this->status ?? 'pending') {
            'paid' => '✓ LUNAS',
            'partial' => '⚠️ CICILAN',
            'pending' => '⏳ MENUNGGU',
            'overdue' => '❌ TERLAMBAT',
            default => $this->status,
        };
    }

    /**
     * Dapatkan warna badge status
     */
    public function getStatusColor(): string
    {
        return match ($this->status ?? 'pending') {
            'paid' => 'success',
            'partial' => 'warning',
            'pending' => 'gray',
            'overdue' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Dapatkan persentase pembayaran
     */
    public function getPaymentPercentage(): float
    {
        if ($this->amount_due == 0) {
            return 0;
        }

        return round(($this->amount_paid / $this->amount_due) * 100, 2);
    }

    /**
     * Cek apakah pembayaran masih dalam batas waktu
     */
    public function isWithinDueDate(): bool
    {
        return $this->status !== 'paid' && $this->due_date >= Carbon::today();
    }

    /**
     * Hitung berapa hari lagi jatuh tempo
     */
    public function getDaysUntilDue(): int
    {
        return $this->due_date->diffInDays(Carbon::today(), false);
    }

    /**
     * Hitung berapa hari sudah telat
     */
    public function getDaysOverdue(): int
    {
        if ($this->isPaid() || !$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(Carbon::today());
    }
}
