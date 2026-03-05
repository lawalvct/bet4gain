<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IpRestrictionResource\Pages;
use App\Models\IpRestriction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class IpRestrictionResource extends Resource
{
    protected static ?string $model = IpRestriction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'IP Restrictions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('IP Restriction')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->required()
                            ->maxLength(45)
                            ->label('IP Address')
                            ->placeholder('e.g., 192.168.1.1')
                            ->rules(['ip']),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'blacklist' => 'Blacklist (Block)',
                                'whitelist' => 'Whitelist (Allow)',
                            ])
                            ->default('blacklist'),
                        Forms\Components\TextInput::make('reason')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('Leave empty for permanent restriction'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'blacklist' => 'danger',
                        'whitelist' => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->reason),
                Tables\Columns\TextColumn::make('creator.username')
                    ->label('Created By')
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('Never')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'blacklist' => 'Blacklisted',
                        'whitelist' => 'Whitelisted',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        \App\Models\AuditLog::log(
                            action: 'ip_restriction_deleted',
                            resource: $record,
                            notes: "Removed {$record->type}: {$record->ip_address}",
                        );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIpRestrictions::route('/'),
            'create' => Pages\CreateIpRestriction::route('/create'),
            'edit'   => Pages\EditIpRestriction::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
