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
use Illuminate\Support\Facades\Storage;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Site Settings';

    protected string $view = 'filament.pages.manage-site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => SiteSetting::get('site_name', 'Bet4Gain'),
            'site_description' => SiteSetting::get('site_description', 'The Ultimate Crash Game'),
            'site_keywords' => SiteSetting::get('site_keywords', 'crash game, betting, aviator'),
            'site_logo' => SiteSetting::get('site_logo', ''),
            'contact_email' => SiteSetting::get('contact_email', ''),
            'maintenance_mode' => SiteSetting::get('maintenance_mode', false),
            'registration_enabled' => SiteSetting::get('registration_enabled', true),
            'email_verification_required' => SiteSetting::get('email_verification_required', false),
            'google_analytics_id' => SiteSetting::get('google_analytics_id', ''),
            'footer_text' => SiteSetting::get('footer_text', '© 2024 Bet4Gain. All rights reserved.'),
            'terms_url' => SiteSetting::get('terms_url', ''),
            'privacy_url' => SiteSetting::get('privacy_url', ''),
            'social_discord' => SiteSetting::get('social_discord', ''),
            'social_telegram' => SiteSetting::get('social_telegram', ''),
            'social_twitter' => SiteSetting::get('social_twitter', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('General')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('site_description')
                            ->label('Meta Description')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('site_keywords')
                            ->label('Meta Keywords')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Branding')
                    ->description('Upload your site logo. Files are stored on the public disk.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('site_logo')
                            ->label('Site Logo')
                            ->image()
                            ->disk('public')
                            ->directory('site')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Recommended: PNG/SVG, transparent background. Max 2MB.')
                            ->imagePreviewHeight('80')
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('footer_text')
                            ->label('Footer Text')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Access Control')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode')
                            ->helperText('When enabled, only admins can access the site'),

                        Forms\Components\Toggle::make('registration_enabled')
                            ->label('Registration Enabled'),

                        Forms\Components\Toggle::make('email_verification_required')
                            ->label('Email Verification Required'),
                    ]),

                Forms\Components\Section::make('Analytics & Legal')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('Google Analytics ID')
                            ->placeholder('G-XXXXXXXXXX')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('terms_url')
                            ->label('Terms of Service URL')
                            ->url()
                            ->maxLength(2048),

                        Forms\Components\TextInput::make('privacy_url')
                            ->label('Privacy Policy URL')
                            ->url()
                            ->maxLength(2048),
                    ]),

                Forms\Components\Section::make('Social Links')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('social_discord')
                            ->label('Discord')
                            ->url()
                            ->maxLength(2048),

                        Forms\Components\TextInput::make('social_telegram')
                            ->label('Telegram')
                            ->url()
                            ->maxLength(2048),

                        Forms\Components\TextInput::make('social_twitter')
                            ->label('Twitter / X')
                            ->url()
                            ->maxLength(2048),
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

            if ($key === 'site_logo' && is_string($old) && ! empty($old) && $old !== $value) {
                Storage::disk('public')->delete($old);
            }

            if ($old !== $value) {
                $oldValues[$key] = $old;
                $newValues[$key] = $value;
            }

            $type = match (true) {
                is_bool($value) => 'boolean',
                $key === 'site_logo' => 'file',
                default => 'string',
            };

            SiteSetting::set($key, $value, $type, 'site');
        }

        if (!empty($newValues)) {
            AuditLog::log('setting.site_updated', null, $oldValues, $newValues);
        }

        Notification::make()
            ->title('Site settings saved successfully.')
            ->success()
            ->send();
    }
}
