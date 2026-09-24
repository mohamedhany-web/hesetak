<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class PublicPiiHardeningTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
    }

    public function test_certificate_verify_does_not_expose_email(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'secret-student@example.com',
            'password' => Hash::make('password'),
        ]);

        $code = 'CERT-'.bin2hex(random_bytes(8));

        Certificate::query()->create([
            'user_id' => $student->id,
            'certificate_number' => 'CERT-00000099',
            'verification_code' => $code,
            'title' => 'Test',
            'issued_at' => now(),
            'status' => 'issued',
        ]);

        $this->get('/certificates/verify/'.$code)
            ->assertOk()
            ->assertDontSee('secret-student@example.com', false)
            ->assertDontSee('البريد', false);
    }

    public function test_new_referral_codes_do_not_embed_user_id(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['referral_code' => null])->saveQuietly();

        $code = app(ReferralService::class)->generateReferralCode($user->fresh());

        $this->assertStringStartsWith('REF', $code);
        $this->assertStringNotContainsString(str_pad((string) $user->id, 6, '0', STR_PAD_LEFT), $code);
    }

    public function test_sensitive_media_paths_are_not_public(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tutor-applications/ids/doc.pdf', 'secret-id-doc');
        Storage::disk('public')->put('tutor-applications/photos/avatar.jpg', 'public-photo');

        $this->get('/media/tutor-applications/ids/doc.pdf')->assertNotFound();
        $this->get('/storage/tutor-applications/ids/doc.pdf')->assertNotFound();
        $this->get('/media/tutor-applications/photos/avatar.jpg')->assertOk();
    }

    public function test_user_route_key_never_falls_back_to_numeric_id(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['uuid' => null])->saveQuietly();
        $user->refresh();

        $key = $user->getRouteKey();
        $this->assertNotSame((string) $user->id, $key);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $key
        );
    }
}
