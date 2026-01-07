<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Kamar Kos';

    protected static ?string $modelLabel = 'Kamar Kos';

    protected static ?string $pluralModelLabel = 'Kamar-Kamar Kos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kamar')
                    ->description('Data lengkap kamar kos')
                    ->schema([
                        Forms\Components\TextInput::make('room_number')
                            ->label('Nomor Kamar')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: Kamar 1, Kamar A1'),

                        Forms\Components\Select::make('room_type')
                            ->label('Tipe Kamar')
                            ->options([
                                'standard' => 'Standard',
                                'deluxe' => 'Deluxe',
                            ])
                            ->required()
                            ->default('standard'),

                        Forms\Components\TextInput::make('monthly_rate')
                            ->label('Tarif Per Bulan (Rp)')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->step(50000),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Kapasitas Penghuni')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Deskripsi')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Kamar')
                            ->options([
                                'available' => 'Tersedia',
                                'occupied' => 'Terisi',
                                'maintenance' => 'Perbaikan',
                            ])
                            ->required()
                            ->default('available'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi lengkap kamar (fasilitas, lokasi, dll)')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room_number')
                    ->label('Nomor Kamar')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'standard' => 'Standard',
                        'deluxe' => 'Deluxe',
                        default => $state,
                    })
                    ->badge()
                    ->colors([
                        'gray' => 'standard',
                        'blue' => 'deluxe',
                    ]),

                Tables\Columns\TextColumn::make('monthly_rate')
                    ->label('Tarif/Bulan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'occupied' => 'Terisi',
                        'maintenance' => 'Perbaikan',
                        default => $state,
                    })
                    ->badge()
                    ->colors([
                        'success' => 'available',
                        'primary' => 'occupied',
                        'warning' => 'maintenance',
                    ]),

                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Penghuni')
                    ->counts('tenants')
                    ->badge(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'available' => 'Tersedia',
                        'occupied' => 'Terisi',
                        'maintenance' => 'Perbaikan',
                    ]),

                Tables\Filters\SelectFilter::make('room_type')
                    ->label('Filter Tipe')
                    ->options([
                        'standard' => 'Standard',
                        'deluxe' => 'Deluxe',
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'view' => Pages\ViewRoom::route('/{record}'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
