<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Services\OneToOneSessionUnlockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class OneToOneSessionSequentialUnlockTest extends TestCase
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
                $table->string('room_name')->nullable();
                $table->string('title')->nullable();
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
                $table->unsignedBigInteger('student_course_enrollment_id')->nullable();
                $table->foreignId('instructor_id');
                $table->foreignId('student_id');
                $table->unsignedBigInteger('advanced_course_id')->nullable();
                $table->unsignedInteger('session_number')->default(1);
                $table->timestamp('scheduled_at')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(50);
                $table->string('status', 32)->default('scheduled');
                $table->unsignedBigInteger('classroom_meeting_id')->nullable();
                $table->string('series_id', 36)->nullable();
                $table->timestamp('student_unlocked_at')->nullable();
                $table->unsignedBigInteger('student_unlocked_by_user_id')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('one_to_one_sessions', 'student_unlocked_at')) {
            Schema::table('one_to_one_sessions', function (Blueprint $table) {
                $table->timestamp('student_unlocked_at')->nullable();
                $table->unsignedBigInteger('student_unlocked_by_user_id')->nullable();
            });
        }

        config([
            'livekit.livekit.api_key' => 'APItestkey',
            'livekit.livekit.api_secret' => 'test-secret-value-1234567890',
            'livekit.livekit.url' => 'wss://live.glottical.com',
            'livekit.livekit.host' => 'live.glottical.com',
        ]);
    }

    public function test_student_can_only_join_current_session_in_series(): void
    {
        [$student, $instructor, $admin] = $this->users();
        $seriesId = 'series-test-1';

        $session1 = $this->scheduledSession($student, $instructor, 1, $seriesId);
        $session2 = $this->scheduledSession($student, $instructor, 2, $seriesId);

        $this->assertTrue(OneToOneSessionUnlockService::canStudentJoin($session1, $student));
        $this->assertFalse(OneToOneSessionUnlockService::canStudentJoin($session2, $student));

        $this->actingAs($student)
            ->get(route('classroom.secure-enter', $session2->classroomMeeting))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('classroom.secure-enter', $session1->classroomMeeting))
            ->assertRedirect();
    }

    public function test_next_session_opens_after_previous_completed(): void
    {
        [$student, $instructor] = $this->users();
        $seriesId = 'series-test-2';

        $session1 = $this->scheduledSession($student, $instructor, 1, $seriesId);
        $session2 = $this->scheduledSession($student, $instructor, 2, $seriesId);

        $session1->update(['status' => OneToOneSession::STATUS_COMPLETED]);

        $this->assertTrue(OneToOneSessionUnlockService::canStudentJoin($session2->fresh(), $student));
        $this->assertFalse(OneToOneSessionUnlockService::canStudentJoin($session1->fresh(), $student));
    }

    public function test_admin_manual_unlock_allows_early_join(): void
    {
        [$student, $instructor, $admin] = $this->users();
        $seriesId = 'series-test-3';

        $session1 = $this->scheduledSession($student, $instructor, 1, $seriesId);
        $session2 = $this->scheduledSession($student, $instructor, 2, $seriesId);

        OneToOneSessionUnlockService::adminUnlockForStudent($session2, $admin);

        $session2->refresh();
        $this->assertNotNull($session2->student_unlocked_at);
        $this->assertTrue(OneToOneSessionUnlockService::canStudentJoin($session2, $student));

        $this->actingAs($student)
            ->get(route('classroom.secure-enter', $session2->classroomMeeting))
            ->assertRedirect();
    }

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    private function users(): array
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);

        return [$student, $instructor, $admin];
    }

    private function scheduledSession(User $student, User $instructor, int $number, string $seriesId): OneToOneSession
    {
        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => $number,
            'series_id' => $seriesId,
            'scheduled_at' => now()->addHours($number),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
        ]);

        $code = 'SER'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).substr(md5($seriesId), 0, 8);
        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => $code,
            'room_name' => 'حصتك-'.$code,
            'title' => 'Session '.$number,
            'max_participants' => 4,
            'settings' => ['allow_guest_join' => false, 'private_lesson' => true],
        ]);

        $session->update(['classroom_meeting_id' => $meeting->id]);

        return $session->fresh(['classroomMeeting']);
    }
}
