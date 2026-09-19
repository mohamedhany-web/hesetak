<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\User;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Order;
use App\Models\AdvancedCourse;
use App\Models\ContactMessage;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\Certificate;
use App\Models\LectureVideoQuestionAnswer;

class DashboardController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/login')->with('error', 'يجب تسجيل الدخول أولاً');
        }
        
        // التحقق من أن المستخدم نشط
        if (!$user->is_active) {
            Auth::logout();
            return redirect('/login')->with('error', 'حسابك غير نشط. يرجى التواصل مع الإدارة.');
        }
        
        // التحقق من كون المستخدم موظف
        if ($user->isEmployee()) {
            // الموظف ذو دور RBAC مخصص → لوحة تحكم الأدمن بصلاحيات محدودة
            if ($user->roles()->exists()) {
                return redirect()->route('admin.dashboard');
            }
            // الموظف العادي → لوحة الموظفين
            return redirect()->route('employee.dashboard');
        }
        
        // التحقق من وجود دور للمستخدم
        if (!$user->role) {
            Auth::logout();
            return redirect('/login')->with('error', 'دور المستخدم غير محدد. يرجى التواصل مع الإدارة.');
        }
        
        // دعم الأدوار القديمة والجديدة للتوافق
        $role = strtolower(trim($user->role));
        
        switch ($role) {
            case 'super_admin':
            case 'admin': // للتوافق مع الأدوار القديمة
                // توجيه المديرين إلى لوحة التحكم الأساسية
                return redirect()->route('admin.dashboard');
            case 'instructor':
            case 'teacher': // للتوافق مع الأدوار القديمة
                if (! $user->canAccessInstructorPanel()) {
                    return redirect()
                        ->route('public.tutor.apply.profile')
                        ->with('error', app()->getLocale() === 'ar'
                            ? 'أكمل ملفك التعريفي. لوحة المعلم تُفتح بعد تفعيل الإدارة.'
                            : 'Complete your profile. The instructor dashboard opens after admin activation.');
                }

                return $this->instructorDashboard();
            case 'student':
                return $this->studentDashboard();
            default:
                // إذا كان الدور غير معروف، نعيد إلى الصفحة الرئيسية مع رسالة خطأ
                Auth::logout();
                return redirect('/login')->with('error', 'دور المستخدم غير صالح: ' . $role . '. يرجى التواصل مع الإدارة.');
        }
    }


    private function instructorDashboard()
    {
        $user = Auth::user();
        
        try {
            // معرفات الكورسات التي يدرّسها المدرب: مباشرة (instructor_id) + المعينة له في المسارات (assigned_courses)
            $directCourseIds = \App\Models\AdvancedCourse::where('instructor_id', $user->id)->pluck('id');
            $assignedFromPaths = $user->teachingLearningPaths()->get()->flatMap(function ($ay) {
                $ids = json_decode($ay->pivot->assigned_courses ?? '[]', true);
                return is_array($ids) ? $ids : [];
            });
            $teachingCourseIds = $directCourseIds->merge($assignedFromPaths)->unique()->filter()->values();

            // عدد الكورسات التي يدرّسها
            $myCoursesCount = $teachingCourseIds->count();

            // الكورسات (آخر 5 للعرض)
            $my_courses = $myCoursesCount > 0
                ? \App\Models\AdvancedCourse::whereIn('id', $teachingCourseIds)
                    ->with(['academicSubject', 'academicYear'])
                    ->withCount(['enrollments as active_students_count' => function ($q) {
                        $q->where('status', 'active');
                    }])
                    ->latest()
                    ->take(5)
                    ->get()
                : collect();

            // إحصائيات حقيقية (مبنية على كورسات التدريس فقط)
            $stats = [
                'my_courses' => $myCoursesCount,
                'total_students' => $teachingCourseIds->isEmpty()
                    ? 0
                    : \App\Models\StudentCourseEnrollment::whereIn('advanced_course_id', $teachingCourseIds)
                        ->where('status', 'active')
                        ->distinct('user_id')
                        ->count('user_id'),
                'my_classrooms' => Classroom::where('teacher_id', $user->id)->count(),
                'total_lectures' => $teachingCourseIds->isEmpty()
                    ? 0
                    : \App\Models\Lecture::whereIn('course_id', $teachingCourseIds)->count(),
                'upcoming_lectures' => $teachingCourseIds->isEmpty()
                    ? 0
                    : \App\Models\Lecture::whereIn('course_id', $teachingCourseIds)
                        ->where('status', 'scheduled')
                        ->where('scheduled_at', '>=', now())
                        ->count(),
                'total_assignments' => \App\Models\Assignment::where('teacher_id', $user->id)->count(),
                'pending_submissions' => \App\Models\AssignmentSubmission::whereHas('assignment', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                })->whereNull('graded_at')->count(),
                'total_exams' => \App\Models\Exam::where('created_by', $user->id)->count(),
            ];

            // المحاضرات القادمة (للكورسات التي يدرّسها فقط)
            $upcoming_lectures = $teachingCourseIds->isEmpty()
                ? collect()
                : \App\Models\Lecture::whereIn('course_id', $teachingCourseIds)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())
                ->with(['course', 'lesson'])
                ->orderBy('scheduled_at', 'asc')
                ->take(5)
                ->get();

            // الواجبات المعلقة (تسليمات تحتاج تقييم)
            $pending_assignments = \App\Models\AssignmentSubmission::whereHas('assignment', function($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                })
                ->whereNull('graded_at')
                ->with(['assignment', 'student'])
                ->latest()
                ->take(5)
                ->get();

            $my_classrooms = Classroom::where('teacher_id', $user->id)
                ->with('students')
                ->latest()
                ->take(5)
                ->get();

            $upcomingPrivateSession = null;
            $upcomingPrivateCount = 0;
            $upcoming_private_sessions = collect();
            if (\Illuminate\Support\Facades\Schema::hasTable('one_to_one_sessions')) {
                $upcomingPrivateQ = \App\Models\OneToOneSession::query()
                    ->where('instructor_id', $user->id)
                    ->where('status', \App\Models\OneToOneSession::STATUS_SCHEDULED)
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>=', now())
                    ->with(['student:id,name', 'course:id,title'])
                    ->orderBy('scheduled_at');

                $upcomingPrivateSession = (clone $upcomingPrivateQ)->first();
                $upcoming_private_sessions = (clone $upcomingPrivateQ)->take(5)->get();
                $upcomingPrivateCount = (clone $upcomingPrivateQ)->count();
            }
            $stats['upcoming_private'] = $upcomingPrivateCount;
            $stats['upcoming_tutoring'] = 0;
            $stats['cohorts_count'] = 0;

            // Keep legacy keys empty — group bookings are out of product scope
            $upcomingTutoringBooking = null;
            $upcoming_tutoring_bookings = collect();

            $stats['live_now'] = 0;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('live_sessions')) {
                    $stats['live_now'] = \App\Models\LiveSession::query()
                        ->where('instructor_id', $user->id)
                        ->where('status', 'live')
                        ->count();
                }
            } catch (\Throwable $e) {
            }

            // Activity series (last 7 days) — 1:1 sessions + live
            $activitySeries = [];
            $activityLabels = [];
            $activityPeak = 0;
            $activityPeakLabel = '';
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->startOfDay();
                $dayEnd = (clone $day)->endOfDay();
                $count = 0;
                if (\Illuminate\Support\Facades\Schema::hasTable('one_to_one_sessions')) {
                    $count += \App\Models\OneToOneSession::query()
                        ->where('instructor_id', $user->id)
                        ->whereBetween('scheduled_at', [$day, $dayEnd])
                        ->count();
                }
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('live_sessions')) {
                        $count += \App\Models\LiveSession::query()
                            ->where('instructor_id', $user->id)
                            ->whereBetween('scheduled_at', [$day, $dayEnd])
                            ->count();
                    }
                } catch (\Throwable $e) {
                }
                $label = $day->translatedFormat('D');
                $activitySeries[] = $count;
                $activityLabels[] = $label;
                if ($count >= $activityPeak) {
                    $activityPeak = $count;
                    $activityPeakLabel = $day->translatedFormat('j M');
                }
            }
            $activityTotal = array_sum($activitySeries);
            $activityMax = max(1, ...$activitySeries);

            $donutSlices = [
                ['key' => 'private', 'label' => __('instructor.private_lessons'), 'value' => (int) ($stats['upcoming_private'] ?? 0), 'tone' => 'full'],
                ['key' => 'live', 'label' => __('instructor.live_broadcast'), 'value' => (int) ($stats['live_now'] ?? 0), 'tone' => 'mid'],
                ['key' => 'pending', 'label' => __('instructor.cd_past'), 'value' => 0, 'tone' => 'soft'],
            ];

            $activeOnline = (int) ($stats['upcoming_private'] ?? 0);
            $activeOffline = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('one_to_one_sessions')) {
                $activeOffline = \App\Models\OneToOneSession::query()
                    ->where('instructor_id', $user->id)
                    ->whereIn('status', [
                        \App\Models\OneToOneSession::STATUS_COMPLETED,
                        \App\Models\OneToOneSession::STATUS_CANCELLED,
                    ])
                    ->where('scheduled_at', '>=', now()->subDays(30))
                    ->count();

                $donutSlices[2]['value'] = \App\Models\OneToOneSession::query()
                    ->where('instructor_id', $user->id)
                    ->where('status', \App\Models\OneToOneSession::STATUS_PENDING)
                    ->count();
            }
            $donutTotal = max(0, collect($donutSlices)->sum('value'));
            $activeTotal = $activeOnline + $activeOffline;
            $activePct = $activeTotal > 0 ? (int) round(($activeOnline / $activeTotal) * 100) : 0;

            // Income / withdrawals summary
            $incomeChange = 0;
            $incomeAmount = 0;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('instructor_withdrawals')) {
                    $incomeAmount = (float) \DB::table('instructor_withdrawals')
                        ->where('user_id', $user->id)
                        ->where('status', 'paid')
                        ->sum('amount');
                }
            } catch (\Throwable $e) {
            }

            // Activities feed — upcoming 1:1
            $activities = collect();
            foreach ($upcoming_private_sessions->take(4) as $session) {
                $activities->push([
                    'title' => $session->student?->name
                        ?? $session->course?->title
                        ?? __('instructor.private_lessons'),
                    'meta' => optional($session->scheduled_at)->diffForHumans() ?? '',
                    'url' => Route::has('instructor.one-to-one-sessions.index')
                        ? route('instructor.one-to-one-sessions.index')
                        : route('dashboard'),
                    'icon' => 'bookings',
                ]);
            }
            foreach (($pending_assignments ?? collect())->take(2) as $sub) {
                $activities->push([
                    'title' => $sub->assignment->title ?? __('instructor.assignment_default'),
                    'meta' => ($sub->student->name ?? '').' · '.optional($sub->created_at)->diffForHumans(),
                    'url' => Route::has('instructor.assignments.submissions')
                        ? route('instructor.assignments.submissions', $sub->assignment)
                        : route('dashboard'),
                    'icon' => 'task',
                ]);
            }
            $activities = $activities->take(4)->values();

            $overview = [
                'activity_series' => $activitySeries,
                'activity_labels' => $activityLabels,
                'activity_total' => $activityTotal,
                'activity_peak' => $activityPeak,
                'activity_peak_label' => $activityPeakLabel,
                'activity_max' => $activityMax,
                'donut_slices' => $donutSlices,
                'donut_total' => $donutTotal,
                'active_online' => $activeOnline,
                'active_offline' => $activeOffline,
                'active_total' => $activeTotal,
                'active_pct' => $activePct,
                'income_amount' => $incomeAmount,
                'income_change' => $incomeChange,
                'activities' => $activities,
                'month_label' => now()->translatedFormat('F Y'),
            ];

            return view('dashboard.instructor', compact(
                'stats',
                'my_courses',
                'my_classrooms',
                'upcoming_lectures',
                'pending_assignments',
                'upcomingTutoringBooking',
                'upcoming_tutoring_bookings',
                'upcomingPrivateSession',
                'upcoming_private_sessions',
                'overview'
            ));
        } catch (\Exception $e) {
            // في حالة وجود خطأ، نعيد لوحة تحكم بسيطة
            \Log::error('Instructor Dashboard Error: ' . $e->getMessage());
            $stats = [
                'my_courses' => 0,
                'total_students' => 0,
                'my_classrooms' => 0,
                'total_lectures' => 0,
                'upcoming_lectures' => 0,
                'total_assignments' => 0,
                'pending_submissions' => 0,
                'total_exams' => 0,
                'upcoming_tutoring' => 0,
                'upcoming_private' => 0,
                'cohorts_count' => 0,
                'live_now' => 0,
            ];
            $my_courses = collect();
            $my_classrooms = collect();
            $upcoming_lectures = collect();
            $pending_assignments = collect();
            $upcomingTutoringBooking = null;
            $upcoming_tutoring_bookings = collect();
            $upcomingPrivateSession = null;
            $upcoming_private_sessions = collect();
            $overview = [
                'activity_series' => [0, 0, 0, 0, 0, 0, 0],
                'activity_labels' => [],
                'activity_total' => 0,
                'activity_peak' => 0,
                'activity_peak_label' => '',
                'activity_max' => 1,
                'donut_slices' => [],
                'donut_total' => 0,
                'active_online' => 0,
                'active_offline' => 0,
                'active_total' => 0,
                'active_pct' => 0,
                'income_amount' => 0,
                'income_change' => 0,
                'activities' => collect(),
                'month_label' => now()->translatedFormat('F Y'),
            ];

            return view('dashboard.instructor', compact(
                'stats',
                'my_courses',
                'my_classrooms',
                'upcoming_lectures',
                'pending_assignments',
                'upcomingTutoringBooking',
                'upcoming_tutoring_bookings',
                'upcomingPrivateSession',
                'upcoming_private_sessions',
                'overview'
            ));
        }
    }

    private function studentDashboard()
    {
        $user = Auth::user();

        // نفس لوحة الجدول الزمني — البيانات تُضبط داخل StudentSchoolHomeService (حصص فردية + رصيد)
        $payload = app(\App\Services\StudentSchoolHomeService::class)->build($user, [
            'week' => request()->query('week'),
            'view' => request()->query('view'),
            'sort' => request()->query('sort'),
            'q' => request()->query('q'),
        ]);

        return view('student.school.home', $payload);
    }

    private function calculateOverallProgress($user)
    {
        $enrollments = $user->courseEnrollments()
            ->whereIn('status', ['active', 'completed'])
            ->get();
        if ($enrollments->isEmpty()) return 0;
        
        $totalProgress = $enrollments->reduce(function ($carry, $enrollment) {
            return $carry + (float) ($enrollment->progress ?? 0);
        }, 0);

        return round($totalProgress / $enrollments->count(), 1);
    }

}
