<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ClassroomWhiteboardSyncTest extends TestCase
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
    }

    /**
     * @return array{0: User, 1: User, 2: ClassroomMeeting}
     */
    protected function seedMeeting(bool $allowParticipant = false): array
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true, 'password' => Hash::make('x')]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true, 'password' => Hash::make('x')]);
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
            'code' => 'WB'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-WB',
            'title' => 'حصة سبورة',
            'started_at' => now()->subMinutes(2),
            'settings' => [
                'allow_guest_join' => false,
                'allow_participant_whiteboard' => $allowParticipant,
            ],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        return [$instructor, $student, $meeting];
    }

    public function test_host_whiteboard_snapshot_is_visible_to_student(): void
    {
        [$instructor, $student, $meeting] = $this->seedMeeting();

        $elements = [[
            'id' => 'el1',
            'type' => 'rectangle',
            'x' => 10,
            'y' => 20,
            'width' => 100,
            'height' => 40,
            'version' => 1,
            'versionNonce' => 11,
            'isDeleted' => false,
        ]];

        $this->actingAs($instructor)
            ->postJson(route('instructor.classroom.whiteboard.push', $meeting), [
                'v' => 1001,
                'elements' => $elements,
                'appState' => ['viewBackgroundColor' => '#ffffff'],
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'v' => 1001]);

        $this->actingAs($student)
            ->getJson(route('student.classroom.whiteboard.state', $meeting))
            ->assertOk()
            ->assertJsonPath('v', 1001)
            ->assertJsonPath('elements.0.id', 'el1')
            ->assertJsonPath('is_host', true);
    }

    public function test_student_cannot_push_whiteboard_when_not_allowed(): void
    {
        [, $student, $meeting] = $this->seedMeeting(allowParticipant: false);

        $this->actingAs($student)
            ->postJson(route('student.classroom.whiteboard.push', $meeting), [
                'v' => 5,
                'elements' => [['id' => 'x', 'type' => 'freedraw', 'version' => 1, 'versionNonce' => 1, 'isDeleted' => false]],
            ])
            ->assertStatus(422);
    }

    public function test_student_can_push_whiteboard_when_allowed(): void
    {
        [$instructor, $student, $meeting] = $this->seedMeeting(allowParticipant: true);

        $this->actingAs($student)
            ->postJson(route('student.classroom.whiteboard.push', $meeting), [
                'v' => 42,
                'elements' => [['id' => 's1', 'type' => 'freedraw', 'version' => 2, 'versionNonce' => 3, 'isDeleted' => false]],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->actingAs($instructor)
            ->getJson(route('instructor.classroom.whiteboard.state', $meeting))
            ->assertOk()
            ->assertJsonPath('elements.0.id', 's1');
    }

    public function test_older_version_does_not_overwrite_newer_snapshot(): void
    {
        [$instructor, , $meeting] = $this->seedMeeting();

        Cache::put('mx_wb_classroom_'.$meeting->id, [
            'v' => 500,
            'elements' => [['id' => 'keep', 'type' => 'text', 'version' => 1, 'versionNonce' => 1, 'isDeleted' => false]],
            'appState' => null,
            'files' => null,
            'ts' => now()->timestamp,
        ], now()->addHour());

        $this->actingAs($instructor)
            ->postJson(route('instructor.classroom.whiteboard.push', $meeting), [
                'v' => 100,
                'elements' => [['id' => 'old', 'type' => 'text', 'version' => 1, 'versionNonce' => 1, 'isDeleted' => false]],
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'skipped' => true]);

        $this->actingAs($instructor)
            ->getJson(route('instructor.classroom.whiteboard.state', $meeting))
            ->assertOk()
            ->assertJsonPath('elements.0.id', 'keep');
    }
}
