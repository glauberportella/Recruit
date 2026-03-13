<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('jitsi.jitsi_domain', 'localhost');
        $this->migrator->add('jitsi.jitsi_public_url', 'https://localhost:8443');
        $this->migrator->add('jitsi.jwt_app_id', 'recruit');
        $this->migrator->add('jitsi.jwt_app_secret', '');
        $this->migrator->add('jitsi.default_meeting_duration', 60);
        $this->migrator->add('jitsi.enable_recording', false);
        $this->migrator->add('jitsi.enable_waiting_room', true);
        $this->migrator->add('jitsi.enable_password_protection', false);
    }
};
