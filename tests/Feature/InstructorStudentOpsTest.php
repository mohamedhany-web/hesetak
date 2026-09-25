<?php

namespace Tests\Feature;

use App\Models\FreeTrialBooking;
use App\Models\OneToOneSession;
use App\Models\OneToOneSessionRating;
use App\Models\User;
use App\Services\OneToOneSessionRatingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class InstructorStudentOpsTest extends TestCase
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
        if (! Schema::hasTable('one_to_one_sessions')) {
            Schema::create('one_to_one_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_course_enrollment_id')->nullable();
                $table->unsignedBigInteger('student_service_entitlement_id')->nullable();
                $table->unsignedBigInteger('advanced_course_id')->nullable();
                $table->foreignId('instructor_id');
                $table->foreignId('student_id');
                $table->unsignedInteger('session_number')->default(1);
                $table->timestamp('scheduled_at')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(50);
                $table->string('status', 32)->default('scheduled');
                $table->unsignedBigInteger('classroom_meeting_id')->nullable();
                $table->boolean('is_complimentary')->default(false);
                $table->unsignedBigInteger('booked_by_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('report_required_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('one_to_one_sessions', function (Blueprint $table) {
                foreach ([
                    'student_course_enrollment_id' => 'unsignedBigInteger',
                    'advanced_course_id' => 'unsignedBigInteger',
                    'booked_by_user_id' => 'unsignedBigInteger',
                ] as $col => $_) {
                    if (! Schema::hasColumn('one_to_one_sessions', $col)) {
                        $table->unsignedBigInteger($col)->nullable();
                    }
                }
                if (! Schema::hasColumn('one_to_one_sessions', 'is_complimentary')) {
                    $table->boolean('is_complimentary')->default(false);
                }
                if (! Schema::hasColumn('one_to_one_sessions', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (! Schema::hasColumn('one_to_one_sessions', 'report_required_at')) {
                    $table->timestamp('report_required_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('classroom_meetings')) {
            Schema::create('classroom_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->string('code', 32)->nullable();
                $table->string('room_name', 64)->nullable();
                $table->string('title')->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->unsignedInteger('planned_duration_minutes')->nullable();
                $table->unsignedInteger('max_participants')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('advanced_courses')) {
            Schema::create('advanced_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instructor_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('delivery_type')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('free_trial_bookings')) {
            Schema::create('free_trial_bookings', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('country_code')->nullable();
                $table->string('goal')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('instructor_id')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(30);
                $table->string('status', 32)->default('pending');
                $table->text('notes')->nullable();
                $table->string('timezone')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('one_to_one_weekly_availability')) {
            Schema::create('one_to_one_weekly_availability', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instructor_id');
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('one_to_one_session_ratings')) {
            Schema::create('one_to_one_session_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('one_to_one_session_id')->unique();
                $table->foreignId('student_id');
                $table->foreignId('instructor_id');
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('sender_id')->nullable();
                $table->string('type')->nullable();
                $table->string('title')->nullable();
                $table->text('message')->nullable();
                $table->string('action_url')->nullable();
                $table->string('action_text')->nullable();
                $table->string('priority')->nullable();
                $table->string('audience')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function test_instructor_can_accept_and_reject_pending_free_trial(): void
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser('student');

        $starts = now()->addDays(2)->startOfHour()->addHours(2);

        // قبول التجربة لا يشترط توافر أسبوعي منشور مسبقاً.
        $this->assertFalse(
            \App\Models\OneToOneWeeklyAvailability::query()
                ->where('instructor_id', $instructor->id)
                ->exists()
        );

        $pending = FreeTrialBooking::query()->create([
            'name' => $student->name,
            'email' => $student->email,
            'goal' => FreeTrialBooking::GOAL_TRIAL,
            'user_id' => $student->id,
            'instructor_id' => $instructor->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => FreeTrialBooking::STATUS_PENDING,
            'notes' => 'طلب تنسيق',
        ]);

        $response = $this->actingAs($instructor)
            ->from(route('instructor.free-trial-bookings.show', $pending))
            ->post(route('instructor.free-trial-bookings.accept', $pending), [
                'starts_at' => $starts->toDateTimeString(),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $pending->refresh();
        $this->assertSame(FreeTrialBooking::STATUS_CONFIRMED, $pending->status);
        $this->assertNotNull($pending->one_to_one_session_id);

        $rejectable = FreeTrialBooking::query()->create([
            'name' => 'Guest',
            'email' => 'guest-trial@example.com',
            'goal' => FreeTrialBooking::GOAL_TRIAL,
            'instructor_id' => $instructor->id,
            'starts_at' => $starts->copy()->addDay(),
            'ends_at' => $starts->copy()->addDay()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => FreeTrialBooking::STATUS_PENDING,
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.free-trial-bookings.reject', $rejectable), [
                'reason' => 'مشغول',
            ])
            ->assertRedirect(route('instructor.free-trial-bookings.index'));

        $this->assertSame(FreeTrialBooking::STATUS_CANCELLED, $rejectable->fresh()->status);
    }

    public function test_instructor_notification_go_reaches_free_trial_show_not_student_route(): void
    {
        $instructor = $this->makeUser('instructor');
        $booking = FreeTrialBooking::query()->create([
            'name' => 'طالب تجريبي',
            'email' => 'trial-bell@example.com',
            'goal' => FreeTrialBooking::GOAL_TRIAL,
            'instructor_id' => $instructor->id,
            'starts_at' => now()->addDays(3)->startOfHour(),
            'ends_at' => now()->addDays(3)->startOfHour()->addMinutes(30),
            'duration_minutes' => 30,
            'status' => FreeTrialBooking::STATUS_PENDING,
        ]);

        $notification = \App\Models\Notification::query()->create([
            'user_id' => $instructor->id,
            'type' => 'general',
            'title' => 'طلب حصة مجانية',
            'message' => 'اختبار جرس المعلم',
            'action_url' => route('instructor.free-trial-bookings.show', $booking),
            'priority' => 'high',
            'audience' => 'instructor',
            'is_read' => false,
        ]);

        $this->actingAs($instructor)
            ->get(route('notifications.go', $notification))
            ->assertRedirect(route('dashboard'));

        $go = $this->actingAs($instructor)
            ->get(route('instructor.notifications.go', $notification));

        $go->assertRedirect(route('instructor.free-trial-bookings.show', $booking));
        $this->assertTrue((bool) $notification->fresh()->is_read);
    }

    public function test_student_must_rate_completed_one_to_one_session(): void
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser('student');

        $session = OneToOneSession::query()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->subHour(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_COMPLETED,
            'is_complimentary' => true,
        ]);

        $this->assertTrue(OneToOneSessionRatingService::studentMustRate($session, $student));

        $this->actingAs($student)
            ->post(route('student.one-to-one-sessions.rate', $session), [
                'rating' => 5,
                'comment' => 'ممتاز',
            ])
            ->assertRedirect(route('student.one-to-one-sessions.show', $session));

        $this->assertDatabaseHas('one_to_one_session_ratings', [
            'one_to_one_session_id' => $session->id,
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'rating' => 5,
        ]);
        $this->assertFalse(OneToOneSessionRatingService::studentMustRate($session->fresh(), $student));
    }

    public function test_consultations_ui_flag_is_hidden_for_students(): void
    {
        $this->assertFalse((bool) config('student_ui.show_consultations'));
        $this->assertTrue(route('consultations.index') !== '');
        $this->assertTrue(route('instructor.consultations.schedule', ['consultation' => 1]) !== '');
    }

    private function makeUser(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role).' '.uniqid(),
            'email' => $role.'.'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
