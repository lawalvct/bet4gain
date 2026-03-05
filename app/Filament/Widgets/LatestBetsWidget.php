<?php

namespace App\Filament\Widgets;

use App\Models\Bet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBetsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Bets';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Bet::query()
                    ->with(['user', 'gameRound'])
                    ->latest()
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.username')
                    ->label('Player')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->numeric(4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('gameRound.crash_point')
                    ->label('Crash @')
                    ->numeric(2)
                    ->suffix('x'),

                Tables\Columns\TextColumn::make('cashed_out_at')
                    ->label('Cashout @')
                    ->numeric(2)
                    ->suffix('x')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('payout')
                    ->numeric(4)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 15, 25]);
    }
}
