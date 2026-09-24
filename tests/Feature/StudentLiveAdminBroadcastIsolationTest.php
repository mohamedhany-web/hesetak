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

class StudentLiveAdminBroadcastIsolationTest extends TestCase
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
    }

    public function test_admin_only_broadcast_is_hidden_from_student_index_and_join(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'name' => 'مدير منصة glottical',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'name' => 'معلم الكورس',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $server = LiveServer::create([
            'name' => 'Hissatak Meeting',
            'domain' => 'live.glottical.com',
            'provider' => 'livekit',
            'status' => 'active',
        ]);

        $adminBroadcast = LiveSession::create([
            'instructor_id' => $admin->id,
            'server_id' => $server->id,
            'title' => 'بث إداري — 2026/08/23 00:11',
            'description' => 'جلسة مباشرة أنشأتها الإدارة للانضمام الفوري.',
            'room_name' => 'admin-only-room',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
            'settings' => ['admin_only' => true],
        ]);

        $studentSession = LiveSession::create([
            'instructor_id' => $instructor->id,
            'server_id' => $server->id,
            'title' => 'جلسة كورس مفتوحة',
            'room_name' => 'student-open-room',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
        ]);

        $this->assertTrue($adminBroadcast->isAdminOnlyBroadcast());
        $this->assertFalse($studentSession->isAdminOnlyBroadcast());
        $this->assertFalse($adminBroadcast->canUserJoin($student));
        $this->assertTrue($studentSession->canUserJoin($student));

        $index = $this->actingAs($student)->get(route('student.live-sessions.index'));
        $index->assertOk();
        $index->assertDontSee('بث إداري', false);
        $index->assertDontSee('مدير منصة glottical', false);
        $index->assertSee('جلسة كورس مفتوحة', false);
        $index->assertSee('معلم الكورس', false);

        $this->actingAs($student)
            ->post(route('student.live-sessions.join', $adminBroadcast))
            ->assertRedirect();
    }

    public function test_legacy_admin_broadcast_without_settings_flag_is_still_blocked(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        $legacy = LiveSession::create([
            'instructor_id' => $admin->id,
            'title' => 'بث إداري — قديم',
            'description' => 'جلسة مباشرة أنشأتها الإدارة للانضمام الفوري.',
            'room_name' => 'legacy-admin-room',
            'status' => 'live',
            'started_at' => now(),
            'require_enrollment' => false,
        ]);

        $this->assertTrue($legacy->isAdminOnlyBroadcast());
        $this->assertFalse($legacy->canUserJoin($student));

        $this->actingAs($student)
            ->get(route('student.live-sessions.index'))
            ->assertOk()
            ->assertDontSee('بث إداري — قديم', false);
    }
}
