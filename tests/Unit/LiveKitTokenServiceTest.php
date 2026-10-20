<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\LiveKitTokenService;
use Tests\TestCase;

class LiveKitTokenServiceTest extends TestCase
{
    public function test_create_join_token_contains_expected_claims(): void
    {
        config([
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.token_ttl' => 3600,
            'livekit.livekit.url' => 'wss://live.hissatak.online',
            'livekit.livekit.host' => 'live.hissatak.online',
        ]);

        $user = new User([
            'name' => 'معلم تجريبي',
            'email' => 'teacher@example.com',
            'role' => 'instructor',
        ]);
        $user->id = 42;

        $service = new LiveKitTokenService();
        $this->assertTrue($service->isConfigured());
        $this->assertSame('wss://live.hissatak.online', $service->wsUrl());

        $token = $service->createJoinToken('room-demo', $user, ['roomAdmin' => true]);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        $payload = json_decode($this->b64urlDecode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('APItestkey', $payload['iss']);
        $this->assertSame('user-42', $payload['sub']);
        $this->assertSame('room-demo', $payload['video']['room']);
        $this->assertTrue($payload['video']['roomJoin']);
        $this->assertTrue($payload['video']['roomAdmin']);
    }

    public function test_can_publish_sources_restricts_screen_share(): void
    {
        config([
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.token_ttl' => 3600,
        ]);

        $service = new LiveKitTokenService();
        $token = $service->createIdentityToken(
            'room-a',
            'guest-1',
            'Guest',
            ['canPublishSources' => ['camera', 'microphone']]
        );
        $parts = explode('.', $token);
        $payload = json_decode($this->b64urlDecode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['camera', 'microphone'], $payload['video']['canPublishSources']);
    }

    private function b64urlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
