<?php

namespace App\Filament\Resources;

use App\Enums\GameRoundStatus;
use App\Filament\Resources\GameRoundResource\Pages;
use App\Filament\Resources\GameRoundResource\RelationManagers;
use App\Models\GameRound;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class GameRoundResource extends Resource
{
    protected static ?string $model = GameRound::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string|\UnitEnum|null $navigationGroup = 'Game Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'round_hash';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Round Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('round_hash')
                            ->label('Round Hash')
                            ->disabled(),

                        Forms\Components\TextInput::make('crash_point')
                            ->label('Crash Point')
                            ->numeric()
                            ->suffix('x')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options(GameRoundStatus::class)
                            ->disabled(),

                        Forms\Components\TextInput::make('nonce')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('duration_ms')
                            ->label('Duration (ms)')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('crashed_at')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Seeds (Provably Fair)')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('server_seed')
                            ->disabled(),

                        Forms\Components\TextInput::make('client_seed')
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

                Tables\Columns\TextColumn::make('round_hash')
                    ->label('Hash')
                    ->limit(12)
                    ->tooltip(fn (GameRound $record): string => $record->round_hash ?? '')
                    ->searchable(),

                Tables\Columns\TextColumn::make('crash_point')
                    ->label('Crash @')
                    ->numeric(2)
                    ->suffix('x')
                    ->sortable()
                    ->color(fn (GameRound $record): string => match (true) {
                        $record->crash_point >= 10 => 'danger',
                        $record->crash_point >= 2 => 'success',
                        $record->crash_point >= 1.5 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (GameRoundStatus $state): string => $state->label())
                    ->color(fn (GameRoundStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('bets_count')
                    ->label('Bets')
                    ->counts('bets')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bets_sum_amount')
                    ->label('Total Wagered')
                    ->sum('bets', 'amount')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('bets_sum_payout')
                    ->label('Total Payout')
                    ->sum('bets', 'payout')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1000, 1) . 's' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime('M d, H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(GameRoundStatus::class),

                Tables\Filters\Filter::make('high_crash')
                    ->label('High Crash (10x+)')
                    ->query(fn ($query) => $query->where('crash_point', '>=', 10)),

                Tables\Filters\Filter::make('low_crash')
                    ->label('Low Crash (<1.5x)')
                    ->query(fn ($query) => $query->where('crash_point', '<', 1.5)),

                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameRounds::route('/'),
            'view' => Pages\ViewGameRound::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
