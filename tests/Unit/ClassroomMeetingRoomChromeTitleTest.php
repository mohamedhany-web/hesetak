<?php

namespace Tests\Unit;

use App\Models\ClassroomMeeting;
use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class ClassroomMeetingRoomChromeTitleTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();

        if (! Schema::hasTable('classroom_meetings')) {
            Schema::create('classroom_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->unsignedBigInteger('consultation_request_id')->nullable();
                $table->string('code', 32)->unique();
                $table->string('room_name', 64)->nullable();
                $table->string('title')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('one_to_one_sessions')) {
            Schema::create('one_to_one_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instructor_id');
                $table->foreignId('student_id');
                $table->string('status', 32)->default('scheduled');
                $table->unsignedBigInteger('classroom_meeting_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_room_chrome_title_strips_student_name_from_one_to_one(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'mohamed hany']);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'status' => OneToOneSession::STATUS_SCHEDULED,
        ]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'one_to_one_session_id' => $session->id,
            'code' => 'TITLE001',
            'room_name' => 'حصتك-TITLE001',
            'title' => 'حصة 1:1: كورس فردي — mohamed hany',
        ]);

        $this->assertSame('حصة 1:1: كورس فردي', $meeting->roomChromeTitle());
        $this->assertStringNotContainsString('mohamed hany', $meeting->roomChromeTitle());
    }

    public function test_legacy_one_to_one_title_without_course_becomes_generic(): void
    {
        $meeting = new ClassroomMeeting([
            'code' => 'TITLE002',
            'title' => 'حصة 1:1 — طالب قديم',
        ]);

        $this->assertSame('حصة 1:1', $meeting->roomChromeTitle());
    }
}
