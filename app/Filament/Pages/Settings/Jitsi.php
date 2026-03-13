<?php

namespace App\Filament\Pages\Settings;

use App\Settings\JitsiSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class Jitsi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static string $view = 'filament.pages.settings.jitsi';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Settings';

    protected ?string $heading = '';

    protected static ?string $navigationLabel = 'Online Interviews (Jitsi)';

    public array $state = [];

    public function mount(JitsiSettings $settings): void
    {
        $this->state = [
            'jitsi_domain' => $settings->jitsi_domain ?? 'localhost',
            'jitsi_public_url' => $settings->jitsi_public_url ?? 'https://localhost:8443',
            'jwt_app_id' => $settings->jwt_app_id ?? 'recruit',
            'jwt_app_secret' => $settings->jwt_app_secret ?? '',
            'default_meeting_duration' => $settings->default_meeting_duration ?? 60,
            'enable_recording' => $settings->enable_recording ?? false,
            'enable_waiting_room' => $settings->enable_waiting_room ?? true,
            'enable_password_protection' => $settings->enable_password_protection ?? false,
        ];
    }

    public function saveSettings(JitsiSettings $settings): void
    {
        Validator::make($this->state, [
            'jitsi_domain' => ['required', 'string', 'max:255'],
            'jitsi_public_url' => ['required', 'string', 'url:http,https', 'max:500'],
            'jwt_app_id' => ['required', 'string', 'max:100'],
            'jwt_app_secret' => ['nullable', 'string', 'max:500'],
            'default_meeting_duration' => ['required', 'integer', 'min:15', 'max:480'],
        ])->validateWithBag('saveSettings');

        $settings->jitsi_domain = $this->state['jitsi_domain'];
        $settings->jitsi_public_url = $this->state['jitsi_public_url'];
        $settings->jwt_app_id = $this->state['jwt_app_id'];
        $settings->jwt_app_secret = $this->state['jwt_app_secret'];
        $settings->default_meeting_duration = (int) $this->state['default_meeting_duration'];
        $settings->enable_recording = (bool) $this->state['enable_recording'];
        $settings->enable_waiting_room = (bool) $this->state['enable_waiting_room'];
        $settings->enable_password_protection = (bool) $this->state['enable_password_protection'];
        $settings->save();

        Notification::make()
            ->title('Jitsi settings updated')
            ->success()
            ->body('Your Jitsi Meet configuration has been saved successfully.')
            ->send();
    }
}
