<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\Notification;
use App\Models\OneToOneSession;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupCohort;
use App\Models\User;
use App\Services\StudentScheduleService;
use App\Services\TutoringClassService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class StudentHomeScheduleTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();
        $this->createExtraTables();
    }

    public function test_week_calendar_includes_private_slots(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'code' => 'ABCD1234',
            'room_name' => 'room-test',
            'title' => 'Private',
            'scheduled_for' => now()->next(Carbon::SATURDAY)->setTime(17, 0),
        ]);

        $sat = now()->startOfWeek(Carbon::SATURDAY)->setTime(17, 0);
        OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => $sat,
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'classroom_meeting_id' => $meeting->id,
        ]);

        $days = StudentScheduleService::weekDays($student);
        $this->assertCount(7, $days);
        $all = $days->flatMap->items;
        $this->assertTrue($all->contains(fn ($i) => $i->type === 'private'));
    }

    public function test_join_private_and_class_redirects(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true, 'password' => Hash::make('x')]);
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'code' => 'JOIN9999',
            'room_name' => 'room-join',
            'title' => 'Join',
        ]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'classroom_meeting_id' => $meeting->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.schedule.join', ['type' => 'private', 'id' => $session->id]))
            ->assertRedirect(route('classroom.secure-enter', $meeting));

        $group = TutoringGroup::create([
            'type' => TutoringGroup::TYPE_COLLECTIVE,
            'title' => 'فصل',
            'slug' => 'g2-'.uniqid(),
            'instructor_id' => $instructor->id,
            'price' => 0,
            'capacity' => 10,
            'duration_minutes' => 60,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $cohort = TutoringGroupCohort::create([
            'tutoring_group_id' => $group->id,
            'title' => 'دفعة',
            'slug' => 'c2-'.uniqid(),
            'starts_at' => now(),
            'study_days' => [6],
            'study_time' => '18:00',
            'sessions_count' => 1,
            'session_duration_minutes' => 60,
            'timezone' => 'Africa/Cairo',
            'capacity' => 10,
            'enrolled_count' => 0,
            'min_enrollment' => 1,
            'status' => TutoringGroupCohort::STATUS_OPEN,
            'is_visible' => true,
            'sort_order' => 0,
        ]);
        TutoringClassService::enrollStudent($cohort, $student, countSeat: false);
        $class = TutoringClassSession::create([
            'tutoring_group_cohort_id' => $cohort->id,
            'tutoring_group_id' => $group->id,
            'session_number' => 1,
            'title' => 'حصة',
            'starts_at' => now()->addMinutes(5),
            'ends_at' => now()->addMinutes(65),
            'status' => TutoringClassSession::STATUS_SCHEDULED,
        ]);

        $this->actingAs($student)
            ->get(route('student.schedule.join', ['type' => 'class', 'id' => $class->id]))
            ->assertRedirect();

        $this->assertNotNull($class->fresh()->classroom_meeting_id);
    }

    public function test_reminder_creates_notification_once(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);

        OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->addMinutes(30),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
        ]);

        $sent = StudentScheduleService::sendUpcomingReminders(30);
        $this->assertSame(1, $sent);
        $this->assertSame(1, Notification::query()->where('user_id', $student->id)->where('type', 'reminder')->count());

        $sentAgain = StudentScheduleService::sendUpcomingReminders(30);
        $this->assertSame(0, $sentAgain);
    }

    public function test_library_routes_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('student.library.materials'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('student.library.videos'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('student.library.curriculum'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('student.library.home'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('student.lectures.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('student.schedule.join'));
    }

    public function test_student_ui_course_flags_follow_config(): void
    {
        // بدون تسجيل نشط: يتبع الإعداد (كورسات مفعّلة في حصتك، مكتبات مرجعية معطّلة)
        $this->assertTrue(student_ui('show_courses'));
        $this->assertTrue(student_ui('show_exams'));
        $this->assertFalse(student_ui('show_libraries'));
    }

    protected function createExtraTables(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('one_to_one_sessions')) {
            \Illuminate\Support\Facades\Schema::create('one_to_one_sessions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('student_course_enrollment_id')->nullable();
                $table->unsignedBigInteger('student_service_entitlement_id')->nullable();
                $table->unsignedBigInteger('advanced_course_id')->nullable();
                $table->foreignId('instructor_id');
                $table->foreignId('student_id');
                $table->unsignedInteger('session_number')->default(1);
                $table->timestamp('scheduled_at')->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->boolean('is_private_lecture')->default(false);
                $table->string('system_channel')->nullable();
                $table->string('status')->default('scheduled');
                $table->foreignId('classroom_meeting_id')->nullable();
                $table->unsignedBigInteger('booked_by_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ensure notifications has data json
        if (\Illuminate\Support\Facades\Schema::hasTable('notifications')
            && ! \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'data')) {
            \Illuminate\Support\Facades\Schema::table('notifications', function ($table) {
                $table->json('data')->nullable();
            });
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('notifications')
            && ! \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'is_read')) {
            \Illuminate\Support\Facades\Schema::table('notifications', function ($table) {
                $table->boolean('is_read')->default(false);
            });
        }
    }
}
