<?php

namespace App\Services;

use App\Models\LiveServer;
use App\Models\LiveSetting;
use App\Models\LiveSession;
use App\Models\User;

/**
 * مزوّد غرف البث لمنصة حصتك — LiveKit فقط.
 */
class LiveMeetingProvider
{
    public function __construct(
        private readonly LiveKitTokenService $liveKit,
    ) {}

    public function defaultProvider(): string
    {
        return 'livekit';
    }

    public function resolveProvider(?LiveServer $server = null): string
    {
        return 'livekit';
    }

    public function usesLiveKit(?LiveServer $server = null): bool
    {
        return true;
    }

    /**
     * @return array{
     *   provider: string,
     *   livekitUrl: string|null,
     *   livekitToken: string|null,
     *   livekitHost: string|null,
     *   livekitConfigured: bool,
     *   allowScreenShare: bool,
     *   allowChat: bool
     * }
     */
    public function roomPayload(LiveSession $session, User $user, bool $isHost = false): array
    {
        $server = $session->server ?: $this->preferredLiveKitServer();
        $allowScreenShare = $isHost || (bool) ($session->allow_screen_share ?? true);

        $payload = [
            'provider' => 'livekit',
            'livekitUrl' => $this->liveKit->wsUrl($server),
            'livekitToken' => null,
            'livekitHost' => $this->liveKit->publicHost($server),
            'livekitConfigured' => $this->liveKit->isConfigured(),
            'allowScreenShare' => $allowScreenShare,
            'allowChat' => false,
        ];

        if ($this->liveKit->isConfigured()) {
            $grants = [
                'canPublish' => true,
                'canSubscribe' => true,
                // البث الجماعي: لا نسمح بـ data channel (دردشة/إشارات غير مرغوبة)
                'canPublishData' => false,
                'roomAdmin' => $isHost,
            ];

            // Restrict students from publishing screen when host disabled share.
            if (! $allowScreenShare) {
                $grants['canPublishSources'] = ['camera', 'microphone'];
            }

            $payload['livekitToken'] = $this->liveKit->createJoinToken(
                $session->room_name,
                $user,
                array_merge($grants, [
                    'metadata' => [
                        'user_id' => $user->id,
                        'role' => $user->role ?? null,
                        'is_host' => $isHost,
                    ],
                ])
            );
        }

        return $payload;
    }

    /**
     * @param  array{canPublish?: bool, canSubscribe?: bool, canPublishData?: bool, roomAdmin?: bool, hidden?: bool, canPublishSources?: list<string>}  $grants
     * @return array{provider: string, livekitUrl: string|null, livekitToken: string|null, livekitHost: string|null, livekitConfigured: bool, roomName: string, allowScreenShare: bool, allowChat: bool}
     */
    public function classroomPayload(string $roomName, User $user, bool $isHost = false, array $grants = []): array
    {
        $server = $this->preferredLiveKitServer();
        $payload = [
            'provider' => 'livekit',
            'livekitUrl' => $this->liveKit->wsUrl($server),
            'livekitToken' => null,
            'livekitHost' => $this->liveKit->publicHost($server),
            'livekitConfigured' => $this->liveKit->isConfigured(),
            'roomName' => $roomName,
            'allowScreenShare' => true,
            'allowChat' => false,
        ];

        if ($this->liveKit->isConfigured()) {
            $payload['livekitToken'] = $this->liveKit->createJoinToken(
                $roomName,
                $user,
                array_merge([
                    'canPublish' => true,
                    'canSubscribe' => true,
                    // حصص Classroom الخاصة تحتاج data channel لمزامنة السبورة الفورية
                    'canPublishData' => true,
                    'roomAdmin' => $isHost,
                    'metadata' => [
                        'is_host' => $isHost,
                    ],
                ], $grants)
            );
        }

        return $payload;
    }

    /**
     * @return array{provider: string, livekitUrl: string|null, livekitToken: string|null, livekitHost: string|null, livekitConfigured: bool, roomName: string, allowScreenShare: bool, allowChat: bool}
     */
    public function classroomGuestPayload(string $roomName, string $guestToken, string $displayName): array
    {
        $server = $this->preferredLiveKitServer();
        $payload = [
            'provider' => 'livekit',
            'livekitUrl' => $this->liveKit->wsUrl($server),
            'livekitToken' => null,
            'livekitHost' => $this->liveKit->publicHost($server),
            'livekitConfigured' => $this->liveKit->isConfigured(),
            'roomName' => $roomName,
            'allowScreenShare' => true,
            'allowChat' => false,
        ];

        if ($this->liveKit->isConfigured()) {
            $payload['livekitToken'] = $this->liveKit->createIdentityToken(
                $roomName,
                'guest-'.substr(hash('sha256', $guestToken), 0, 16),
                $displayName,
                [
                    'canPublish' => true,
                    'canSubscribe' => true,
                    // ضيوف الحصة الخاصة: السماح بمزامنة السبورة عند التفعيل
                    'canPublishData' => true,
                    'metadata' => ['guest' => true, 'display_name' => $displayName],
                ]
            );
        }

        return $payload;
    }

    public function preferredLiveKitServer(): ?LiveServer
    {
        return LiveServer::query()
            ->where('status', 'active')
            ->where('provider', 'livekit')
            ->orderByDesc('id')
            ->first();
    }
}
