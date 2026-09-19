<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Models\SessionAttendance;
use App\Support\ShareAnnotationSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveSessionController extends Controller
{
    public function index(Request $request)
    {
        if (! student_ui('show_live_broadcast')) {
            return redirect()->route('dashboard');
        }

        $user = auth()->user();

        $enrolledCourseIds = collect();
        if (Schema::hasTable('student_course_enrollments')) {
            $enrolledCourseIds = $user->courseEnrollments()
                ->get()
                ->filter(fn ($e) => \App\Services\CourseSubscriptionService::enrollmentGrantsAccess($e))
                ->pluck('advanced_course_id');
        } elseif (Schema::hasTable('online_enrollments')) {
            $enrollQuery = DB::table('online_enrollments')
                ->where('user_id', $user->id);
            if (Schema::hasColumn('online_enrollments', 'is_active')) {
                $enrollQuery->where('is_active', true);
            }
            $enrolledCourseIds = $enrollQuery->pluck('advanced_course_id');
        }

        $query = LiveSession::with(['course', 'instructor'])
            ->visibleToStudents()
            ->where(function ($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds)
                    ->orWhere('require_enrollment', false)
                    ->orWhereNull('course_id');
            })
            ->whereIn('status', ['scheduled', 'live']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        $liveSessions = LiveSession::query()
            ->visibleToStudents()
            ->where('status', 'live')
            ->where(function ($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds)
                    ->orWhere('require_enrollment', false)
                    ->orWhereNull('course_id');
            })
            ->with(['instructor', 'course'])
            ->get();

        return view('student.live-sessions.index', compact('sessions', 'liveSessions'));
    }

    public function show(LiveSession $liveSession)
    {
        if (! student_ui('show_live_broadcast')) {
            return redirect()->route('dashboard');
        }

        if (! $liveSession->canUserJoin(auth()->user())) {
            abort(403, 'ليس لديك صلاحية دخول هذه الجلسة');
        }

        $liveSession->load(['course', 'instructor', 'recordings' => fn ($q) => $q->where('status', 'ready')->where('is_published', true)]);

        return view('student.live-sessions.show', compact('liveSession'));
    }

    public function join(LiveSession $liveSession)
    {
        $user = auth()->user();

        if (! $liveSession->canUserJoin($user)) {
            return back()->with('error', 'ليس لديك صلاحية دخول هذه الجلسة — تأكد من تسجيلك في الكورس');
        }
        if (! $liveSession->isLive()) {
            return back()->with('error', 'الجلسة ليست في وضع البث حالياً');
        }

        $existing = SessionAttendance::where('session_id', $liveSession->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (! $existing) {
            SessionAttendance::create([
                'session_id' => $liveSession->id,
                'user_id' => $user->id,
                'joined_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'role_in_session' => 'student',
            ]);
        }

        $allowStudentWhiteboard = $liveSession->allowsStudentWhiteboard();
        $meeting = app(\App\Services\LiveMeetingProvider::class)->roomPayload($liveSession, $user, false);

        return view('student.live-sessions.room', array_merge(
            compact('liveSession', 'user', 'allowStudentWhiteboard'),
            $meeting
        ));
    }

    public function leave(LiveSession $liveSession)
    {
        $attendance = SessionAttendance::where('session_id', $liveSession->id)
            ->where('user_id', auth()->id())
            ->whereNull('left_at')
            ->first();

        $attendance?->markLeft();

        return redirect()->route('student.live-sessions.index')
            ->with('success', 'تم تسجيل خروجك من الجلسة');
    }

    /**
     * فحص حالة الجلسة (polling من الطالب)
     */
    public function status(LiveSession $liveSession)
    {
        if (! $liveSession->canUserJoin(auth()->user())) {
            abort(403);
        }

        $liveSession->refresh();

        return response()->json([
            'status' => $liveSession->status,
            'ended' => in_array($liveSession->status, ['ended', 'cancelled']),
            'allow_student_whiteboard' => $liveSession->allowsStudentWhiteboard(),
        ]);
    }

    /**
     * مزامنة رسم الطالب فوق منطقة البث (إحداثيات معيّرة) ليظهر لدى المدرب.
     */
    public function pushShareAnnotation(Request $request, LiveSession $liveSession)
    {
        $user = auth()->user();
        if ($user->id === $liveSession->instructor_id) {
            abort(403);
        }
        if (! $liveSession->canUserJoin($user) || ! $liveSession->isLive() || ! $liveSession->allowsStudentWhiteboard()) {
            return response()->json(['message' => 'غير مسموح'], 422);
        }

        $clean = ShareAnnotationSanitizer::polylines($request->input('polylines'));
        $key = 'mx_share_ann_live_'.$liveSession->id;
        $all = Cache::get($key, []);
        $all[(string) $user->id] = [
            'name' => $user->name,
            'polylines' => $clean,
            'ts' => now()->timestamp,
        ];
        Cache::put($key, $all, now()->addHours(6));

        return response()->json(['ok' => true]);
    }
}
