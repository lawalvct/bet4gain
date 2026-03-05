<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ManageGameSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Game Settings';

    protected string $view = 'filament.pages.manage-game-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'min_bet' => SiteSetting::get('min_bet', 10),
            'max_bet' => SiteSetting::get('max_bet', 100000),
            'house_edge' => SiteSetting::get('house_edge', 5),
            'betting_duration' => SiteSetting::get('betting_duration', 10),
            'max_players_per_round' => SiteSetting::get('max_players_per_round', 100),
            'auto_cashout_max' => SiteSetting::get('auto_cashout_max', 1000),
            'demo_balance' => SiteSetting::get('demo_balance', 10000),
            'coin_to_currency_rate' => SiteSetting::get('coin_to_currency_rate', 1),
            'flying_object' => SiteSetting::get('flying_object', 'rocket'),
            'game_background' => SiteSetting::get('game_background', 'default'),
            'sound_enabled' => SiteSetting::get('sound_enabled', true),
            'chat_enabled' => SiteSetting::get('chat_enabled', true),
            'guest_play_enabled' => SiteSetting::get('guest_play_enabled', true),
            'max_chat_message_length' => SiteSetting::get('max_chat_message_length', 200),
            'chat_cooldown_seconds' => SiteSetting::get('chat_cooldown_seconds', 3),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Betting Limits')
                    ->description('Configure minimum and maximum bet amounts.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('min_bet')
                            ->label('Minimum Bet')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('coins'),

                        Forms\Components\TextInput::make('max_bet')
                            ->label('Maximum Bet')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('coins'),

                        Forms\Components\TextInput::make('auto_cashout_max')
                            ->label('Max Auto-Cashout Multiplier')
                            ->numeric()
                            ->required()
                            ->suffix('x'),

                        Forms\Components\TextInput::make('max_players_per_round')
                            ->label('Max Players Per Round')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ]),

                Forms\Components\Section::make('Game Engine')
                    ->description('Configure house edge and game timing.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('house_edge')
                            ->label('House Edge')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(20)
                            ->suffix('%')
                            ->helperText('Percentage of each bet retained by the house'),

                        Forms\Components\TextInput::make('betting_duration')
                            ->label('Betting Phase Duration')
                            ->numeric()
                            ->required()
                            ->minValue(5)
                            ->maxValue(60)
                            ->suffix('seconds'),

                        Forms\Components\TextInput::make('demo_balance')
                            ->label('Default Demo Balance')
                            ->numeric()
                            ->required()
                            ->suffix('coins'),

                        Forms\Components\TextInput::make('coin_to_currency_rate')
                            ->label('Coin to Currency Rate')
                            ->numeric()
                            ->required()
                            ->helperText('1 coin = X NGN'),
                    ]),

                Forms\Components\Section::make('Game Appearance')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('flying_object')
                            ->label('Flying Object')
                            ->options([
                                'rocket' => 'Rocket',
                                'airplane' => 'Airplane',
                                'spaceship' => 'Spaceship',
                                'bird' => 'Bird',
                                'balloon' => 'Balloon',
                            ])
                            ->required(),

                        Forms\Components\Select::make('game_background')
                            ->label('Game Background')
                            ->options([
                                'default' => 'Default (Space)',
                                'sky' => 'Sky',
                                'ocean' => 'Ocean',
                                'mountain' => 'Mountain',
                                'city' => 'City Night',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('sound_enabled')
                            ->label('Sound Effects Enabled'),
                    ]),

                Forms\Components\Section::make('Chat Settings')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('chat_enabled')
                            ->label('Chat Enabled'),

                        Forms\Components\Toggle::make('guest_play_enabled')
                            ->label('Guest Play Enabled'),

                        Forms\Components\TextInput::make('max_chat_message_length')
                            ->label('Max Message Length')
                            ->numeric()
                            ->required()
                            ->suffix('chars'),

                        Forms\Components\TextInput::make('chat_cooldown_seconds')
                            ->label('Chat Cooldown')
                            ->numeric()
                            ->required()
                            ->suffix('seconds'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $oldValues = [];
        $newValues = [];

        foreach ($data as $key => $value) {
            $old = SiteSetting::get($key);
            if ($old !== $value) {
                $oldValues[$key] = $old;
                $newValues[$key] = $value;
            }

            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_float($value) => 'string',
                default => 'string',
            };

            SiteSetting::set($key, $value, $type, 'game');
        }

        if (!empty($newValues)) {
            AuditLog::log('setting.game_updated', null, $oldValues, $newValues);
        }

        Notification::make()
            ->title('Game settings saved successfully.')
            ->success()
            ->send();
    }
}
