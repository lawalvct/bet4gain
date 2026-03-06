<?php

namespace App\Filament\Resources;

use App\Enums\AdPlacement;
use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Advertisement Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('url')
                            ->url()
                            ->required()
                            ->maxLength(2048),

                        Forms\Components\Select::make('placement')
                            ->options(AdPlacement::class)
                            ->required(),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher = shown first'),

                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->disk('public')
                            ->directory('advertisements')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Scheduling')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->helperText('Leave blank for immediate'),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date')
                            ->helperText('Leave blank for no end date'),
                    ]),

                Forms\Components\Section::make('Performance')
                    ->columns(2)
                    ->visible(fn (string $operation): bool => $operation !== 'create')
                    ->schema([
                        Forms\Components\TextInput::make('impressions')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('clicks')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Placeholder::make('ctr')
                            ->label('Click-Through Rate')
                            ->content(function (?Advertisement $record): string {
                                if (!$record || $record->impressions === 0) return '0%';
                                return number_format(($record->clicks / $record->impressions) * 100, 2) . '%';
                            }),
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

                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->height(40),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('placement')
                    ->badge()
                    ->formatStateUsing(fn (AdPlacement $state): string => $state->label())
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('impressions')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('clicks')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime('M d, Y')
                    ->placeholder('Immediate')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime('M d, Y')
                    ->placeholder('No end')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('placement')
                    ->options(AdPlacement::class),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Advertisement $record): void {
                        AuditLog::log('advertisement.deleted', $record, ['title' => $record->title]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
