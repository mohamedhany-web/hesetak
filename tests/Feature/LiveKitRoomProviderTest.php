<?php

namespace Tests\Feature;

use App\Models\LiveServer;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class LiveKitRoomProviderTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();

        Schema::create('live_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain');
            $table->string('provider', 32)->default('livekit');
            $table->string('status')->default('active');
            $table->string('ip_address')->nullable();
            $table->unsignedInteger('max_participants')->default(100);
            $table->unsignedInteger('current_load')->default(0);
            $table->json('config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable();
            $table->foreignId('instructor_id');
            $table->foreignId('server_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('room_name');
            $table->string('status')->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->boolean('is_recorded')->default(false);
            $table->boolean('allow_chat')->default(true);
            $table->boolean('allow_screen_share')->default(true);
            $table->boolean('require_enrollment')->default(false);
            $table->boolean('mute_on_join')->default(false);
            $table->boolean('video_off_on_join')->default(false);
            $table->string('password')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('session_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id');
            $table->foreignId('user_id');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('role_in_session')->nullable();
            $table->timestamps();
        });

        config([
            'livekit.provider' => 'livekit',
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.url' => 'wss://live.glottical.com',
            'livekit.livekit.host' => 'live.glottical.com',
        ]);
    }

    public function test_instructor_room_uses_livekit_client_not_jitsi(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        $server = LiveServer::create([
            'name' => 'حصتك LiveKit',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
            'ip_address' => '187.124.36.228',
            'max_participants' => 100,
        ]);

        $session = LiveSession::create([
            'instructor_id' => $instructor->id,
            'server_id' => $server->id,
            'title' => 'اختبار LiveKit',
            'room_name' => 'glottical-livekit-test',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('instructor.live-sessions.room', $session));

        $response->assertOk();
        $response->assertSee('livekit-client', false);
        $response->assertSee('live.glottical.com', false);
        $response->assertSee('مشاركة الشاشة', false);
        $response->assertDontSee('id="lk-chat-panel"', false);
        $response->assertDontSee('الدردشة', false);
        $response->assertSee('lk-theme-instructor', false);
        $response->assertSee('id="lk-focus"', false);
        $response->assertSee('id="lk-pip"', false);
        $response->assertSee('lk-pip-cols-btn', false);
        $response->assertSee('data-pip-cols="2"', false);
        $response->assertSee('track.attach', false);
        $response->assertSee('playPipVideo', false);
        $response->assertSee('syncFloatingPipExclusive', false);
        $response->assertSee('byIdentity', false);
        $response->assertSee('osPipOpening', false);
        $response->assertSee('id="lk-toggle-os-pip"', false);
        $response->assertSee('__mxLkToggleScreenAnnotate', false);
        $response->assertSee('startAnnotatedScreenShare', false);
        $response->assertSee('lk-local-share-placeholder', false);
        $response->assertSee('selfBrowserSurface', false);
        $response->assertSee('preferScreenShareQuality', false);
        $response->assertSee('scaleX(-1)', false);
        $response->assertSee('screenShareEncoding', false);
        $response->assertSee('adaptiveStream: false', false);
        $response->assertSee('subscriberAllowPause: false', false);
        $response->assertSee('ensureParticipantPresence', false);
        $response->assertSee('ParticipantConnected', false);
        $response->assertSee('__mxLkGetRecordCapture', false);
        $response->assertSee('cameraVideos', false);
        $response->assertSee('id="lk-zoom-in"', false);
        $response->assertSee('id="lk-toggle-mic"', false);
        $response->assertSee('microphone-slash', false);
        $response->assertSee('.lk-btn.is-off', false);
        $response->assertSee('is-on-active', false);
        $response->assertSee('syncMicButton', false);
        $response->assertSee('max="300"', false);
        $response->assertDontSee('max="500"', false);
        $response->assertSee('الكاميرات', false);
        $response->assertSee('su-live-shell', false);
        $response->assertSee('instructor-panel.css', false);
        $response->assertDontSee('external_api.js', false);
        $response->assertDontSee('JitsiMeetExternalAPI', false);
    }

    public function test_student_room_uses_student_theme_and_share_tools(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $server = LiveServer::create([
            'name' => 'حصتك LiveKit',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);
        $session = LiveSession::create([
            'instructor_id' => $instructor->id,
            'server_id' => $server->id,
            'title' => 'جلسة طالب',
            'room_name' => 'glottical-student-theme',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
            'allow_screen_share' => true,
            'allow_chat' => true,
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.live-sessions.join', $session));

        $response->assertOk();
        $response->assertSee('lk-theme-student', false);
        $response->assertSee('st-live-shell', false);
        $response->assertSee('id="lk-focus"', false);
        $response->assertSee('id="lk-pip"', false);
        $response->assertSee('id="lk-toggle-os-pip"', false);
        $response->assertSee('id="lk-zoom-in"', false);
        $response->assertSee('id="lk-zoom-slider"', false);
        $response->assertSee('max="500"', false);
        $response->assertSee('layout-duo', false);
        $response->assertSee('layout-trio', false);
        $response->assertSee('مشاركة الشاشة', false);
    }

    public function test_student_room_hides_screen_share_when_disabled(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $server = LiveServer::create([
            'name' => 'حصتك LiveKit',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);
        $session = LiveSession::create([
            'instructor_id' => $instructor->id,
            'server_id' => $server->id,
            'title' => 'بدون شير',
            'room_name' => 'glottical-no-share',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
            'allow_screen_share' => false,
            'allow_chat' => true,
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.live-sessions.join', $session));

        $response->assertOk();
        $response->assertDontSee('id="lk-toggle-screen"', false);
        $response->assertDontSee('id="lk-chat-panel"', false);
        $response->assertDontSee('الدردشة', false);
    }

    public function test_live_meeting_tokens_never_allow_data_publish(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        LiveServer::create([
            'name' => 'حصتك LiveKit',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);
        $session = LiveSession::create([
            'instructor_id' => $instructor->id,
            'title' => 'شات مفعّل في DB',
            'room_name' => 'glottical-chat-blocked',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
            'allow_chat' => true,
        ]);

        $payload = app(\App\Services\LiveMeetingProvider::class)->roomPayload($session, $instructor, true);

        $this->assertFalse($payload['allowChat']);
        $this->assertNotEmpty($payload['livekitToken']);
        $parts = explode('.', $payload['livekitToken']);
        $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertFalse($claims['video']['canPublishData'] ?? true);
    }
}
