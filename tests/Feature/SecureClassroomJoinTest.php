<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Services\ClassroomMeetingAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class SecureClassroomJoinTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->ensureMeetingTables();
    }

    protected function ensureMeetingTables(): void
    {
        if (! Schema::hasTable('classroom_meetings')) {
            Schema::create('classroom_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('consultation_request_id')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->unsignedBigInteger('tutoring_group_booking_id')->nullable();
                $table->string('code', 32)->unique();
                $table->string('room_name', 64)->nullable();
                $table->string('title')->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->unsignedInteger('planned_duration_minutes')->nullable();
                $table->unsignedInteger('max_participants')->nullable();
                $table->unsignedInteger('participants_peak')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('one_to_one_sessions')) {
            Schema::create('one_to_one_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instructor_id');
                $table->foreignId('student_id');
                $table->unsignedInteger('session_number')->default(1);
                $table->timestamp('scheduled_at')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(50);
                $table->string('status', 32)->default('scheduled');
                $table->unsignedBigInteger('classroom_meeting_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('classroom_meeting_participants')) {
            Schema::create('classroom_meeting_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_meeting_id');
                $table->string('token', 64);
                $table->string('display_name')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('left_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('live_servers')) {
            Schema::create('live_servers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('domain')->nullable();
                $table->string('provider', 32)->default('livekit');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        config([
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.url' => 'wss://live.glottical.com',
            'livekit.livekit.host' => 'live.glottical.com',
        ]);
    }

    public function test_private_one_to_one_blocks_guest_link_and_allows_student_enter(): void
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
        $outsider = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'PRIV1234',
            'room_name' => 'حصتك-PRIV1234',
            'title' => 'حصة خاصة',
            'max_participants' => 4,
            'settings' => ['allow_guest_join' => false, 'private_lesson' => true],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        $this->assertFalse(ClassroomMeetingAccessService::allowsGuestJoin($meeting));
        $this->assertTrue(ClassroomMeetingAccessService::userCanEnter($meeting, $student));
        $this->assertTrue(ClassroomMeetingAccessService::userCanEnter($meeting, $instructor));
        $this->assertFalse(ClassroomMeetingAccessService::userCanEnter($meeting, $outsider));
        $this->assertFalse(ClassroomMeetingAccessService::userIsHost($meeting, $student));
        $this->assertTrue(ClassroomMeetingAccessService::userIsHost($meeting, $instructor));

        $this->postJson(route('classroom.join.enter', ['code' => $meeting->code]), [
            'display_name' => 'متطفل',
        ])->assertStatus(403);

        $this->actingAs($outsider)
            ->get(route('classroom.secure-enter', $meeting))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('classroom.secure-enter', $meeting))
            ->assertRedirect(route('student.classroom.room', $meeting));
    }

    public function test_open_meeting_guest_join_requires_explicit_flag(): void
    {
        $host = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $host->id,
            'code' => 'OPEN9999',
            'room_name' => 'حصتك-OPEN9999',
            'title' => 'اجتماع مفتوح',
            'started_at' => now(),
            'max_participants' => 10,
            'settings' => ['allow_guest_join' => false],
        ]);

        $this->assertFalse(ClassroomMeetingAccessService::allowsGuestJoin($meeting));
        $this->get(route('classroom.join', ['code' => $meeting->code]))
            ->assertOk()
            ->assertSee('اجتماع خاص داخل المنصة', false);

        $meeting->update(['settings' => ['allow_guest_join' => true]]);
        $this->assertTrue(ClassroomMeetingAccessService::allowsGuestJoin($meeting->fresh()));
    }
}
