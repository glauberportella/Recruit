<?php

namespace App\Services\Jitsi;

use App\Models\Interview;
use App\Settings\JitsiSettings;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class JitsiService
{
    private JitsiSettings $settings;

    public function __construct(JitsiSettings $settings)
    {
        $this->settings = $settings;
    }

    public function generateRoomName(Interview $interview): string
    {
        return 'recruit-interview-' . $interview->id . '-' . Str::slug($interview->title);
    }

    public function generateToken(Interview $interview, array $user, bool $isModerator = false): string
    {
        $now = time();
        $payload = [
            'iss' => $this->settings->jwt_app_id,
            'sub' => $this->settings->jitsi_domain,
            'aud' => $this->settings->jwt_app_id,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($interview->duration_minutes * 60) + 900, // meeting duration + 15 min buffer
            'room' => $interview->meeting_room,
            'context' => [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'avatar' => '',
                    'moderator' => $isModerator,
                ],
                'features' => [
                    'livestreaming' => false,
                    'recording' => $this->settings->enable_recording,
                    'screen-sharing' => true,
                    'lobby' => $this->settings->enable_waiting_room,
                ],
            ],
        ];

        return JWT::encode($payload, $this->settings->jwt_app_secret, 'HS256');
    }

    public function getMeetingUrl(Interview $interview, array $user, bool $isModerator = false): string
    {
        $token = $this->generateToken($interview, $user, $isModerator);
        $baseUrl = rtrim($this->settings->jitsi_public_url, '/');
        $room = $interview->meeting_room;

        return "{$baseUrl}/{$room}?jwt={$token}";
    }

    public function getEmbedConfig(Interview $interview): array
    {
        return [
            'domain' => $this->settings->jitsi_domain,
            'roomName' => $interview->meeting_room,
            'configOverwrite' => [
                'startWithAudioMuted' => true,
                'startWithVideoMuted' => false,
                'prejoinPageEnabled' => $this->settings->enable_waiting_room,
                'disableDeepLinking' => true,
                'enableClosePage' => true,
                'enableWelcomePage' => false,
            ],
            'interfaceConfigOverwrite' => [
                'SHOW_JITSI_WATERMARK' => false,
                'SHOW_WATERMARK_FOR_GUESTS' => false,
                'DEFAULT_BACKGROUND' => '#1a1a2e',
                'TOOLBAR_BUTTONS' => [
                    'camera', 'chat', 'closedcaptions', 'desktop',
                    'filmstrip', 'fullscreen', 'hangup', 'microphone',
                    'participants-pane', 'raisehand', 'select-background',
                    'settings', 'tileview', 'toggle-camera',
                ],
            ],
        ];
    }
}
