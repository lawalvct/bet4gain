<?php

namespace App\Filament\Resources;

use App\Enums\ChatMessageType;
use App\Filament\Resources\ChatMessageResource\Pages;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ChatMessageResource extends Resource
{
    protected static ?string $model = ChatMessage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Chat Moderation';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Message Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'username')
                            ->disabled(),

                        Forms\Components\Select::make('type')
                            ->options(ChatMessageType::class)
                            ->disabled(),

                        Forms\Components\Textarea::make('message')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_deleted')
                            ->label('Deleted'),

                        Forms\Components\Select::make('deleted_by')
                            ->relationship('deletedByUser', 'username')
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

                Tables\Columns\TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn (ChatMessage $record): string => $record->message ?? ''),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ChatMessageType $state): string => $state->label())
                    ->color(fn (ChatMessageType $state): string => match ($state) {
                        ChatMessageType::System => 'warning',
                        ChatMessageType::Gif => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_deleted')
                    ->boolean()
                    ->label('Deleted')
                    ->trueIcon('heroicon-o-trash')
                    ->trueColor('danger'),

                Tables\Columns\TextColumn::make('deletedByUser.username')
                    ->label('Deleted By')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(ChatMessageType::class),

                Tables\Filters\TernaryFilter::make('is_deleted')
                    ->label('Deleted Messages'),

                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('delete_message')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ChatMessage $record): bool => !$record->is_deleted)
                    ->action(function (ChatMessage $record): void {
                        $record->update([
                            'is_deleted' => true,
                            'deleted_by' => auth()->id(),
                        ]);
                        AuditLog::log('chat.message_deleted', $record, null, null, "Message: {$record->message}");
                        Notification::make()->title('Message deleted.')->success()->send();
                    }),

                Tables\Actions\Action::make('restore_message')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ChatMessage $record): bool => $record->is_deleted)
                    ->action(function (ChatMessage $record): void {
                        $record->update([
                            'is_deleted' => false,
                            'deleted_by' => null,
                        ]);
                        Notification::make()->title('Message restored.')->success()->send();
                    }),

                Tables\Actions\Action::make('ban_user')
                    ->label('Ban User')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ban User from Platform')
                    ->visible(fn (ChatMessage $record): bool => !$record->user?->is_banned && !$record->user?->isAdmin())
                    ->action(function (ChatMessage $record): void {
                        $record->user?->update(['is_banned' => true]);
                        AuditLog::log('user.banned', $record->user, null, ['is_banned' => true], 'Banned from chat moderation');
                        Notification::make()->title('User banned.')->success()->send();
                    }),

                Tables\Actions\Action::make('mute_user')
                    ->label('Mute 1h')
                    ->icon('heroicon-o-speaker-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ChatMessage $record): bool => !$record->user?->isAdmin())
                    ->action(function (ChatMessage $record): void {
                        $record->user?->update(['muted_until' => now()->addHour()]);
                        AuditLog::log('user.muted', $record->user, null, ['muted_until' => now()->addHour()->toISOString()]);
                        Notification::make()->title('User muted for 1 hour.')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('deleteSelected')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (ChatMessage $msg) => $msg->update([
                                'is_deleted' => true,
                                'deleted_by' => auth()->id(),
                            ]));
                            Notification::make()->title('Selected messages deleted.')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatMessages::route('/'),
            'view' => Pages\ViewChatMessage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
