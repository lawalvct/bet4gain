<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Audit Log';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Audit Entry')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'username')
                            ->disabled(),

                        Forms\Components\TextInput::make('action')
                            ->disabled(),

                        Forms\Components\TextInput::make('resource_type')
                            ->label('Resource Type')
                            ->disabled(),

                        Forms\Components\TextInput::make('resource_id')
                            ->label('Resource ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Changes')
                    ->columns(2)
                    ->schema([
                        Forms\Components\KeyValue::make('old_values')
                            ->label('Old Values')
                            ->disabled(),

                        Forms\Components\KeyValue::make('new_values')
                            ->label('New Values')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.username')
                    ->label('Admin')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->searchable()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'created') => 'success',
                        str_contains($state, 'updated') => 'info',
                        str_contains($state, 'deleted') => 'danger',
                        str_contains($state, 'banned') => 'danger',
                        str_contains($state, 'unbanned') => 'success',
                        str_contains($state, 'approved') => 'success',
                        str_contains($state, 'rejected') => 'danger',
                        str_contains($state, 'balance') => 'warning',
                        str_contains($state, 'setting') => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('resource_type')
                    ->label('Resource')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resource_id')
                    ->label('ID')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(fn () => AuditLog::distinct()
                        ->pluck('action', 'action')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Admin')
                    ->relationship('user', 'username'),

                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->startOfWeek())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
