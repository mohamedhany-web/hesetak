<?php

namespace Tests\Feature;

use App\Models\ClassroomMeeting;
use App\Models\LiveSession;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Services\StudentLessonCleanupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsFeatureSchema;
use Tests\TestCase;

class StudentLessonCleanupTest extends TestCase
{
    use BuildsFeatureSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFeatureSchema();

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
                $table->string('series_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('one_to_one_sessions', 'notes')) {
            Schema::table('one_to_one_sessions', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }

        if (! Schema::hasTable('classroom_meetings')) {
            Schema::create('classroom_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('one_to_one_session_id')->nullable();
                $table->string('code', 32)->unique();
                $table->string('room_name', 64)->nullable();
                $table->string('title')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tutoring_group_bookings')) {
            Schema::create('tutoring_group_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('instructor_id')->nullable();
                $table->unsignedBigInteger('tutoring_group_id')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('student_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('live_sessions')) {
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
                $table->json('settings')->nullable();
                $table->boolean('require_enrollment')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('session_attendance')) {
            Schema::create('session_attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id');
                $table->foreignId('user_id');
                $table->timestamps();
            });
        }
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
    }

    public function test_admin_cleanup_index_lists_experimental_one_to_one(): void
    {
        $admin = $this->admin();
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'notes' => 'تسكين يدوي من الإدارة — تجريب',
        ]);

        $this->withoutMiddleware()
            ->actingAs($admin)
            ->get(route('admin.student-lesson-cleanup.index', [
                'type' => 'one_to_one',
                'experimental' => 1,
            ]))
            ->assertOk()
            ->assertSee('تنظيف الحصص والبيانات التجريبية', false)
            ->assertSee('تسكين يدوي من الإدارة', false);
    }

    public function test_purge_one_to_one_removes_linked_classroom_meeting(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $meeting = ClassroomMeeting::create([
            'user_id' => $instructor->id,
            'code' => 'CLEAN01',
            'room_name' => 'حصتك-CLEAN01',
            'title' => 'حصة تجريبية',
            'started_at' => now(),
        ]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 50,
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'classroom_meeting_id' => $meeting->id,
            'notes' => 'تجريب',
        ]);
        $meeting->update(['one_to_one_session_id' => $session->id]);

        $this->assertSame(1, StudentLessonCleanupService::purgeOneToOne($session));
        $this->assertDatabaseMissing('one_to_one_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('classroom_meetings', ['id' => $meeting->id]);
    }

    public function test_purge_live_session_and_bulk_one_to_one(): void
    {
        $admin = $this->admin();
        $live = LiveSession::create([
            'instructor_id' => $admin->id,
            'title' => 'بث إداري — تجريب',
            'description' => 'جلسة مباشرة أنشأتها الإدارة للانضمام الفوري.',
            'room_name' => 'admin-clean-room',
            'status' => 'ended',
            'settings' => ['admin_only' => true],
        ]);

        StudentLessonCleanupService::purgeLiveSession($live);
        $this->assertDatabaseMissing('live_sessions', ['id' => $live->id]);

        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $a = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'status' => OneToOneSession::STATUS_CANCELLED,
            'notes' => 'test',
        ]);
        $b = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 2,
            'status' => OneToOneSession::STATUS_COMPLETED,
            'notes' => 'test',
        ]);

        $this->withoutMiddleware()
            ->actingAs($admin)
            ->post(route('admin.student-lesson-cleanup.bulk'), [
                'type' => 'one_to_one',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('one_to_one_sessions', ['id' => $a->id]);
        $this->assertDatabaseMissing('one_to_one_sessions', ['id' => $b->id]);
    }

    public function test_single_row_destroy_uses_post_not_delete_route(): void
    {
        $admin = $this->admin();
        $instructor = User::factory()->create(['role' => 'instructor', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $session = OneToOneSession::create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'session_number' => 1,
            'status' => OneToOneSession::STATUS_CANCELLED,
            'notes' => 'تجريب',
        ]);

        $this->withoutMiddleware()
            ->actingAs($admin)
            ->from(route('admin.student-lesson-cleanup.index'))
            ->post(route('admin.student-lesson-cleanup.destroy-one-to-one', $session))
            ->assertRedirect();

        $this->assertDatabaseMissing('one_to_one_sessions', ['id' => $session->id]);
    }
}
