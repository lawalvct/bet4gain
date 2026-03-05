<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Users & Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'username', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->options(UserRole::class)
                            ->required()
                            ->default(UserRole::User),

                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Account Status')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_banned')
                            ->label('Banned')
                            ->helperText('Block user from accessing the platform'),

                        Forms\Components\Toggle::make('is_guest')
                            ->label('Guest Account')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('muted_until')
                            ->label('Muted Until')
                            ->helperText('Prevent user from chatting until this date'),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn (User $record): string => $record->avatar_url),

                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::Moderator => 'warning',
                        UserRole::User => 'gray',
                    }),

                Tables\Columns\TextColumn::make('coinBalance.balance')
                    ->label('Coins')
                    ->numeric(2)
                    ->sortable()
                    ->placeholder('0.00'),

                Tables\Columns\TextColumn::make('wallet.balance')
                    ->label('Wallet')
                    ->numeric(2)
                    ->sortable()
                    ->placeholder('0.00'),

                Tables\Columns\IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(UserRole::class),

                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Banned Status'),

                Tables\Filters\TernaryFilter::make('is_guest')
                    ->label('Guest Users'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('ban')
                    ->label('Ban')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ban User')
                    ->modalDescription('Are you sure you want to ban this user? They will be unable to access the platform.')
                    ->visible(fn (User $record): bool => !$record->is_banned && !$record->isAdmin())
                    ->action(function (User $record): void {
                        $record->update(['is_banned' => true]);
                        AuditLog::log('user.banned', $record, null, ['is_banned' => true]);
                        Notification::make()->title('User banned successfully.')->success()->send();
                    }),

                Tables\Actions\Action::make('unban')
                    ->label('Unban')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->is_banned)
                    ->action(function (User $record): void {
                        $record->update(['is_banned' => false]);
                        AuditLog::log('user.unbanned', $record, ['is_banned' => true], ['is_banned' => false]);
                        Notification::make()->title('User unbanned successfully.')->success()->send();
                    }),

                Tables\Actions\Action::make('adjustBalance')
                    ->label('Adjust Balance')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('wallet_type')
                            ->options([
                                'coins' => 'Coins',
                                'wallet' => 'Wallet (Cash)',
                            ])
                            ->required()
                            ->default('coins'),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->helperText('Use positive for credit, negative for debit'),

                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (User $record, array $data): void {
                        $amount = (float) $data['amount'];
                        $type = $data['wallet_type'];

                        if ($type === 'coins') {
                            $coinBalance = $record->coinBalance;
                            if ($coinBalance) {
                                $oldBalance = $coinBalance->balance;
                                $coinBalance->increment('balance', $amount);
                            } else {
                                $oldBalance = 0;
                                $record->coinBalance()->create(['balance' => max(0, $amount), 'demo_balance' => 10000]);
                            }
                            AuditLog::log('balance.adjusted', $record,
                                ['coin_balance' => $oldBalance],
                                ['coin_balance' => $oldBalance + $amount],
                                "Reason: {$data['reason']}"
                            );
                        } else {
                            $wallet = $record->wallet;
                            if ($wallet) {
                                $oldBalance = $wallet->balance;
                                $wallet->increment('balance', $amount);
                            } else {
                                $oldBalance = 0;
                                $record->wallet()->create(['balance' => max(0, $amount), 'currency' => 'NGN']);
                            }
                            AuditLog::log('balance.adjusted', $record,
                                ['wallet_balance' => $oldBalance],
                                ['wallet_balance' => $oldBalance + $amount],
                                "Reason: {$data['reason']}"
                            );
                        }

                        Notification::make()
                            ->title('Balance adjusted successfully.')
                            ->body("Adjusted {$type} by {$amount}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('banSelected')
                        ->label('Ban Selected')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function (User $user) {
                                if (!$user->isAdmin()) {
                                    $user->update(['is_banned' => true]);
                                    AuditLog::log('user.banned', $user, null, ['is_banned' => true], 'Bulk ban');
                                }
                            });
                            Notification::make()->title('Selected users banned.')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WalletRelationManager::class,
            RelationManagers\BetsRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
