<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'wallet';

    protected static ?string $title = 'Wallet';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('balance')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->default('NGN')
                    ->maxLength(10),

                Forms\Components\Toggle::make('is_locked')
                    ->label('Locked'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('balance')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean()
                    ->label('Locked'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
