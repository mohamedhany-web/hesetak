<?php

namespace Tests\Feature;

use App\Models\LiveServer;
use App\Models\LiveSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class AdminLiveSessionFlowTest extends TestCase
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
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('role_in_session')->nullable();
            $table->timestamps();
        });

        Schema::create('advanced_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('live_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        config([
            'livekit.provider' => 'livekit',
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.url' => 'wss://live.glottical.com',
            'livekit.livekit.host' => 'live.glottical.com',
            'currency.code' => 'SAR',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
    }

    public function test_admin_index_and_create_pages_render(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.live-sessions.index'))->assertOk()
            ->assertSee('ابدأ بثاً الآن', false)
            ->assertSee('جدولة جلسة', false);

        $this->actingAs($admin)->get(route('admin.live-sessions.create'))->assertOk()
            ->assertSee('جدولة جلسة بث', false);
    }

    public function test_admin_instant_creates_live_session_and_opens_room(): void
    {
        $admin = $this->admin();

        LiveServer::create([
            'name' => 'حصتك LiveKit',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.live-sessions.instant'), [
                'title' => 'بث اختبار إداري',
            ]);

        $session = LiveSession::query()->latest('id')->first();
        $this->assertNotNull($session);
        $this->assertSame('live', $session->status);
        $this->assertSame($admin->id, $session->instructor_id);
        $this->assertFalse((bool) $session->require_enrollment);
        $this->assertTrue($session->isAdminOnlyBroadcast());
        $this->assertTrue((bool) data_get($session->settings, 'admin_only'));

        $response->assertRedirect(route('admin.live-sessions.room', $session));

        $this->assertDatabaseHas('session_attendance', [
            'session_id' => $session->id,
            'user_id' => $admin->id,
            'role_in_session' => 'instructor',
        ]);

        $room = $this->actingAs($admin)->get(route('admin.live-sessions.room', $session));
        $room->assertOk();
        $room->assertSee('livekit-client', false);
        $room->assertSee('بث إداري', false);
        $room->assertDontSee('JitsiMeetExternalAPI', false);
    }

    public function test_admin_can_start_scheduled_session_and_end_it(): void
    {
        $admin = $this->admin();
        $server = LiveServer::create([
            'name' => 'حصتك LiveKit',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);

        $session = LiveSession::create([
            'instructor_id' => $admin->id,
            'server_id' => $server->id,
            'title' => 'جلسة مجدولة',
            'room_name' => 'admin-sched-test',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
            'require_enrollment' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.live-sessions.start', $session))
            ->assertRedirect(route('admin.live-sessions.room', $session));

        $session->refresh();
        $this->assertSame('live', $session->status);
        $this->assertNotNull($session->started_at);

        $this->actingAs($admin)
            ->post(route('admin.live-sessions.end', $session))
            ->assertRedirect(route('admin.live-sessions.show', $session));

        $session->refresh();
        $this->assertSame('ended', $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_admin_show_page_has_join_actions_when_live(): void
    {
        $admin = $this->admin();
        $session = LiveSession::create([
            'instructor_id' => $admin->id,
            'title' => 'جلسة مباشرة',
            'room_name' => 'admin-live-show',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.live-sessions.show', $session))
            ->assertOk()
            ->assertSee('دخول الغرفة', false)
            ->assertSee('إنهاء البث', false);
    }

    public function test_currency_helper_defaults_to_sar(): void
    {
        $this->assertSame('SAR', platform_currency());
        $this->assertSame('ر.س', currency_symbol());
        $this->assertSame('SAR', config('currency.code'));
    }
}
