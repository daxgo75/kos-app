<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pembayaran')
                    ->description('Detail pembayaran kos')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Penyewa')
                            ->relationship('tenant', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->relationship('room', 'room_number')
                            ->required()
                            ->preload()
                            ->searchable(),

                        Forms\Components\TextInput::make('amount_due')
                            ->label('Jumlah Tagihan (Rp)')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->step(1000)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $amountDue = $state ?? 0;
                                $amountPaid = $get('amount_paid') ?? 0;
                                $set('remaining_amount', max(0, $amountDue - $amountPaid));
                            }),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Jumlah Dibayar (Rp)')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->step(1000)
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $amountDue = $get('amount_due') ?? 0;
                                $amountPaid = $state ?? 0;
                                $set('remaining_amount', max(0, $amountDue - $amountPaid));
                            }),

                        Forms\Components\TextInput::make('remaining_amount')
                            ->label('Sisa Tagihan (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(1000)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tanggal Jatuh Tempo')
                            ->required(),

                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Tanggal Pembayaran'),

                        Forms\Components\Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'pending' => 'Menunggu',
                                'paid' => 'Lunas',
                                'overdue' => 'Terlambat',
                                'partial' => 'Sebagian',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer',
                                'ewallet' => 'E-Wallet',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan tentang pembayaran')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->label('Tagihan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'partial' => 'Sebagian',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'primary' => 'partial',
                    ]),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'ewallet' => 'E-Wallet',
                        default => $state,
                    })
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'partial' => 'Sebagian',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Filter Metode')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'ewallet' => 'E-Wallet',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
