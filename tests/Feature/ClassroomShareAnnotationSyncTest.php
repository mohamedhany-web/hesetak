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

class ClassroomShareAnnotationSyncTest extends TestCase
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
    protected function seedMeeting(): array
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
            'code' => 'ANN'.strtoupper(substr(uniqid(), -4)),
            'room_name' => 'حصتك-ANN',
            'title' => 'حصة رسم',
            'started_at' => now()->subMinutes(2),
            'settings' => ['allow_guest_join' => false, 'allow_participant_whiteboard' => true],
        ]);
        $session->update(['classroom_meeting_id' => $meeting->id]);

        return [$instructor, $student, $meeting];
    }

    public function test_host_drawing_is_visible_to_student_poll(): void
    {
        [$instructor, $student, $meeting] = $this->seedMeeting();

        $this->actingAs($instructor)
            ->postJson(route('instructor.classroom.share-annotation', $meeting), [
                'polylines' => [[[0.1, 0.1], [0.2, 0.2], [0.3, 0.15]]],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->actingAs($student)
            ->getJson(route('student.classroom.share-annotations', $meeting))
            ->assertOk()
            ->assertJsonPath('layers.'.$instructor->id.'.is_host', true)
            ->assertJsonStructure(['layers' => [(string) $instructor->id => ['polylines', 'name', 'ts']]]);
    }

    public function test_student_drawing_is_visible_to_instructor_poll(): void
    {
        [$instructor, $student, $meeting] = $this->seedMeeting();

        $this->actingAs($student)
            ->postJson(route('student.classroom.share-annotation', $meeting), [
                'polylines' => [[[0.4, 0.4], [0.5, 0.6]]],
            ])
            ->assertOk();

        $this->actingAs($instructor)
            ->getJson(route('instructor.classroom.share-annotations', $meeting))
            ->assertOk()
            ->assertJsonPath('layers.'.$student->id.'.name', $student->name);

        $this->assertNotEmpty(Cache::get('mx_share_ann_classroom_'.$meeting->id));
    }
}
