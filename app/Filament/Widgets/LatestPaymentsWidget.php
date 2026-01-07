<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['tenant', 'room'])
                    ->where('status', '!=', 'paid')
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Penyewa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.room_number')
                    ->label('Kamar')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_due')
                    ->label('Jumlah Tagihan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Pembayaran')
                    ->money('IDR')
                    ->color(fn (Payment $record): string => match (true) {
                        $record->remaining_amount <= 0 => 'success',
                        $record->remaining_amount > 0 && $record->amount_paid > 0 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Menunggu',
                        'partial' => '⚠️ Cicilan',
                        'paid' => '✓ Lunas',
                        'overdue' => '❌ Terlambat',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Telat')
                    ->getStateUsing(fn (Payment $record) => $record->isOverdue())
                    ->boolean()
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-exclamation-triangle' : '')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
            ]);
    }
}
