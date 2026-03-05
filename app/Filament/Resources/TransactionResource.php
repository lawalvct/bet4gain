<?php

namespace App\Filament\Resources;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\AuditLog;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Users & Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'gateway_reference'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'username')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options(TransactionType::class)
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('currency')
                            ->default('NGN')
                            ->required()
                            ->maxLength(10),

                        Forms\Components\Select::make('status')
                            ->options(TransactionStatus::class)
                            ->required()
                            ->default(TransactionStatus::Pending),

                        Forms\Components\TextInput::make('gateway')
                            ->maxLength(50)
                            ->placeholder('paystack, nomba, manual'),

                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('gateway_reference')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull(),
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
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state): string => $state->label())
                    ->color(fn (TransactionType $state): string => $state->isCredit() ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('amount')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TransactionStatus $state): string => $state->label())
                    ->color(fn (TransactionStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('gateway')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->limit(15)
                    ->tooltip(fn (Transaction $record): string => $record->reference ?? '')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(TransactionType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->options(TransactionStatus::class),

                Tables\Filters\SelectFilter::make('gateway')
                    ->options(fn () => Transaction::whereNotNull('gateway')
                        ->distinct()
                        ->pluck('gateway', 'gateway')
                        ->toArray()),

                Tables\Filters\Filter::make('pending_withdrawals')
                    ->label('Pending Withdrawals')
                    ->query(fn ($query) => $query
                        ->where('type', TransactionType::Withdrawal)
                        ->where('status', TransactionStatus::Pending)),

                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Withdrawal')
                    ->modalDescription('This will mark the transaction as completed. Make sure the payment has been processed.')
                    ->visible(fn (Transaction $record): bool =>
                        $record->type === TransactionType::Withdrawal &&
                        $record->status === TransactionStatus::Pending
                    )
                    ->action(function (Transaction $record): void {
                        $old = $record->status;
                        $record->update(['status' => TransactionStatus::Completed]);
                        AuditLog::log('transaction.approved', $record,
                            ['status' => $old->value],
                            ['status' => 'completed'],
                        );
                        Notification::make()->title('Withdrawal approved.')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Withdrawal')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (Transaction $record): bool =>
                        $record->type === TransactionType::Withdrawal &&
                        $record->status === TransactionStatus::Pending
                    )
                    ->action(function (Transaction $record, array $data): void {
                        $old = $record->status;
                        $record->update([
                            'status' => TransactionStatus::Failed,
                            'description' => 'Rejected: ' . $data['reason'],
                        ]);

                        // Refund wallet balance
                        if ($wallet = $record->user?->wallet) {
                            $wallet->increment('balance', $record->amount);
                        }

                        AuditLog::log('transaction.rejected', $record,
                            ['status' => $old->value],
                            ['status' => 'failed'],
                            "Reason: {$data['reason']}"
                        );
                        Notification::make()->title('Withdrawal rejected. Balance refunded.')->warning()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
