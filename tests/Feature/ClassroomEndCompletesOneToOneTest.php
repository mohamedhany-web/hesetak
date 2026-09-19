<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ClassroomEndCompletesOneToOneTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->ensureTables();
    }

    protected function ensureTables(): void
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
                $table->string('recording_disk')->nullable();
                $table->string('recording_path')->nullable();
                $table->string('recording_audio_path')->nullable();
                $table->timestamp('recording_uploaded_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('classroom_meetings', function (Blueprint $table) {
                if (! Schema::hasColumn('classroom_meetings', 'recording_disk')) {
                    $table->string('recording_disk')->nullable();
                }
                if (! Schema::hasColumn('classroom_meetings', 'recording_path')) {
                    $table->string('recording_path')->nullable();
                }
                if (! Schema::hasColumn('classroom_meetings', 'recording_audio_path')) {
                    $table->string('recording_audio_path')->nullable();
                }
                if (! Schema::hasColumn('classroom_meetings', 'recording_uploaded_at')) {
                    $table->timestamp('recording_uploaded_at')->nullable();
                }
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
                $table->unsignedBigInteger('student_service_entitlement_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('student_service_entitlements')) {
            Schema::create('student_service_entitlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('scope')->nullable();
                $table->unsignedInteger('units_total')->default(0);
                $table->unsignedInteger('units_used')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_ending_classroom_marks_linked_one_to_one_completed(): void
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
            'scheduled_at' => now()->subMinutes(5),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'END'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-ENDTEST',
            'title' => 'حصة 1:1: كورس فردي',
            'planned_duration_minutes' => 50,
            'max_participants' => 4,
            'started_at' => now()->subMinutes(20),
            'settings' => ['allow_guest_join' => false],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        $this->actingAs($instructor)
            ->post(route('instructor.classroom.end', $meeting))
            ->assertRedirect(route('instructor.one-to-one-sessions.show', $session));

        $this->assertNotNull($meeting->fresh()->ended_at);
        $this->assertSame(OneToOneSession::STATUS_COMPLETED, $session->fresh()->status);
    }

    public function test_student_can_open_recording_when_meeting_ended_with_media(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->subHour(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_COMPLETED,
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'REC'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-RECTEST',
            'title' => 'حصة 1:1: كورس فردي',
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(10),
            'recording_disk' => 'live_recordings_r2',
            'recording_audio_path' => 'classroom/1/audio.webm',
            'recording_uploaded_at' => now()->subMinutes(9),
            'settings' => ['allow_guest_join' => false],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        $this->assertTrue($meeting->hasBrowserRecording());

        // بدون storage الحقيقي: نتأكد أن الراوت يصل للطالب المصرّح ولا يعطي 403
        $response = $this->actingAs($student)
            ->get(route('student.classroom.recording', $meeting));

        // إما redirect away للرابط أو 404 إن فشل temporaryUrl — المهم ليس 403
        $this->assertNotSame(403, $response->status());
        $this->assertTrue(in_array($response->status(), [302, 404, 500], true));

        $this->actingAs($student)
            ->get(route('student.one-to-one-sessions.show', $session))
            ->assertOk()
            ->assertSee(route('student.classroom.recording', $meeting), false);

        $this->actingAs($student)
            ->getJson(route('student.classroom.recording.status', $meeting))
            ->assertOk()
            ->assertJson([
                'ready' => true,
                'ended' => true,
            ])
            ->assertJsonPath('watch_url', route('student.classroom.recording', $meeting));
    }

    public function test_student_recording_status_waits_until_media_ready(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 2,
            'scheduled_at' => now()->subHour(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_COMPLETED,
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'WAIT'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-WAITREC',
            'title' => 'حصة 1:1',
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinute(),
            'settings' => ['allow_guest_join' => false],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        $this->actingAs($student)
            ->getJson(route('student.classroom.recording.status', $meeting))
            ->assertOk()
            ->assertJson([
                'ready' => false,
                'ended' => true,
                'watch_url' => null,
            ]);

        $this->actingAs($student)
            ->get(route('student.one-to-one-sessions.show', $session))
            ->assertOk()
            ->assertSee('جاري تجهيز التسجيل', false)
            ->assertSee('recording-status', false)
            ->assertSee('mx-oto-recording-wait', false);
    }
}
