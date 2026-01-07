<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPayments = Payment::count();
        $paidPayments = Payment::where('status', 'paid')->count();
        $unpaidPayments = Payment::where('status', '!=', 'paid')->count();
        $overduePayments = Payment::where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $totalDue = Payment::sum('amount_due');
        $totalPaid = Payment::where('status', 'paid')->sum('amount_due');
        $totalOutstanding = Payment::where('status', '!=', 'paid')->sum('remaining_amount');

        return [
            Stat::make('Total Pembayaran', $totalPayments)
                ->description('Jumlah catatan pembayaran')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Sudah Lunas', $paidPayments)
                ->description(round(($paidPayments / max($totalPayments, 1)) * 100, 1) . '% selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Belum Dibayar', $unpaidPayments)
                ->description($overduePayments . ' yang telah telat')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('Total Terkumpul', 'Rp ' . number_format($totalPaid, 0, ',', '.'))
                ->description('Dari Rp ' . number_format($totalDue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Total Tunggakan', 'Rp ' . number_format($totalOutstanding, 0, ',', '.'))
                ->description($unpaidPayments . ' pembayaran menunggu')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Telat Bayar', $overduePayments)
                ->description('Pembayaran yang sudah overdue')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
