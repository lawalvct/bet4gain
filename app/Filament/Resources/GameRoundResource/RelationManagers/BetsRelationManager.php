<?php

namespace App\Filament\Resources\GameRoundResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BetsRelationManager extends RelationManager
{
    protected static string $relationship = 'bets';

    protected static ?string $title = 'Bets in Round';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.username')
                    ->label('Player')
                    ->searchable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user_id)),

                Tables\Columns\TextColumn::make('amount')
                    ->numeric(4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency'),

                Tables\Columns\TextColumn::make('auto_cashout_at')
                    ->label('Auto Cashout')
                    ->numeric(2)
                    ->suffix('x')
                    ->placeholder('-'),

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
            ])
            ->defaultSort('amount', 'desc');
    }
}
