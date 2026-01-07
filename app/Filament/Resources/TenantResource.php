<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Penyewa';

    protected static ?string $modelLabel = 'Penyewa';

    protected static ?string $pluralModelLabel = 'Daftar Penyewa';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penyewa')
                    ->description('Data pribadi dan kontak penyewa')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->required(),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->relationship('room', 'room_number')
                            ->required()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tanggal Penghunian')
                    ->description('Informasi check-in dan check-out')
                    ->schema([
                        Forms\Components\DatePicker::make('check_in_date')
                            ->label('Tanggal Check-in')
                            ->required(),

                        Forms\Components\DatePicker::make('check_out_date')
                            ->label('Tanggal Check-out')
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'moved_out' => 'Sudah Keluar',
                            ])
                            ->required()
                            ->default('active'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->description('Alamat dan catatan lainnya')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Asal')
                            ->placeholder('Alamat lengkap penyewa')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan tentang penyewa')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.room_number')
                    ->label('Kamar')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Check-in')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'moved_out' => 'Sudah Keluar',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'gray' => 'moved_out',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'moved_out' => 'Sudah Keluar',
                    ]),

                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Filter Kamar')
                    ->relationship('room', 'room_number'),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
