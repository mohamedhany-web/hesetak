<?php

namespace App\Services;

use App\Models\LiveServer;
use App\Models\LiveSetting;
use App\Models\User;
use InvalidArgumentException;

class LiveKitTokenService
{
    public function isConfigured(): bool
    {
        $key = (string) config('livekit.livekit.api_key');
        $secret = (string) config('livekit.livekit.api_secret');

        return $key !== '' && $secret !== '';
    }

    public function wsUrl(?LiveServer $server = null): string
    {
        if ($server && $server->provider === 'livekit') {
            $host = LiveSetting::normalizeLiveHost((string) $server->domain);
            if ($host !== '') {
                return 'wss://'.$host;
            }
        }

        $url = trim((string) config('livekit.livekit.url', ''));
        if ($url !== '') {
            return $url;
        }

        $host = trim((string) config('livekit.livekit.host', 'live.hissatak.online'));

        return 'wss://'.$host;
    }

    public function publicHost(?LiveServer $server = null): string
    {
        if ($server && $server->provider === 'livekit') {
            $host = LiveSetting::normalizeLiveHost((string) $server->domain);
            if ($host !== '') {
                return $host;
            }
        }

        return trim((string) config('livekit.livekit.host', 'live.hissatak.online'));
    }

    /**
     * @param  array{canPublish?: bool, canSubscribe?: bool, canPublishData?: bool, roomAdmin?: bool, hidden?: bool, canPublishSources?: list<string>, metadata?: array<string, mixed>}  $grants
     */
    public function createJoinToken(string $roomName, User $user, array $grants = []): string
    {
        $identity = 'user-'.$user->id;
        $name = trim((string) ($user->name ?: $user->email ?: $identity));
        $existingMeta = is_array($grants['metadata'] ?? null) ? $grants['metadata'] : [];

        return $this->createIdentityToken($roomName, $identity, $name, array_merge($grants, [
            'metadata' => array_merge($existingMeta, [
                'user_id' => $user->id,
                'role' => $user->role ?? null,
            ]),
        ]));
    }

    /**
     * توكن LiveKit لهوية نصية (ضيف classroom / مشارك بدون User).
     *
     * @param  array{canPublish?: bool, canSubscribe?: bool, canPublishData?: bool, roomAdmin?: bool, hidden?: bool, canPublishSources?: list<string>, metadata?: array<string, mixed>}  $grants
     */
    public function createIdentityToken(string $roomName, string $identity, string $displayName, array $grants = []): string
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('مفاتيح Hissatak Meeting غير مضبوطة في ملف البيئة.');
        }

        $apiKey = (string) config('livekit.livekit.api_key');
        $apiSecret = (string) config('livekit.livekit.api_secret');
        $ttl = max(300, (int) config('livekit.livekit.token_ttl', 21600));
        $now = time();

        $identity = trim($identity) !== '' ? trim($identity) : ('guest-'.bin2hex(random_bytes(6)));
        $name = trim($displayName) !== '' ? trim($displayName) : $identity;

        $video = [
            'roomJoin' => true,
            'room' => $roomName,
            'canPublish' => (bool) ($grants['canPublish'] ?? true),
            'canSubscribe' => (bool) ($grants['canSubscribe'] ?? true),
            'canPublishData' => (bool) ($grants['canPublishData'] ?? true),
        ];

        if (! empty($grants['canPublishSources']) && is_array($grants['canPublishSources'])) {
            $video['canPublishSources'] = array_values(array_filter(
                array_map('strval', $grants['canPublishSources'])
            ));
        }

        if (! empty($grants['roomAdmin'])) {
            $video['roomAdmin'] = true;
            $video['roomCreate'] = true;
        }

        if (! empty($grants['hidden'])) {
            $video['hidden'] = true;
        }

        $meta = $grants['metadata'] ?? ['identity' => $identity];

        $payload = [
            'iss' => $apiKey,
            'sub' => $identity,
            'nbf' => $now - 10,
            'exp' => $now + $ttl,
            'name' => $name,
            'video' => $video,
            'metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ];

        return $this->encodeJwt($payload, $apiSecret);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeJwt(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
