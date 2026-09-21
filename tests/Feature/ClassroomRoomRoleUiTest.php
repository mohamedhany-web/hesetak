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

class ClassroomRoomRoleUiTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->ensureMeetingTables();

        config([
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.url' => 'wss://live.glottical.com',
            'livekit.livekit.host' => 'live.glottical.com',
        ]);
    }

    protected function ensureMeetingTables(): void
    {
        if (! Schema::hasTable('classroom_meetings')) {
            Schema::create('classroom_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->string('code', 32)->unique();
                $table->string('room_name', 64)->nullable();
                $table->string('title')->nullable();
                $table->unsignedInteger('planned_duration_minutes')->nullable();
                $table->unsignedInteger('max_participants')->nullable();
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
    }

    /**
     * @return array{0: User, 1: User, 2: ClassroomMeeting}
     */
    protected function seedOneToOneMeeting(): array
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
            'code' => 'UI'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-UI-TEST',
            'title' => 'حصة تسكين',
            'planned_duration_minutes' => 50,
            'max_participants' => 4,
            'started_at' => now(),
            'settings' => ['allow_guest_join' => false, 'private_lesson' => true],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        return [$instructor, $student, $meeting];
    }

    public function test_student_is_never_meeting_host_for_one_to_one(): void
    {
        [, $student, $meeting] = $this->seedOneToOneMeeting();

        $this->assertFalse(ClassroomMeetingAccessService::userIsHost($meeting, $student));
        $this->assertTrue(ClassroomMeetingAccessService::userCanEnter($meeting, $student));
    }

    public function test_instructor_is_host_for_one_to_one(): void
    {
        [$instructor, , $meeting] = $this->seedOneToOneMeeting();

        $this->assertTrue(ClassroomMeetingAccessService::userIsHost($meeting, $instructor));
    }

    public function test_student_room_hides_instructor_toolbar_controls(): void
    {
        [, $student, $meeting] = $this->seedOneToOneMeeting();

        $response = $this->actingAs($student)
            ->get(route('student.classroom.room', $meeting));

        $response->assertOk();
        $response->assertSee('st-live-shell', false);
        $response->assertSee('حصة خاصة', false);
        $response->assertSee('data.ended', false);
        $response->assertDontSee('بث إداري', false);
        $response->assertDontSee('بث مباشر', false);
        $response->assertSee('lk-theme-student', false);
        $response->assertSee('emit_and_poll', false);
        $response->assertSee('share-annotation', false);
        $response->assertSee('mx-ann-above-media', false);
        $response->assertSee('microphone-slash', false);
        $response->assertSee('syncMicButton', false);
        $response->assertSee('id="lk-zoom-slider"', false);
        $response->assertSee('id="lk-toolbar-zoom"', false);
        $response->assertSee('id="lk-zoom-fill"', false);
        $response->assertSee('max="500"', false);
        $response->assertDontSee('id="mx-ml-btn-curriculum"', false);
        $response->assertSee('id="btn-wb-popup-open"', false);
        $response->assertSee('classroom-whiteboard-sync', false);
        $response->assertSee('MxClassroomWhiteboardSync', false);
        $response->assertSee('__mxWbSyncOptions', false);
        $response->assertSee('z-index: 100120', false);
        $response->assertDontSee('id="hstk-classroom-toggle-guest-wb"', false);
        $response->assertDontSee('id="hstk-record-dd-wrap"', false);
        $response->assertDontSee('id="btn-record-menu"', false);
        $response->assertDontSee('id="mx-end-meeting-form"', false);
        $response->assertDontSee('عرض منهج', false);
    }

    public function test_student_room_status_reflects_instructor_end(): void
    {
        [$instructor, $student, $meeting] = $this->seedOneToOneMeeting();

        $this->actingAs($student)
            ->getJson(route('student.classroom.room.status', $meeting))
            ->assertOk()
            ->assertJson(['ended' => false, 'started' => true]);

        $this->actingAs($instructor)
            ->post(route('instructor.classroom.end', $meeting))
            ->assertRedirect();

        $this->actingAs($student)
            ->getJson(route('student.classroom.room.status', $meeting))
            ->assertOk()
            ->assertJson(['ended' => true]);
    }

    public function test_instructor_room_shows_host_toolbar_controls(): void
    {
        [$instructor, , $meeting] = $this->seedOneToOneMeeting();

        $response = $this->actingAs($instructor)
            ->get(route('instructor.classroom.room', $meeting));

        $response->assertOk();
        $response->assertSee('lk-theme-instructor', false);
        $response->assertSee('max="300"', false);
        $response->assertDontSee('max="500"', false);
        $response->assertSee('id="mx-ml-btn-curriculum"', false);
        $response->assertSee('id="btn-wb-popup-open"', false);
        $response->assertSee('classroom-whiteboard-sync', false);
        $response->assertSee('__mxClassroomWbSync', false);
        $response->assertSee('z-index: 100120', false);
        $response->assertSee('hstk-wb-open', false);
        $response->assertSee('id="mx-end-meeting-form"', false);
        $response->assertSee('عرض منهج', false);
        $response->assertSee('btn-mx-host-share-draw', false);
        $response->assertSee('قلم الشاشة', false);
        $response->assertSee('__mxLkToggleScreenAnnotate', false);
        $response->assertSee('startAnnotatedScreenShare', false);
        $response->assertSee('lk-local-share-placeholder', false);
        $response->assertSee('preferScreenShareQuality', false);
        $response->assertSee('selfBrowserSurface', false);
        $response->assertSee('host_emit', false);
        $response->assertSee('lk-pip-cols-btn', false);
        $response->assertSee('syncFloatingPipExclusive', false);
        $response->assertSee('mxGracefulEndMeeting', false);
        $response->assertSee('scheduleLectureComposite', false);
        $response->assertSee('__mxLkGetRecordCapture', false);
        $response->assertSee('drawLectureCameraGrid', false);
        $response->assertSee('cameraVideos', false);
        $response->assertSee('التسجيل يعمل — جاري التقاط الغرفة', false);
        $response->assertSee('mxBuildLectureAudioTrack', false);
        $response->assertSee('الكاميرات', false);
        $response->assertDontSee('id="btn-record-menu"', false);
        $response->assertDontSee('id="hstk-record-dd-wrap"', false);
        $response->assertDontSee('العودة للوحة', false);
    }
}
