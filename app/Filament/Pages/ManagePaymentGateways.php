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

class ManagePaymentGateways extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Payment Gateways';

    protected string $view = 'filament.pages.manage-payment-gateways';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Paystack
            'paystack_enabled' => SiteSetting::get('paystack_enabled', false),
            'paystack_public_key' => SiteSetting::get('paystack_public_key', ''),
            'paystack_secret_key' => SiteSetting::get('paystack_secret_key', ''),
            'paystack_test_mode' => SiteSetting::get('paystack_test_mode', true),

            // Nomba
            'nomba_enabled' => SiteSetting::get('nomba_enabled', false),
            'nomba_client_id' => SiteSetting::get('nomba_client_id', ''),
            'nomba_client_secret' => SiteSetting::get('nomba_client_secret', ''),
            'nomba_account_id' => SiteSetting::get('nomba_account_id', ''),
            'nomba_test_mode' => SiteSetting::get('nomba_test_mode', true),

            // Limits
            'min_deposit' => SiteSetting::get('min_deposit', 500),
            'max_deposit' => SiteSetting::get('max_deposit', 500000),
            'min_withdrawal' => SiteSetting::get('min_withdrawal', 1000),
            'max_withdrawal' => SiteSetting::get('max_withdrawal', 500000),
            'withdrawal_fee_percent' => SiteSetting::get('withdrawal_fee_percent', 1.5),
            'deposit_fee_percent' => SiteSetting::get('deposit_fee_percent', 0),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Paystack')
                    ->description('Configure Paystack payment gateway.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('paystack_enabled')
                            ->label('Enable Paystack')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('paystack_public_key')
                            ->label('Public Key')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('paystack_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('paystack_test_mode')
                            ->label('Test Mode')
                            ->helperText('Use test keys for sandbox environment'),
                    ]),

                Forms\Components\Section::make('Nomba')
                    ->description('Configure Nomba payment gateway.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('nomba_enabled')
                            ->label('Enable Nomba')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nomba_client_id')
                            ->label('Client ID')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nomba_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nomba_account_id')
                            ->label('Account ID')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('nomba_test_mode')
                            ->label('Test Mode'),
                    ]),

                Forms\Components\Section::make('Deposit & Withdrawal Limits')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('min_deposit')
                            ->label('Minimum Deposit')
                            ->numeric()
                            ->required()
                            ->prefix('₦'),

                        Forms\Components\TextInput::make('max_deposit')
                            ->label('Maximum Deposit')
                            ->numeric()
                            ->required()
                            ->prefix('₦'),

                        Forms\Components\TextInput::make('min_withdrawal')
                            ->label('Minimum Withdrawal')
                            ->numeric()
                            ->required()
                            ->prefix('₦'),

                        Forms\Components\TextInput::make('max_withdrawal')
                            ->label('Maximum Withdrawal')
                            ->numeric()
                            ->required()
                            ->prefix('₦'),

                        Forms\Components\TextInput::make('deposit_fee_percent')
                            ->label('Deposit Fee')
                            ->numeric()
                            ->required()
                            ->suffix('%'),

                        Forms\Components\TextInput::make('withdrawal_fee_percent')
                            ->label('Withdrawal Fee')
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $oldValues = [];
        $newValues = [];

        // Sensitive fields - mask in audit log
        $sensitiveKeys = ['paystack_public_key', 'paystack_secret_key', 'nomba_client_id', 'nomba_client_secret'];

        foreach ($data as $key => $value) {
            $old = SiteSetting::get($key);
            if ($old !== $value) {
                if (in_array($key, $sensitiveKeys)) {
                    $oldValues[$key] = $old ? '****' . substr((string) $old, -4) : null;
                    $newValues[$key] = $value ? '****' . substr((string) $value, -4) : null;
                } else {
                    $oldValues[$key] = $old;
                    $newValues[$key] = $value;
                }
            }

            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) || is_float($value) => 'string',
                default => 'string',
            };

            SiteSetting::set($key, $value, $type, 'payment');
        }

        if (!empty($newValues)) {
            AuditLog::log('setting.payment_updated', null, $oldValues, $newValues);
        }

        Notification::make()
            ->title('Payment gateway settings saved successfully.')
            ->success()
            ->send();
    }
}
