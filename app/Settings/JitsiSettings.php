<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class JitsiSettings extends Settings
{
    public ?string $jitsi_domain;

    public ?string $jitsi_public_url;

    public ?string $jwt_app_id;

    public ?string $jwt_app_secret;

    public int $default_meeting_duration;

    public bool $enable_recording;

    public bool $enable_waiting_room;

    public bool $enable_password_protection;

    public static function group(): string
    {
        return 'jitsi';
    }
}
