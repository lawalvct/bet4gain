<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BetsRelationManager extends RelationManager
{
    protected static string $relationship = 'bets';

    protected static ?string $title = 'Bet History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gameRound.crash_point')
                    ->label('Crash @')
                    ->numeric(2)
                    ->suffix('x'),

                Tables\Columns\TextColumn::make('amount')
                    ->numeric(4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),

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

                Tables\Columns\IconColumn::make('is_auto')
                    ->boolean()
                    ->label('Auto'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(\App\Enums\BetStatus::class),

                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'COIN' => 'Coins',
                        'DEMO' => 'Demo',
                    ]),
            ]);
    }
}
