<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuspiciousActivityResource\Pages;
use App\Models\SuspiciousActivity;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SuspiciousActivityResource extends Resource
{
    protected static ?string $model = SuspiciousActivity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Suspicious Activity';

    protected static ?string $navigationBadgeColor = 'danger';

    public static function getNavigationBadge(): ?string
    {
        $count = SuspiciousActivity::where('reviewed', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Activity Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'username')
                            ->disabled()
                            ->label('User'),
                        Forms\Components\TextInput::make('type')
                            ->disabled(),
                        Forms\Components\TextInput::make('severity')
                            ->disabled(),
                        Forms\Components\Toggle::make('reviewed')
                            ->disabled(),
                    ]),
                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\KeyValue::make('details')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Review')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('reviewed_by')
                            ->relationship('reviewer', 'username')
                            ->disabled()
                            ->label('Reviewed By'),
                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->disabled(),
                        Forms\Components\Textarea::make('review_notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn($record) => $record->user_id ? UserResource::getUrl('view', ['record' => $record->user_id]) : null),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'multi_account'   => 'warning',
                        'win_streak'      => 'info',
                        'rapid_withdrawal' => 'danger',
                        'bot_behavior'    => 'danger',
                        'ip_change'       => 'gray',
                        'deposit_spike'   => 'warning',
                        'cashout_pattern' => 'info',
                        default           => 'gray',
                    }),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'low'      => 'gray',
                        'medium'   => 'warning',
                        'high'     => 'danger',
                        'critical' => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('reviewed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.username')
                    ->label('Reviewed By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'multi_account'   => 'Multi Account',
                        'win_streak'      => 'Win Streak',
                        'rapid_withdrawal' => 'Rapid Withdrawal',
                        'bot_behavior'    => 'Bot Behavior',
                        'ip_change'       => 'IP Change',
                        'deposit_spike'   => 'Deposit Spike',
                        'cashout_pattern' => 'Cashout Pattern',
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'low'      => 'Low',
                        'medium'   => 'Medium',
                        'high'     => 'High',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\TernaryFilter::make('reviewed')
                    ->label('Review Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_reviewed')
                    ->label('Mark Reviewed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => !$record->reviewed)
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Review Notes')
                            ->placeholder('Add notes about this activity...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markReviewed(
                            reviewerId: auth()->id(),
                            notes: $data['review_notes'] ?? null,
                        );
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Marked as reviewed'),
                Tables\Actions\Action::make('ban_user')
                    ->label('Ban User')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn($record) => $record->user && !$record->user->is_banned)
                    ->action(function ($record) {
                        $record->user->update(['is_banned' => true]);
                        $record->markReviewed(auth()->id(), 'User banned due to suspicious activity');

                        \App\Models\AuditLog::log(
                            action: 'user_banned',
                            resource: $record->user,
                            notes: "Banned via suspicious activity: {$record->type}",
                        );
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ban User')
                    ->modalDescription('Are you sure you want to ban this user? They will not be able to access the platform.')
                    ->successNotificationTitle('User banned'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_review')
                    ->label('Mark All Reviewed')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->markReviewed(auth()->id(), 'Bulk reviewed');
                        }
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Marked as reviewed'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuspiciousActivities::route('/'),
            'view'  => Pages\ViewSuspiciousActivity::route('/{record}'),
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
