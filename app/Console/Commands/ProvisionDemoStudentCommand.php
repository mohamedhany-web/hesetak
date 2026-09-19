<?php

namespace App\Console\Commands;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\Assignment;
use App\Models\FreeTrialBooking;
use App\Models\Lecture;
use App\Models\LectureMaterial;
use App\Models\LiveRecording;
use App\Models\LiveSession;
use App\Models\Notification;
use App\Models\OneToOneSession;
use App\Models\Order;
use App\Models\PrivateLessonMessage;
use App\Models\PrivateLessonThread;
use App\Models\ServicePackage;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentInstructorAssignment;
use App\Models\StudentLearningStreak;
use App\Models\StudentServiceEntitlement;
use App\Models\TutoringClassAttendance;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupCohort;
use App\Models\User;
use App\Services\StudentEntitlementService;
use App\Services\StudentSchoolGameService;
use App\Services\TutoringClassService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ProvisionDemoStudentCommand extends Command
{
    protected $signature = 'demo:provision-student
                            {--student=memohany049@gmail.com : Student email}
                            {--instructor=instructor4@mualimx.com : Instructor email}
                            {--password=password123 : Password to set for both accounts}';

    protected $description = 'Provision a full demo student + instructor with enrollments, entitlements, sessions, library, assignments, and messages';

    public function handle(): int
    {
        $studentEmail = strtolower(trim((string) $this->option('student')));
        $instructorEmail = strtolower(trim((string) $this->option('instructor')));
        $password = (string) $this->option('password');

        return DB::transaction(function () use ($studentEmail, $instructorEmail, $password) {
            $student = $this->ensureStudent($studentEmail, $password);
            $instructor = $this->ensureInstructor($instructorEmail, $password);

            $year = AcademicYear::query()->where('slug', 'islamic-foundations-3')->first()
                ?? AcademicYear::query()->orderBy('id')->skip(1)->first()
                ?? AcademicYear::query()->first();
            $subjects = AcademicSubject::query()->orderBy('id')->take(3)->get();
            if (! $year || $subjects->isEmpty()) {
                $this->error('Academic years/subjects missing. Seed SchoolProgramSeeder first.');

                return 1;
            }

            $student->forceFill([
                'name' => $student->name ?: 'Mohamed Hany',
                'is_active' => true,
                'role' => 'student',
            ])->save();

            $package = ServicePackage::query()->where('slug', 'premier-3m')->first()
                ?? ServicePackage::query()->where('plan_type', ServicePackage::PLAN_PREMIER)->where('is_active', true)->first();

            $order = $this->approveOrCreatePremierOrder($student, $package);
            $entitlements = StudentServiceEntitlement::query()
                ->where('user_id', $student->id)
                ->where('status', StudentServiceEntitlement::STATUS_ACTIVE)
                ->get();

            if ($entitlements->isEmpty() && $package) {
                StudentEntitlementService::grantPremierPlan($student->id, $package, $order?->id);
                $entitlements = StudentServiceEntitlement::query()
                    ->where('user_id', $student->id)
                    ->where('status', StudentServiceEntitlement::STATUS_ACTIVE)
                    ->get();
            }

            $groupEntitlement = $entitlements->first(fn ($e) => $e->scope === ServicePackage::SCOPE_TUTORING_COLLECTIVE)
                ?? $entitlements->first();
            $privateEntitlement = $entitlements->first(fn ($e) => $e->scope === ServicePackage::SCOPE_PRIVATE_LESSONS)
                ?? $entitlements->skip(1)->first();

            if ($groupEntitlement && (int) $groupEntitlement->units_used < 2) {
                $groupEntitlement->update(['units_used' => min(2, (int) $groupEntitlement->units_total)]);
            }
            if ($privateEntitlement && (int) $privateEntitlement->units_used < 1) {
                $privateEntitlement->update(['units_used' => min(1, (int) $privateEntitlement->units_total)]);
            }

            $groups = $this->ensureGroups($instructor, $year, $subjects);
            $cohorts = [];
            foreach ($groups as $i => $group) {
                $cohorts[] = $this->ensureCohortWithLife($group, $student, $instructor, $groupEntitlement?->id, $i);
            }

            $assignment = $this->ensureInstructorAssignment($student, $instructor, $year);
            $course = $this->ensureCourseBundle($student, $instructor, $year, $subjects->first());
            $this->ensurePrivateLessons($student, $instructor, $privateEntitlement?->id, $assignment?->id, $course?->id);
            $this->ensureMessages($student, $instructor, $assignment?->id, $course?->id, $groups[0]->id ?? null);
            $this->ensurePlacement($student, $year);
            $this->ensureNotifications($student, $instructor, $cohorts[0] ?? null);
            $this->ensureGameProgress($student, $cohorts[0] ?? null);

            $this->newLine();
            $this->info('Demo student provisioned successfully.');
            $this->table(['Key', 'Value'], [
                ['Student', "{$student->email} (id {$student->id})"],
                ['Instructor', "{$instructor->email} (id {$instructor->id})"],
                ['Password', $password],
                ['Order', $order ? "#{$order->id} / {$order->status}" : 'n/a'],
                ['Entitlements', (string) $entitlements->count()],
                ['Groups', (string) count($groups)],
                ['Cohorts', (string) count($cohorts)],
                ['Course', $course ? "#{$course->id} {$course->title}" : 'n/a'],
                ['Year', $year->name],
            ]);

            return 0;
        });
    }

    private function ensureStudent(string $email, string $password): User
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Mohamed Hany',
                'email' => $email,
                'password' => $password,
                'role' => 'student',
                'is_active' => true,
            ]);
            $this->line("Created student {$email}");
        } else {
            $user->forceFill([
                'password' => $password,
                'role' => 'student',
                'is_active' => true,
            ])->save();
            $this->line("Updated student {$email}");
        }

        return $user->fresh();
    }

    private function ensureInstructor(string $email, string $password): User
    {
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $user = User::create([
                'name' => 'هدى الكويتية',
                'email' => $email,
                'password' => $password,
                'role' => 'instructor',
                'is_active' => true,
            ]);
            $this->line("Created instructor {$email}");
        } else {
            $user->forceFill([
                'password' => $password,
                'role' => 'instructor',
                'is_active' => true,
            ])->save();
            $this->line("Updated instructor {$email}");
        }

        return $user->fresh();
    }

    private function approveOrCreatePremierOrder(User $student, ?ServicePackage $package): ?Order
    {
        $order = Order::query()
            ->where('user_id', $student->id)
            ->where('order_type', Order::TYPE_SERVICE_PACKAGE)
            ->where('service_package_id', $package?->id)
            ->latest('id')
            ->first();

        if (! $order && $package) {
            $order = Order::create([
                'user_id' => $student->id,
                'service_package_id' => $package->id,
                'order_type' => Order::TYPE_SERVICE_PACKAGE,
                'original_amount' => $package->price ?? 0,
                'discount_amount' => 0,
                'amount' => $package->price ?? 0,
                'payment_method' => 'manual',
                'status' => Order::STATUS_PENDING,
                'notes' => 'demo:provision-student',
            ]);
        }

        if (! $order) {
            return null;
        }

        if ($order->status !== Order::STATUS_APPROVED) {
            $order->update([
                'status' => Order::STATUS_APPROVED,
                'approved_at' => now(),
                'notes' => trim(($order->notes ? $order->notes.' | ' : '').'demo:approved'),
            ]);
        }

        $existing = StudentServiceEntitlement::query()->where('order_id', $order->id)->exists();
        if (! $existing && $package) {
            StudentEntitlementService::grantFromOrder($order->fresh());
            $this->line("Granted entitlements from order #{$order->id}");
        }

        return $order->fresh();
    }

    /**
     * @return array<int, TutoringGroup>
     */
    private function ensureGroups(User $instructor, AcademicYear $year, $subjects): array
    {
        $defs = [
            ['slug' => 'demo-hoda-quran', 'title' => 'قرآن وتجويد — مجموعة هدى', 'subject' => $subjects[0] ?? null],
            ['slug' => 'demo-hoda-fiqh', 'title' => 'فقه العبادات — مجموعة هدى', 'subject' => $subjects[2] ?? $subjects[1] ?? $subjects[0] ?? null],
        ];

        $groups = [];
        foreach ($defs as $i => $def) {
            $group = TutoringGroup::query()->where('slug', $def['slug'])->first();
            if (! $group) {
                $group = TutoringGroup::create([
                    'type' => TutoringGroup::TYPE_COLLECTIVE,
                    'title' => $def['title'],
                    'slug' => $def['slug'],
                    'description' => 'فصل تجريبي كامل لاختبار تجربة الطالب.',
                    'instructor_id' => $instructor->id,
                    'academic_year_id' => $year->id,
                    'academic_subject_id' => $def['subject']?->id,
                    'price' => 0,
                    'capacity' => 25,
                    'duration_minutes' => 60,
                    'sessions_per_month' => 8,
                    'is_active' => true,
                    'is_featured' => true,
                    'sort_order' => $i + 1,
                    'learning_path' => TutoringGroup::PATH_ARABIC,
                    'currency' => platform_currency(),
                ]);
                $this->line("Created group {$group->slug}");
            } else {
                $group->update([
                    'instructor_id' => $instructor->id,
                    'academic_year_id' => $year->id,
                    'academic_subject_id' => $def['subject']?->id,
                    'is_active' => true,
                    'title' => $def['title'],
                ]);
            }
            $groups[] = $group->fresh();
        }

        return $groups;
    }

    private function ensureCohortWithLife(
        TutoringGroup $group,
        User $student,
        User $instructor,
        ?int $entitlementId,
        int $index
    ): TutoringGroupCohort {
        $slug = $group->slug.'-cohort-a';
        $studyDay = Carbon::now()->dayOfWeekIso; // 1=Mon ... 7=Sun
        $starts = now()->setTime(18, 0)->addHours($index); // staggered today missions

        $cohort = TutoringGroupCohort::query()->where('slug', $slug)->first();
        if (! $cohort) {
            $cohort = TutoringGroupCohort::create([
                'tutoring_group_id' => $group->id,
                'title' => $group->title.' — الدفعة أ',
                'slug' => $slug,
                'starts_at' => now()->subWeeks(3),
                'study_days' => [$studyDay, (($studyDay + 2 - 1) % 7) + 1],
                'study_time' => sprintf('%02d:00', 17 + $index),
                'sessions_count' => 12,
                'session_duration_minutes' => 60,
                'timezone' => config('app.timezone', 'Africa/Cairo'),
                'capacity' => 25,
                'enrolled_count' => 0,
                'min_enrollment' => 1,
                'status' => TutoringGroupCohort::STATUS_OPEN,
                'is_visible' => true,
                'sort_order' => $index,
            ]);
            $this->line("Created cohort {$cohort->slug}");
        } else {
            $cohort->update([
                'status' => TutoringGroupCohort::STATUS_OPEN,
                'is_visible' => true,
                'study_days' => [$studyDay, (($studyDay + 2 - 1) % 7) + 1],
                'study_time' => sprintf('%02d:00', 17 + $index),
            ]);
        }

        TutoringClassService::enrollStudent(
            cohort: $cohort->fresh(),
            user: $student,
            entitlementId: $entitlementId,
            countSeat: true,
            notes: 'demo:provision-student',
        );

        // Wipe and rebuild a predictable session set for this demo cohort.
        TutoringClassSession::query()
            ->where('tutoring_group_cohort_id', $cohort->id)
            ->where('notes', 'like', 'demo:%')
            ->delete();

        $sessionDefs = [
            ['offset_days' => -14, 'status' => TutoringClassSession::STATUS_COMPLETED, 'attend' => true],
            ['offset_days' => -7, 'status' => TutoringClassSession::STATUS_COMPLETED, 'attend' => true],
            ['offset_days' => -3, 'status' => TutoringClassSession::STATUS_COMPLETED, 'attend' => false],
            ['offset_days' => 0, 'status' => TutoringClassSession::STATUS_SCHEDULED, 'attend' => false, 'hours' => 2 + $index],
            ['offset_days' => 3, 'status' => TutoringClassSession::STATUS_SCHEDULED, 'attend' => false],
            ['offset_days' => 7, 'status' => TutoringClassSession::STATUS_SCHEDULED, 'attend' => false],
        ];

        foreach ($sessionDefs as $n => $def) {
            $start = isset($def['hours'])
                ? now()->addHours((int) $def['hours'])
                : now()->addDays((int) $def['offset_days'])->setTime(17 + $index, 0);
            $session = TutoringClassSession::create([
                'tutoring_group_cohort_id' => $cohort->id,
                'tutoring_group_id' => $group->id,
                'session_number' => $n + 1,
                'title' => $group->title.' — حصة '.($n + 1),
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(60),
                'status' => $def['status'],
                'notes' => 'demo:session',
            ]);

            if (! empty($def['attend'])) {
                TutoringClassAttendance::updateOrCreate(
                    [
                        'tutoring_class_session_id' => $session->id,
                        'user_id' => $student->id,
                    ],
                    [
                        'status' => TutoringClassAttendance::STATUS_PRESENT,
                        'joined_at' => $start->copy()->addMinutes(2),
                        'left_at' => $start->copy()->addMinutes(55),
                        'notes' => 'demo',
                    ]
                );
                try {
                    StudentSchoolGameService::awardAttendance($session, $student, TutoringClassAttendance::STATUS_PRESENT);
                } catch (\Throwable $e) {
                    // ignore if gamification tables/partial
                }
            }
        }

        TutoringClassService::syncEnrolledCount($cohort->fresh());

        return $cohort->fresh();
    }

    private function ensureInstructorAssignment(User $student, User $instructor, AcademicYear $year): StudentInstructorAssignment
    {
        $row = StudentInstructorAssignment::query()
            ->where('student_id', $student->id)
            ->where('instructor_id', $instructor->id)
            ->where('scope', StudentInstructorAssignment::SCOPE_GENERAL)
            ->first();

        if (! $row) {
            $row = StudentInstructorAssignment::create([
                'student_id' => $student->id,
                'instructor_id' => $instructor->id,
                'academic_year_id' => $year->id,
                'scope' => StudentInstructorAssignment::SCOPE_GENERAL,
                'status' => StudentInstructorAssignment::STATUS_ACTIVE,
                'notes' => 'demo:provision-student',
                'starts_at' => now()->subWeeks(2),
                'instructor_notified_at' => now(),
                'student_notified_at' => now(),
            ]);
        } else {
            $row->update([
                'status' => StudentInstructorAssignment::STATUS_ACTIVE,
                'academic_year_id' => $year->id,
                'starts_at' => $row->starts_at ?: now()->subWeeks(2),
            ]);
        }

        return $row->fresh();
    }

    private function ensureCourseBundle(User $student, User $instructor, AcademicYear $year, ?AcademicSubject $subject): ?AdvancedCourse
    {
        $course = AdvancedCourse::query()->where('title', 'تجربة المنصة — مسار هدى')->first();
        if (! $course) {
            $course = AdvancedCourse::create([
                'title' => 'تجربة المنصة — مسار هدى',
                'instructor_id' => $instructor->id,
                'teacher_id' => $instructor->id,
                'academic_year_id' => $year->id,
                'academic_subject_id' => $subject?->id,
                'description' => 'كورس تجريبي لمكتبة المواد والواجبات والمحاضرات.',
                'level' => 'beginner',
                'duration_hours' => 12,
                'price' => 0,
                'is_active' => true,
                'is_featured' => true,
                'is_free' => true,
                'language' => 'ar',
                'delivery_type' => 'recorded',
                'billing_mode' => 'one_time',
            ]);
            $this->line("Created course #{$course->id}");
        } else {
            $course->update([
                'instructor_id' => $instructor->id,
                'teacher_id' => $instructor->id,
                'academic_year_id' => $year->id,
                'academic_subject_id' => $subject?->id,
                'is_active' => true,
            ]);
        }

        // Also attach one existing public course for richer catalog testing.
        foreach ([3, 10] as $existingId) {
            $existing = AdvancedCourse::query()->find($existingId);
            if (! $existing) {
                continue;
            }
            StudentCourseEnrollment::updateOrCreate(
                [
                    'user_id' => $student->id,
                    'advanced_course_id' => $existing->id,
                ],
                [
                    'enrolled_at' => now()->subDays(10),
                    'activated_at' => now()->subDays(10),
                    'status' => 'active',
                    'progress' => 35,
                    'enrollment_type' => 'gift',
                    'access_type' => 'lifetime',
                    'expires_at' => now()->addMonths(6),
                    'notes' => 'demo:provision-student',
                ]
            );
        }

        StudentCourseEnrollment::updateOrCreate(
            [
                'user_id' => $student->id,
                'advanced_course_id' => $course->id,
            ],
            [
                'enrolled_at' => now()->subDays(12),
                'activated_at' => now()->subDays(12),
                'status' => 'active',
                'progress' => 48,
                'enrollment_type' => 'subscription',
                'access_type' => 'subscription',
                'expires_at' => now()->addMonths(3),
                'notes' => 'demo:provision-student',
            ]
        );

        $lecture = Lecture::query()
            ->where('course_id', $course->id)
            ->where('title', 'محاضرة تجريبية 1 — مقدمة المسار')
            ->first();

        if (! $lecture) {
            $lecture = Lecture::create([
                'course_id' => $course->id,
                'instructor_id' => $instructor->id,
                'title' => 'محاضرة تجريبية 1 — مقدمة المسار',
                'description' => 'محاضرة مسجّلة لاختبار المكتبة.',
                'recording_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'video_platform' => 'youtube',
                'scheduled_at' => now()->subDays(5),
                'duration_minutes' => 45,
                'status' => 'completed',
                'has_attendance_tracking' => true,
                'has_assignment' => true,
            ]);
        }

        Lecture::updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'محاضرة تجريبية 2 — تطبيق عملي',
            ],
            [
                'instructor_id' => $instructor->id,
                'description' => 'محاضرة قادمة.',
                'scheduled_at' => now()->addDays(2)->setTime(19, 0),
                'duration_minutes' => 50,
                'status' => 'scheduled',
            ]
        );

        if (Schema::hasTable('lecture_materials')) {
            LectureMaterial::updateOrCreate(
                [
                    'lecture_id' => $lecture->id,
                    'title' => 'ملخص الدرس PDF',
                ],
                [
                    'file_name' => 'lesson-summary.pdf',
                    'file_path' => 'demo/lesson-summary.pdf',
                    'is_visible_to_student' => true,
                    'sort_order' => 1,
                ]
            );
            LectureMaterial::updateOrCreate(
                [
                    'lecture_id' => $lecture->id,
                    'title' => 'ورقة عمل',
                ],
                [
                    'file_name' => 'worksheet.pdf',
                    'file_path' => 'demo/worksheet.pdf',
                    'is_visible_to_student' => true,
                    'sort_order' => 2,
                ]
            );
        }

        Assignment::updateOrCreate(
            [
                'advanced_course_id' => $course->id,
                'title' => 'واجب تجريبي — تلخيص الحصة',
            ],
            [
                'course_id' => null,
                'teacher_id' => $instructor->id,
                'description' => 'لخّص أهم 5 نقاط من المحاضرة الأولى.',
                'instructions' => 'أرسل ملفاً PDF أو نصاً قصيراً.',
                'due_date' => now()->addDays(5),
                'max_score' => 100,
                'allow_late_submission' => true,
                'status' => 'published',
            ]
        );

        Assignment::updateOrCreate(
            [
                'advanced_course_id' => $course->id,
                'title' => 'واجب قصير — أسئلة مراجعة',
            ],
            [
                'course_id' => null,
                'teacher_id' => $instructor->id,
                'description' => 'أجب عن 3 أسئلة مراجعة.',
                'due_date' => now()->addDays(10),
                'max_score' => 50,
                'allow_late_submission' => false,
                'status' => 'published',
            ]
        );

        if (Schema::hasTable('live_sessions') && Schema::hasTable('live_recordings')) {
            $live = LiveSession::query()
                ->where('course_id', $course->id)
                ->where('title', 'بث تجريبي — مراجعة أسبوعية')
                ->first();

            if (! $live) {
                $live = LiveSession::create([
                    'course_id' => $course->id,
                    'instructor_id' => $instructor->id,
                    'title' => 'بث تجريبي — مراجعة أسبوعية',
                    'description' => 'جلسة مسجّلة لاختبار مكتبة الفيديو.',
                    'status' => 'ended',
                    'scheduled_at' => now()->subDays(4),
                    'started_at' => now()->subDays(4),
                    'ended_at' => now()->subDays(4)->addHour(),
                    'duration_minutes' => 60,
                    'is_recorded' => true,
                    'require_enrollment' => true,
                    'allow_chat' => true,
                ]);
            } else {
                $live->update([
                    'instructor_id' => $instructor->id,
                    'status' => 'ended',
                    'require_enrollment' => true,
                ]);
            }

            LiveRecording::updateOrCreate(
                [
                    'session_id' => $live->id,
                    'title' => 'تسجيل المراجعة الأسبوعية',
                ],
                [
                    'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'duration_seconds' => 3600,
                    'status' => 'ready',
                    'is_published' => true,
                ]
            );
        }

        return $course->fresh();
    }

    private function ensurePrivateLessons(
        User $student,
        User $instructor,
        ?int $entitlementId,
        ?int $assignmentId,
        ?int $courseId
    ): void {
        OneToOneSession::query()
            ->where('student_id', $student->id)
            ->where('instructor_id', $instructor->id)
            ->where('notes', 'like', 'demo:%')
            ->delete();

        OneToOneSession::create([
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'student_service_entitlement_id' => $entitlementId,
            'advanced_course_id' => $courseId,
            'session_number' => 1,
            'scheduled_at' => now()->subDays(2)->setTime(16, 0),
            'duration_minutes' => OneToOneSession::defaultDurationMinutes(),
            'is_private_lecture' => true,
            'status' => OneToOneSession::STATUS_COMPLETED,
            'booked_by_user_id' => $student->id,
            'notes' => 'demo:completed-private',
        ]);

        OneToOneSession::create([
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'student_service_entitlement_id' => $entitlementId,
            'advanced_course_id' => $courseId,
            'session_number' => 2,
            'scheduled_at' => now()->addDay()->setTime(17, 30),
            'duration_minutes' => OneToOneSession::defaultDurationMinutes(),
            'is_private_lecture' => true,
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'booked_by_user_id' => $student->id,
            'notes' => 'demo:upcoming-private',
        ]);

        OneToOneSession::create([
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'student_service_entitlement_id' => $entitlementId,
            'session_number' => 3,
            'scheduled_at' => null,
            'duration_minutes' => OneToOneSession::defaultDurationMinutes(),
            'is_private_lecture' => true,
            'status' => OneToOneSession::STATUS_PENDING,
            'booked_by_user_id' => $student->id,
            'notes' => 'demo:pending-private',
        ]);
    }

    private function ensureMessages(
        User $student,
        User $instructor,
        ?int $assignmentId,
        ?int $courseId,
        ?int $groupId
    ): void {
        $thread = PrivateLessonThread::query()
            ->where('student_id', $student->id)
            ->where('instructor_id', $instructor->id)
            ->where('subject', 'تواصل تجريبي — متابعة المسار')
            ->first();

        if (! $thread) {
            $thread = PrivateLessonThread::create([
                'student_id' => $student->id,
                'instructor_id' => $instructor->id,
                'student_instructor_assignment_id' => $assignmentId,
                'advanced_course_id' => $courseId,
                'tutoring_group_id' => $groupId,
                'subject' => 'تواصل تجريبي — متابعة المسار',
                'status' => PrivateLessonThread::STATUS_OPEN,
                'admin_visible' => true,
                'last_message_at' => now(),
            ]);
        }

        if ($thread->messages()->count() === 0) {
            PrivateLessonMessage::create([
                'private_lesson_thread_id' => $thread->id,
                'sender_id' => $student->id,
                'sender_role' => PrivateLessonMessage::ROLE_STUDENT,
                'body' => 'السلام عليكم أستاذة هدى، هل يمكن مراجعة واجب التلخيص؟',
            ]);
            PrivateLessonMessage::create([
                'private_lesson_thread_id' => $thread->id,
                'sender_id' => $instructor->id,
                'sender_role' => PrivateLessonMessage::ROLE_INSTRUCTOR,
                'body' => 'وعليكم السلام، بالتأكيد. أرسل الملخص وسأراجعها اليوم.',
            ]);
            $thread->update(['last_message_at' => now()]);
        }
    }

    private function ensurePlacement(User $student, AcademicYear $year): void
    {
        if (! Schema::hasTable('free_trial_bookings')) {
            return;
        }

        FreeTrialBooking::updateOrCreate(
            [
                'user_id' => $student->id,
                'email' => $student->email,
            ],
            [
                'name' => $student->name,
                'phone' => '01000000049',
                'goal' => 'تجربة كاملة لمسار المدرسة',
                'starts_at' => now()->subDays(20)->setTime(15, 0),
                'ends_at' => now()->subDays(20)->setTime(15, 45),
                'duration_minutes' => 45,
                'status' => FreeTrialBooking::STATUS_COMPLETED,
                'recommended_academic_year_id' => $year->id,
                'admin_notes' => 'مناسب لمستوى Foundations 3 — متابعة مع أ. هدى.',
                'notes' => 'demo:provision-student',
            ]
        );
    }

    private function ensureNotifications(User $student, User $instructor, ?TutoringGroupCohort $cohort): void
    {
        $items = [
            [
                'title' => 'مرحباً بك في تجربة المنصة',
                'message' => 'تم تجهيز حسابك باشتراك Premier وفصول مع أ. هدى. استكشف الجدول والمكتبة والواجبات.',
                'type' => 'announcement',
                'priority' => 'high',
                'action_url' => route('dashboard'),
                'action_text' => 'افتح الجدول',
            ],
            [
                'title' => 'حصة قادمة اليوم',
                'message' => 'لديك حصة جماعية قريبة. تأكد من جاهزية الإنترنت.',
                'type' => 'reminder',
                'priority' => 'normal',
                'action_url' => $cohort ? route('student.classes.show', $cohort) : route('dashboard'),
                'action_text' => 'عرض الفصل',
            ],
            [
                'title' => 'رسالة من المعلمة',
                'message' => 'ردّت أ. هدى على محادثتك الخاصة.',
                'type' => 'general',
                'priority' => 'normal',
                'action_url' => Route::has('student.private-messages.index') ? route('student.private-messages.index') : route('dashboard'),
                'action_text' => 'افتح الرسائل',
                'sender_id' => $instructor->id,
            ],
        ];

        foreach ($items as $item) {
            $exists = Notification::query()
                ->where('user_id', $student->id)
                ->where('title', $item['title'])
                ->exists();
            if ($exists) {
                continue;
            }
            Notification::create([
                'user_id' => $student->id,
                'sender_id' => $item['sender_id'] ?? null,
                'title' => $item['title'],
                'message' => $item['message'],
                'type' => $item['type'],
                'priority' => $item['priority'],
                'audience' => 'student',
                'action_url' => $item['action_url'],
                'action_text' => $item['action_text'],
                'is_read' => false,
            ]);
        }
    }

    private function ensureGameProgress(User $student, ?TutoringGroupCohort $cohort): void
    {
        try {
            StudentSchoolGameService::ensureDefaultMissions();
            StudentSchoolGameService::award(
                user: $student,
                amount: 120,
                reason: 'demo_bootstrap',
                sourceType: 'demo',
                sourceId: $cohort?->id,
                cohortId: $cohort?->id,
                metadata: ['note' => 'demo:provision-student'],
            );
        } catch (\Throwable $e) {
            $this->warn('Game XP skipped: '.$e->getMessage());
        }

        if (Schema::hasTable('student_learning_streaks')) {
            StudentLearningStreak::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'current_streak' => 4,
                    'longest_streak' => 7,
                    'last_activity_date' => now()->toDateString(),
                ]
            );
        }
    }
}
