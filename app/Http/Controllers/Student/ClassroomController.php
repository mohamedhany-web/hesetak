<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassroomMeeting;
use App\Models\ClassroomMeetingReport;
use App\Models\IntegrationSetting;
use App\Models\OneToOneSession;
use App\Models\TutoringGroupBooking;
use App\Services\ClassroomCurriculumPresentService;
use App\Services\ClassroomMeetingAccessService;
use App\Services\OneToOneSessionUnlockService;
use App\Services\OneToOneSessionService;
use App\Services\TutoringGroupOrchestrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (! $user?->isStudent() || ! $request->routeIs('student.*')) {
                return $next($request);
            }

            $allowed = [
                'student.classroom.room',
                'student.classroom.room.status',
                'student.classroom.recording',
                'student.classroom.recording.status',
                'student.classroom.share-annotations',
                'student.classroom.share-annotation',
                'student.classroom.whiteboard.state',
                'student.classroom.whiteboard.push',
                'student.classroom.curriculum.state',
                'student.classroom.curriculum.slide',
                'student.classroom.curriculum.thumb',
            ];

            $routeName = $request->route()?->getName();
            if ($routeName && ! in_array($routeName, $allowed, true)) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'لا يمكن للطلاب إنشاء أو إدارة اجتماعات مباشرة. انضم من جدولك أو من حصصك/فصولك.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $status = (string) $request->get('status', 'all');
        if (! in_array($status, ['all', 'live', 'scheduled', 'ended'], true)) {
            $status = 'all';
        }

        $meetingsQuery = ClassroomMeeting::query()->where('user_id', $user->id)->withCount('participants');
        if ($status === 'live') {
            $meetingsQuery->whereNotNull('started_at')->whereNull('ended_at');
        } elseif ($status === 'scheduled') {
            $meetingsQuery->whereNull('started_at');
        } elseif ($status === 'ended') {
            $meetingsQuery->whereNotNull('ended_at');
        }

        $meetings = $meetingsQuery
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $limits = $this->classroomLimits();
        $usedMeetingsThisMonth = $this->monthlyClassroomUsage($user);
        $remainingMeetingsThisMonth = max(0, $limits['classroom_meetings_per_month'] - $usedMeetingsThisMonth);
        $joinBaseUrl = url('classroom/join');
        $stats = [
            'total' => ClassroomMeeting::where('user_id', $user->id)->count(),
            'live' => ClassroomMeeting::where('user_id', $user->id)->whereNotNull('started_at')->whereNull('ended_at')->count(),
            'ended' => ClassroomMeeting::where('user_id', $user->id)->whereNotNull('ended_at')->count(),
        ];

        return view('student.classroom.index', compact(
            'meetings',
            'joinBaseUrl',
            'limits',
            'usedMeetingsThisMonth',
            'remainingMeetingsThisMonth',
            'stats',
            'status'
        ));
    }

    /**
     * حصتك Whiteboard — صفحة لوحة كاملة منفصلة (خارج غرفة الاجتماع).
     */
    public function whiteboardStandalone()
    {
        return view('student.classroom.whiteboard-standalone');
    }

    public function create()
    {
        $user = Auth::user();
        $limits = $this->classroomLimits();
        $usedMeetingsThisMonth = $this->monthlyClassroomUsage($user);
        $remainingMeetingsThisMonth = max(0, $limits['classroom_meetings_per_month'] - $usedMeetingsThisMonth);

        return view('student.classroom.create', compact('limits', 'usedMeetingsThisMonth', 'remainingMeetingsThisMonth'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $limits = $this->classroomLimits();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'max_participants' => ['required', 'integer', 'min:2', 'max:'.$limits['classroom_max_participants']],
            'start_now' => ['nullable', Rule::in(['0', '1'])],
            'scheduled_for' => ['nullable', 'date'],
            'planned_duration_minutes' => ['nullable', 'integer', 'min:15', 'max:'.$limits['classroom_max_duration_minutes']],
        ]);

        $code = ClassroomMeeting::generateCode();
        $roomName = 'حصتك-'.$code;
        $startNow = (string) ($data['start_now'] ?? '1') === '1';

        $meeting = ClassroomMeeting::create([
            'user_id' => $user->id,
            'code' => $code,
            'room_name' => $roomName,
            'title' => $data['title'],
            'scheduled_for' => $startNow ? null : ($data['scheduled_for'] ?? null),
            'planned_duration_minutes' => (int) ($data['planned_duration_minutes'] ?? $limits['classroom_default_duration_minutes']),
            'max_participants' => (int) $data['max_participants'],
            'started_at' => $startNow ? now() : null,
            // رابط الضيوف مغلق افتراضياً — يُفعَّل يدوياً إن لزم
            'settings' => ['allow_guest_join' => false],
        ]);

        if ($startNow) {
            return redirect()->route('student.classroom.room', $meeting);
        }

        return redirect()->route('student.classroom.show', $meeting)
            ->with('success', 'تم إنشاء الاجتماع بنجاح. يمكنك بدءه متى شئت.');
    }

    public function start(Request $request)
    {
        $limits = $this->classroomLimits();
        $request->merge([
            'title' => $request->input('title') ?: 'غرفة حصتك - '.now()->format('H:i'),
            'max_participants' => (string) $limits['classroom_max_participants'],
            'planned_duration_minutes' => (string) $limits['classroom_default_duration_minutes'],
            'start_now' => '1',
        ]);

        return $this->store($request);
    }

    public function show(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);

        $meeting->loadCount('participants');
        $meeting->load(['aiReports' => function ($q) {
            $q->orderByDesc('id')->limit(20);
        }]);
        $aiReports = $meeting->aiReports;
        $activeAiReport = $aiReports->first(fn ($r) => in_array($r->status, ['pending', 'processing'], true));
        $latestCompletedAiReport = $aiReports->firstWhere('status', 'completed');
        $joinUrl = ClassroomMeetingAccessService::allowsGuestJoin($meeting)
            ? url('classroom/join/'.$meeting->code)
            : null;
        $guestJoinEnabled = ClassroomMeetingAccessService::allowsGuestJoin($meeting);
        $isPlatformBound = ClassroomMeetingAccessService::isPlatformBound($meeting);
        $limits = $this->classroomLimits();
        $useInstructorRoutes = request()->routeIs('instructor.*');

        return view('student.classroom.show', compact(
            'meeting',
            'joinUrl',
            'guestJoinEnabled',
            'isPlatformBound',
            'limits',
            'useInstructorRoutes',
            'activeAiReport',
            'latestCompletedAiReport'
        ));
    }

    /**
     * دخول آمن من المنصة فقط (طالب/معلم مصرّح) — بدون رابط ضيف قابل للمشاركة.
     */
    public function secureEnter(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);

        return redirect()->to(ClassroomMeetingAccessService::roomUrlForUser($meeting, $user));
    }

    public function edit(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $limits = $this->classroomLimits();

        return view('student.classroom.edit', compact('meeting', 'limits'));
    }

    public function update(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $limits = $this->classroomLimits();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'max_participants' => ['required', 'integer', 'min:2', 'max:'.$limits['classroom_max_participants']],
            'scheduled_for' => ['nullable', 'date'],
            'planned_duration_minutes' => ['nullable', 'integer', 'min:15', 'max:'.$limits['classroom_max_duration_minutes']],
        ]);

        $meeting->update([
            'title' => $data['title'],
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'planned_duration_minutes' => (int) ($data['planned_duration_minutes'] ?? $limits['classroom_default_duration_minutes']),
            'max_participants' => (int) $data['max_participants'],
        ]);

        return redirect()->route('student.classroom.show', $meeting)->with('success', 'تم تحديث إعدادات الاجتماع.');
    }

    public function startMeeting(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);

        if ($meeting->ended_at) {
            return back()->with('error', 'لا يمكن بدء اجتماع منتهي.');
        }
        if (! $meeting->started_at) {
            $meeting->update(['started_at' => now()]);
        }

        return redirect()->to($this->classroomRoomUrl($meeting));
    }

    public function room(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);
        $isHost = ClassroomMeetingAccessService::userIsHost($meeting, $user);
        $canManageMeeting = $isHost && ($user->isInstructor() || $user->isAdmin());

        if ($meeting->ended_at) {
            if (request()->routeIs('instructor.*')) {
                if ($meeting->tutoring_group_booking_id) {
                    return redirect()->route('instructor.tutoring-bookings.show', $meeting->tutoring_group_booking_id)
                        ->with('error', 'انتهى هذا الاجتماع ولا يمكن إعادة فتح الغرفة.');
                }

                if ($meeting->consultation_request_id) {
                    return redirect()->route('instructor.consultations.show', $meeting->consultation_request_id)
                        ->with('error', 'انتهى هذا الاجتماع ولا يمكن إعادة فتح الغرفة.');
                }

                if ($meeting->one_to_one_session_id) {
                    return redirect()->route('instructor.one-to-one-sessions.show', $meeting->one_to_one_session_id)
                        ->with('error', 'انتهى هذا الاجتماع ولا يمكن إعادة فتح الغرفة.');
                }

                return redirect()->route('instructor.consultations.index')
                    ->with('error', 'انتهى هذا الاجتماع ولا يمكن إعادة فتح الغرفة.');
            }

            if ($meeting->one_to_one_session_id && Route::has('student.one-to-one-sessions.show')) {
                return redirect()->route('student.one-to-one-sessions.show', $meeting->one_to_one_session_id)
                    ->with('error', 'انتهى هذا الاجتماع.');
            }

            if ($meeting->tutoring_group_booking_id && Route::has('student.tutoring-bookings.show')) {
                return redirect()->route('student.tutoring-bookings.show', $meeting->tutoring_group_booking_id)
                    ->with('error', 'انتهى هذا الاجتماع.');
            }

            if ((int) $meeting->user_id === (int) $user->id) {
                return redirect()->route('student.classroom.show', $meeting)
                    ->with('error', 'انتهى هذا الاجتماع ولا يمكن إعادة فتح الغرفة.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'انتهى هذا الاجتماع.');
        }

        // أي طرف مصرّح يدخل يبدأ الجلسة حتى يتمكن الطرف الآخر من الانضمام داخل المنصة.
        if (! $meeting->started_at) {
            $meeting->update(['started_at' => now()]);
            $meeting->refresh();
        }

        $limits = $this->classroomLimits();
        if ($meeting->consultation_request_id) {
            $effectiveDurationMinutes = (int) ($meeting->planned_duration_minutes ?: 60);
            $maxDurationMinutes = max(480, $effectiveDurationMinutes);
        } else {
            $maxDurationMinutes = (int) $limits['classroom_max_duration_minutes'];
            $defaultDurationMinutes = (int) $limits['classroom_default_duration_minutes'];
            $effectiveDurationMinutes = (int) ($meeting->planned_duration_minutes ?: $defaultDurationMinutes);
            if ($effectiveDurationMinutes > $maxDurationMinutes) {
                $effectiveDurationMinutes = $maxDurationMinutes;
            }
        }

        // لا نُغلق الحصة تلقائياً عند دخول الطالب بعد انتهاء المدة المخططة —
        // ذلك كان يطرد الطالب بينما المعلم يبقى داخل LiveKit وحيداً.
        // الإغلاق الرسمي فقط عبر ended_at (زر إنهاء المضيف) أو مهلة طويلة جداً (stale).
        $staleAfterMinutes = max($effectiveDurationMinutes + 180, 240);
        if ($meeting->started_at && $meeting->started_at->copy()->addMinutes($staleAfterMinutes)->isPast()) {
            if (! $meeting->ended_at) {
                $meeting->update(['ended_at' => now()]);
            }
            $back = request()->routeIs('instructor.*')
                ? route('instructor.consultations.index')
                : route('dashboard');

            return redirect()->to($back)
                ->with('error', $meeting->consultation_request_id
                    ? 'انتهت مدة جلسة الاستشارة.'
                    : 'انتهت مدة الاجتماع المسموح بها.');
        }

        $meetingPayload = app(\App\Services\LiveMeetingProvider::class)->classroomPayload(
            $meeting->liveRoomName(),
            $user,
            $canManageMeeting
        );
        $meetingEndsAt = $meeting->started_at ? $meeting->started_at->copy()->addMinutes($effectiveDurationMinutes) : null;
        $useInstructorRoutes = request()->routeIs('instructor.*') || $canManageMeeting;
        $subscriptionFeatureMenuItems = [];
        $subscriptionPackageLabel = null;
        $guestJoinEnabled = ClassroomMeetingAccessService::allowsGuestJoin($meeting);
        $lkRole = $canManageMeeting ? 'host' : 'participant';

        $meeting->loadMissing([
            'user:id,name',
            'oneToOneSession.instructor:id,name',
            'oneToOneSession.student:id,name',
        ]);

        $roomExitUrl = $this->classroomRoomExitUrl($meeting, $user, $canManageMeeting);

        $viewName = ($canManageMeeting || request()->routeIs('instructor.*'))
            ? 'student.classroom.room'
            : 'student.classroom.room-student';

        return view($viewName, array_merge(
            compact(
                'meeting',
                'user',
                'maxDurationMinutes',
                'effectiveDurationMinutes',
                'meetingEndsAt',
                'useInstructorRoutes',
                'canManageMeeting',
                'subscriptionFeatureMenuItems',
                'subscriptionPackageLabel',
                'guestJoinEnabled',
                'isHost',
                'lkRole',
                'roomExitUrl'
            ),
            $meetingPayload
        ));
    }

    /**
     * مشاهدة/تحميل تسجيل حصة منتهية — للطالب أو المعلم المصرّح له فقط.
     */
    public function watchRecording(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        abort_unless(ClassroomMeetingAccessService::userCanEnter($meeting, $user), 403);
        abort_unless($meeting->ended_at, 404);

        $url = $meeting->recording_download_url ?? $meeting->recording_audio_download_url;
        abort_unless($url, 404);

        return redirect()->away($url);
    }

    /**
     * تاب منفصل لرفع التسجيل — معطّل للمضيف (الرفع تلقائي صامت من الغرفة).
     */
    public function recordingUploadTab(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);

        // المعلم/المضيف: لا نعرض صفحة الرفع — التسجيل يُرفع تلقائياً من الغرفة
        return redirect()
            ->to($this->classroomRoomUrl($meeting))
            ->with('info', 'التسجيل يُرفع تلقائياً في الخلفية دون الحاجة لهذه الصفحة.');
    }

    public function updateParticipantWhiteboard(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($meeting->ended_at || ! $meeting->started_at) {
            return response()->json(['message' => 'الاجتماع غير نشط حالياً.'], 422);
        }

        $validated = $request->validate([
            'allow' => ['required', 'boolean'],
        ]);

        $settings = $meeting->settings ?? [];
        $settings['allow_participant_whiteboard'] = $validated['allow'];
        $meeting->update(['settings' => $settings]);
        $meeting->refresh();

        return response()->json([
            'ok' => true,
            'allow_participant_whiteboard' => $meeting->allowsParticipantWhiteboard(),
        ]);
    }

    /**
     * لقطة السبورة التفاعلية (Excalidraw) — للطالب/المضيف للحاق بالمحتوى.
     */
    public function whiteboardState(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);

        $payload = Cache::get($this->classroomWhiteboardCacheKey($meeting), [
            'v' => 0,
            'elements' => [],
            'appState' => null,
            'files' => null,
            'ts' => null,
        ]);

        return response()->json($payload);
    }

    /**
     * حفظ لقطة السبورة في الكاش (احتياط لمزامنة LiveKit + متأخري الدخول).
     */
    public function pushWhiteboardState(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);

        if (! $meeting->started_at || $meeting->ended_at) {
            return response()->json(['message' => 'الاجتماع غير نشط حالياً.'], 422);
        }

        $isHost = ClassroomMeetingAccessService::userIsHost($meeting, $user)
            || (int) $meeting->user_id === (int) $user->id
            || $user->isAdmin();

        if (! $isHost && ! $meeting->allowsParticipantWhiteboard()) {
            return response()->json(['message' => 'السبورة غير مفعّلة للمشاركين حالياً.'], 422);
        }

        $validated = $request->validate([
            'v' => ['nullable', 'integer', 'min:0'],
            'elements' => ['required', 'array', 'max:8000'],
            'appState' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
        ]);

        $elements = $validated['elements'];
        // حافظ على حجم معقول في الكاش
        $encoded = json_encode($elements, JSON_UNESCAPED_UNICODE);
        if (is_string($encoded) && strlen($encoded) > 2_500_000) {
            return response()->json(['message' => 'محتوى السبورة كبير جداً.'], 422);
        }

        $v = (int) ($validated['v'] ?? (int) (microtime(true) * 1000));
        $key = $this->classroomWhiteboardCacheKey($meeting);
        $existing = Cache::get($key);
        if (is_array($existing) && (int) ($existing['v'] ?? 0) > $v) {
            return response()->json(['ok' => true, 'skipped' => true, 'v' => (int) $existing['v']]);
        }

        $appState = $validated['appState'] ?? null;
        if (is_array($appState)) {
            $appState = array_intersect_key($appState, array_flip([
                'viewBackgroundColor',
                'gridSize',
                'theme',
            ]));
        }

        $files = $validated['files'] ?? null;
        if (is_array($files)) {
            $filesJson = json_encode($files);
            if (is_string($filesJson) && strlen($filesJson) > 1_500_000) {
                $files = null;
            }
        }

        Cache::put($key, [
            'v' => $v,
            'elements' => $elements,
            'appState' => $appState,
            'files' => $files,
            'ts' => now()->timestamp,
            'from' => (int) $user->id,
            'is_host' => $isHost,
        ], now()->addHours(6));

        return response()->json(['ok' => true, 'v' => $v]);
    }

    private function classroomWhiteboardCacheKey(ClassroomMeeting $meeting): string
    {
        return 'mx_wb_classroom_'.$meeting->id;
    }

    public function updateGuestJoin(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);

        if (ClassroomMeetingAccessService::isPlatformBound($meeting)) {
            return back()->with('error', 'لا يمكن تفعيل رابط ضيف لاجتماعات الحصص الخاصة — الدخول من المنصة فقط.');
        }

        $allow = $request->boolean('allow');
        $settings = $meeting->settings ?? [];
        $settings['allow_guest_join'] = $allow;
        $meeting->update(['settings' => $settings]);

        return back()->with('success', $allow
            ? 'تم تفعيل رابط الضيوف. شاركه بحذر.'
            : 'تم إيقاف رابط الضيوف. الدخول من المنصة فقط.');
    }

    public function shareAnnotations(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);
        if (! $meeting->started_at || $meeting->ended_at) {
            return response()->json(['layers' => []]);
        }

        $layers = Cache::get('mx_share_ann_classroom_'.$meeting->id, []);

        return response()->json(['layers' => $layers]);
    }

    /**
     * رفع طبقة رسم فوق البث (معلم أو طالب مصرّح) لمزامنة فورية مع الطرف الآخر.
     */
    public function pushShareAnnotation(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);

        if (! $meeting->started_at || $meeting->ended_at) {
            return response()->json(['message' => 'الاجتماع غير نشط حالياً.'], 422);
        }

        $isHost = ClassroomMeetingAccessService::userIsHost($meeting, $user)
            || (int) $meeting->user_id === (int) $user->id
            || $user->isAdmin();

        if (! $isHost && ! $meeting->allowsParticipantWhiteboard()) {
            return response()->json(['message' => 'الرسم غير مفعّل للمشاركين حالياً.'], 422);
        }

        $clean = \App\Support\ShareAnnotationSanitizer::polylines($request->input('polylines'));
        $key = 'mx_share_ann_classroom_'.$meeting->id;
        $all = Cache::get($key, []);
        $all[(string) $user->id] = [
            'name' => $user->name,
            'polylines' => $clean,
            'ts' => now()->timestamp,
            'is_host' => $isHost,
        ];
        Cache::put($key, $all, now()->addHours(6));

        return response()->json(['ok' => true]);
    }

    public function curriculumCatalog(ClassroomMeeting $meeting, ClassroomCurriculumPresentService $present)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $present->assertMeetingLive($meeting);

        return response()->json([
            'ok' => true,
            'items' => $present->catalogForHost($user),
        ]);
    }

    public function curriculumPresent(Request $request, ClassroomMeeting $meeting, ClassroomCurriculumPresentService $present)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $present->assertMeetingLive($meeting);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'min:1'],
            'material_id' => ['required', 'integer', 'min:1'],
        ]);

        $session = $present->startPresent(
            $meeting,
            $user,
            (int) $validated['item_id'],
            (int) $validated['material_id']
        );

        $state = $present->publicState($meeting, 'host');

        return response()->json([
            'ok' => true,
            'session_id' => $session['session_id'],
            'title' => $session['title'],
            'current_slide' => $session['current_slide'],
            'slide_count' => $session['slide_count'],
            'item_id' => $session['item_id'],
            'material_id' => $session['material_id'],
            'manifest' => $state['manifest'] ?? null,
        ]);
    }

    public function curriculumState(ClassroomMeeting $meeting, ClassroomCurriculumPresentService $present)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $present->assertMeetingLive($meeting);

        $state = $present->publicState($meeting, 'host');
        if (! $state) {
            return response()->json(['ok' => true, 'active' => false]);
        }

        return response()->json(array_merge(['ok' => true], $state));
    }

    /**
     * فحص حالة الغرفة (polling من الطالب/المشارك عند إنهاء المعلم للاجتماع).
     */
    public function roomStatus(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingCanEnter($meeting, $user);

        $meeting->refresh();

        return response()->json([
            'ended' => filled($meeting->ended_at),
            'started' => filled($meeting->started_at),
            'has_recording' => $meeting->hasBrowserRecording(),
            'allow_participant_whiteboard' => $meeting->allowsParticipantWhiteboard(),
        ]);
    }

    /**
     * حالة جاهزية التسجيل للطالب بعد انتهاء الحصة (polling من لوحة الحصة).
     */
    public function recordingStatus(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        abort_unless(ClassroomMeetingAccessService::userCanEnter($meeting, $user), 403);

        $meeting->refresh();
        $ready = $meeting->hasBrowserRecording() && filled($meeting->ended_at);

        return response()->json([
            'ready' => $ready,
            'ended' => filled($meeting->ended_at),
            'watch_url' => $ready && Route::has('student.classroom.recording')
                ? route('student.classroom.recording', $meeting)
                : null,
        ]);
    }

    public function curriculumUpdateSlide(Request $request, ClassroomMeeting $meeting, ClassroomCurriculumPresentService $present)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $present->assertMeetingLive($meeting);

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'slide' => ['required', 'integer', 'min:1'],
        ]);

        $session = $present->updateSlide(
            $meeting,
            (string) $validated['session_id'],
            (int) $validated['slide']
        );

        if (! $session) {
            return response()->json(['ok' => false, 'message' => 'جلسة العرض غير نشطة'], 422);
        }

        return response()->json([
            'ok' => true,
            'session_id' => $session['session_id'],
            'current_slide' => $session['current_slide'],
            'slide_count' => $session['slide_count'],
        ]);
    }

    public function curriculumStop(ClassroomMeeting $meeting, ClassroomCurriculumPresentService $present)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);

        $present->clearSession($meeting);

        return response()->json(['ok' => true, 'active' => false]);
    }

    public function curriculumSlide(
        ClassroomMeeting $meeting,
        string $sessionId,
        int $slide,
        ClassroomCurriculumPresentService $present
    ) {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $present->assertMeetingLive($meeting);

        return $present->streamSessionAsset($meeting, $sessionId, $slide, 'image');
    }

    public function curriculumThumb(
        ClassroomMeeting $meeting,
        string $sessionId,
        int $slide,
        ClassroomCurriculumPresentService $present
    ) {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        $present->assertMeetingLive($meeting);

        return $present->streamSessionAsset($meeting, $sessionId, $slide, 'thumb');
    }

    public function end(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        app(ClassroomCurriculumPresentService::class)->clearSession($meeting);
        if (! $meeting->ended_at) {
            $meeting->update(['ended_at' => now()]);
        }

        $completedTutoring = $this->completeLinkedTutoringBookingIfNeeded($meeting);
        $this->completeLinkedOneToOneIfNeeded($meeting);

        if (request()->routeIs('instructor.*')) {
            if ($meeting->tutoring_group_booking_id) {
                return redirect()
                    ->route('instructor.tutoring-bookings.show', $meeting->tutoring_group_booking_id)
                    ->with('success', $completedTutoring
                        ? 'تم إنهاء الاجتماع وخصم حصة من رصيد الطالب.'
                        : 'تم إنهاء الاجتماع.');
            }

            if ($meeting->consultation_request_id) {
                return redirect()->route('instructor.consultations.show', $meeting->consultation_request_id)
                    ->with('success', 'تم إنهاء جلسة الاستشارة.');
            }

            $oneToOneId = $meeting->one_to_one_session_id
                ?: OneToOneSession::query()->where('classroom_meeting_id', $meeting->id)->value('id');
            if ($oneToOneId && Route::has('instructor.one-to-one-sessions.show')) {
                return redirect()
                    ->route('instructor.one-to-one-sessions.show', $oneToOneId)
                    ->with('success', 'تم إنهاء الاجتماع ورفع التسجيل للطالب.');
            }

            return redirect()->route('instructor.consultations.index')->with('success', 'تم إنهاء الاجتماع.');
        }

        if ($meeting->tutoring_group_booking_id && Route::has('student.tutoring-bookings.show')) {
            return redirect()
                ->route('student.tutoring-bookings.show', $meeting->tutoring_group_booking_id)
                ->with('success', $completedTutoring
                    ? 'تم إنهاء الحصة وخصم وحدة من رصيدك.'
                    : 'تم إنهاء الاجتماع.');
        }

        $oneToOneId = $meeting->one_to_one_session_id
            ?: OneToOneSession::query()->where('classroom_meeting_id', $meeting->id)->value('id');
        if ($oneToOneId && Route::has('student.one-to-one-sessions.show')) {
            return redirect()
                ->route('student.one-to-one-sessions.show', $oneToOneId)
                ->with('success', 'تم إنهاء الاجتماع. سيظهر التسجيل عند اكتمال رفعه.');
        }

        return redirect()->route('student.classroom.show', $meeting)->with('success', 'تم إنهاء الاجتماع.');
    }

    /**
     * حجز التدريس 1:1 يبقى على Classroom/LiveKit (وليس جلسات البث الجماعي LiveSession).
     * عند إنهاء غرفة مربوطة بحجز مؤكد: خصم الرصيد مرة واحدة.
     */
    private function completeLinkedTutoringBookingIfNeeded(ClassroomMeeting $meeting): bool
    {
        $bookingId = $meeting->tutoring_group_booking_id;
        if (! $bookingId) {
            $bookingId = TutoringGroupBooking::query()
                ->where('classroom_meeting_id', $meeting->id)
                ->value('id');
        }
        if (! $bookingId) {
            return false;
        }

        $booking = TutoringGroupBooking::query()->find($bookingId);
        if (! $booking || $booking->status !== TutoringGroupBooking::STATUS_CONFIRMED) {
            return false;
        }

        // يتطلب بدء الاجتماع فعلياً؛ لا نخصم إن أُغلق قبل البدء
        if (! $meeting->started_at) {
            return false;
        }

        try {
            TutoringGroupOrchestrationService::completeBooking($booking);

            return true;
        } catch (\InvalidArgumentException $e) {
            Log::info('Tutoring auto-complete skipped: '.$e->getMessage(), [
                'meeting_id' => $meeting->id,
                'booking_id' => $booking->id,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Tutoring auto-complete failed: '.$e->getMessage(), [
                'meeting_id' => $meeting->id,
                'booking_id' => $booking->id,
            ]);

            return false;
        }
    }

    /**
     * عند إنهاء غرفة حصة 1:1: تعليم الحصة كمكتملة حتى يظهر التسجيل للطالب.
     */
    private function completeLinkedOneToOneIfNeeded(ClassroomMeeting $meeting): bool
    {
        $sessionId = $meeting->one_to_one_session_id;
        if (! $sessionId) {
            $sessionId = OneToOneSession::query()
                ->where('classroom_meeting_id', $meeting->id)
                ->value('id');
        }
        if (! $sessionId || ! $meeting->started_at) {
            return false;
        }

        $session = OneToOneSession::query()->find($sessionId);
        if (! $session || $session->status === OneToOneSession::STATUS_COMPLETED) {
            return false;
        }

        try {
            OneToOneSessionService::markCompleted($session, true);

            return true;
        } catch (\InvalidArgumentException $e) {
            Log::info('One-to-one auto-complete skipped: '.$e->getMessage(), [
                'meeting_id' => $meeting->id,
                'session_id' => $session->id,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('One-to-one auto-complete failed: '.$e->getMessage(), [
                'meeting_id' => $meeting->id,
                'session_id' => $session->id,
            ]);

            return false;
        }
    }

    public function uploadRecording(Request $request, ClassroomMeeting $meeting)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($blocked = $this->ensureRecordingUploadAllowed($meeting)) {
            return $blocked;
        }

        try {
            $validated = $request->validate([
                // max بالكيلوبايت في Laravel — 1048576 ≈ 1 جيجابايت
                'recording' => ['required', 'file', 'max:1048576'],
                'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:43200'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'فشل التحقق من الملف المرفوع.',
                'errors' => $e->errors(),
            ], 422);
        }

        $file = $validated['recording'];

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        }
        if (! in_array($ext, ['webm', 'mp4', 'ogg', 'mkv'], true)) {
            return response()->json([
                'message' => 'امتداد الملف غير مدعوم. يُتوقع تسجيل المتصفح بصيغة webm.',
            ], 422);
        }

        if ($file->getSize() <= 0) {
            return response()->json([
                'message' => 'الملف المرفوع فارغ. أعد المحاولة من Chrome أو Edge، واضغط «إيقاف التسجيل» قبل إغلاق مشاركة الشاشة.',
            ], 422);
        }

        $mime = strtolower((string) $file->getMimeType());
        $allowedMimes = [
            'video/webm', 'video/mp4', 'video/quicktime', 'video/x-matroska',
            'audio/webm', 'audio/ogg', 'application/octet-stream', 'binary/octet-stream',
        ];
        if ($mime !== '' && ! in_array($mime, $allowedMimes, true)) {
            return response()->json([
                'message' => 'نوع الملف غير متوقع ('.$mime.'). إن استمر ذلك، جرّب متصفحاً آخر.',
            ], 422);
        }

        $disk = Storage::disk('live_recordings_r2');
        $directory = 'classroom-recordings/'.now()->format('Y/m');
        $fileName = sprintf('meeting-%d-%s.%s', $meeting->id, now()->format('Ymd-His'), $ext ?: 'webm');
        $newPath = $directory.'/'.$fileName;

        $oldPath = ($meeting->recording_disk === 'live_recordings_r2') ? $meeting->recording_path : null;

        try {
            $disk->putFileAs($directory, $file, $fileName);
        } catch (\Throwable $e) {
            \Log::error('Classroom recording upload failed', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'تعذر حفظ التسجيل على التخزين السحابي. حاول لاحقاً أو تواصل مع الدعم.',
            ], 500);
        }

        if ($oldPath && $oldPath !== $newPath) {
            try {
                $disk->delete($oldPath);
            } catch (\Throwable $e) {
                // تجاهل فشل حذف التسجيل القديم حتى لا يتعطل حفظ الجديد.
            }
        }

        $meeting->update([
            'recording_disk' => 'live_recordings_r2',
            'recording_path' => $newPath,
            'recording_mime_type' => $file->getMimeType(),
            'recording_size' => $file->getSize(),
            'recording_duration_seconds' => (int) ($validated['duration_seconds'] ?? 0),
            'recording_uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم رفع وحفظ تسجيل المحاضرة بنجاح.',
            'download_url' => $meeting->fresh()->recording_download_url,
        ]);
    }

    /**
     * رابط موقّع لرفع التسجيل مباشرة من المتصفح إلى R2/S3 (يتجاوز حدود PHP و nginx لحجم الطلب).
     */
    public function presignRecordingUpload(Request $request, ClassroomMeeting $meeting)
    {
        @set_time_limit(120);

        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($blocked = $this->ensureRecordingUploadAllowed($meeting)) {
            return $blocked;
        }

        $disk = Storage::disk('live_recordings_r2');
        if (! $disk->providesTemporaryUploadUrls()) {
            return response()->json([
                'direct_upload' => false,
                'message' => 'التخزين الحالي لا يدعم الرفع المباشر؛ سيتم الرفع عبر الخادم.',
            ]);
        }

        $validated = $request->validate([
            'content_type' => ['nullable', 'string', 'max:191'],
        ]);

        $mime = $this->normalizeRecordingMime((string) ($validated['content_type'] ?? 'video/webm'));
        $ext = $this->mimeToRecordingExt($mime);

        $directory = 'classroom-recordings/'.now()->format('Y/m');
        $fileName = sprintf(
            'meeting-%d-%s-%s.%s',
            $meeting->id,
            now()->format('Ymd-His-u'),
            Str::lower(Str::random(10)),
            $ext
        );
        $newPath = $directory.'/'.$fileName;

        $token = Str::random(64);
        Cache::put(
            'classroom_recording_presign:'.$token,
            [
                'path' => $newPath,
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'mime' => $mime,
            ],
            now()->addMinutes(90)
        );

        try {
            $signed = $disk->temporaryUploadUrl(
                $newPath,
                now()->addMinutes(75),
                [
                    'ContentType' => $mime,
                ]
            );
        } catch (\Throwable $e) {
            Cache::forget('classroom_recording_presign:'.$token);
            \Log::error('Classroom recording presign failed', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'direct_upload' => false,
                'message' => 'تعذر تجهيز رابط الرفع إلى التخزين السحابي. تحقق من إعدادات الموقع.',
            ], 503);
        }

        return response()->json([
            'direct_upload' => true,
            'upload_url' => $signed['url'],
            'upload_token' => $token,
            'content_type' => $mime,
            'headers' => $signed['headers'] ?? [],
        ]);
    }

    /**
     * بعد PUT الناجح إلى R2: ربط الملف بالاجتماع (طلب JSON صغير لا يتأثر بحدود رفع الملف).
     */
    public function completeDirectRecordingUpload(Request $request, ClassroomMeeting $meeting)
    {
        @set_time_limit(120);

        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($blocked = $this->ensureRecordingUploadAllowed($meeting)) {
            return $blocked;
        }

        $validated = $request->validate([
            'upload_token' => ['required', 'string', 'size:64'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:43200'],
        ]);

        $cacheKey = 'classroom_recording_presign:'.$validated['upload_token'];
        $payload = Cache::pull($cacheKey);
        if (! is_array($payload)
            || (int) ($payload['meeting_id'] ?? 0) !== (int) $meeting->id
            || (int) ($payload['user_id'] ?? 0) !== (int) $user->id) {
            return response()->json([
                'message' => 'انتهت صلاحية رابط الرفع أو أنه غير صالح. أعد محاولة الرفع.',
            ], 422);
        }

        $path = (string) ($payload['path'] ?? '');
        $mime = (string) ($payload['mime'] ?? 'video/webm');
        if ($path === '' || str_contains($path, '..')) {
            return response()->json(['message' => 'مسار التخزين غير صالح.'], 422);
        }

        $disk = Storage::disk('live_recordings_r2');
        if (! $this->waitForStorageObject($disk, $path)) {
            return response()->json([
                'message' => 'الملف غير ظاهر على التخزين بعد. انتظر ثانية ثم أعد تأكيد الرفع، أو أعد الرفع من جديد.',
            ], 422);
        }

        $size = (int) $disk->size($path);
        $maxBytes = 2147483648;

        if ($size <= 0) {
            return response()->json(['message' => 'الملف المرفوع فارغ.'], 422);
        }

        if ($size > $maxBytes) {
            try {
                $disk->delete($path);
            } catch (\Throwable $e) {
            }

            return response()->json(['message' => 'حجم التسجيل يتجاوز الحد المسموح (٢ جيجابايت).'], 422);
        }

        $oldPath = ($meeting->recording_disk === 'live_recordings_r2') ? $meeting->recording_path : null;

        if ($oldPath && $oldPath !== $path) {
            try {
                $disk->delete($oldPath);
            } catch (\Throwable $e) {
            }
        }

        $meeting->update([
            'recording_disk' => 'live_recordings_r2',
            'recording_path' => $path,
            'recording_mime_type' => $mime,
            'recording_size' => $size,
            'recording_duration_seconds' => (int) ($validated['duration_seconds'] ?? 0),
            'recording_uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم رفع وحفظ تسجيل المحاضرة بنجاح.',
            'download_url' => $meeting->fresh()->recording_download_url,
        ]);
    }

    /**
     * رابط موقّع لرفع ملف الصوت المنفصل مباشرة إلى R2/S3.
     */
    public function presignAudioUpload(Request $request, ClassroomMeeting $meeting)
    {
        @set_time_limit(120);

        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($blocked = $this->ensureRecordingUploadAllowed($meeting)) {
            return $blocked;
        }

        $disk = Storage::disk('live_recordings_r2');
        if (! $disk->providesTemporaryUploadUrls()) {
            return response()->json([
                'direct_upload' => false,
                'message' => 'التخزين الحالي لا يدعم الرفع المباشر.',
            ]);
        }

        $validated = $request->validate([
            'content_type' => ['nullable', 'string', 'max:191'],
        ]);

        $mime = $this->normalizeAudioMime((string) ($validated['content_type'] ?? 'audio/webm'));
        $ext = $this->mimeToAudioExt($mime);
        $directory = 'classroom-recordings-audio/'.now()->format('Y/m');
        $fileName = sprintf(
            'meeting-%d-audio-%s-%s.%s',
            $meeting->id,
            now()->format('Ymd-His-u'),
            Str::lower(Str::random(10)),
            $ext
        );
        $newPath = $directory.'/'.$fileName;

        $token = Str::random(64);
        Cache::put(
            'classroom_audio_presign:'.$token,
            [
                'path' => $newPath,
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'mime' => $mime,
            ],
            now()->addMinutes(90)
        );

        try {
            $signed = $disk->temporaryUploadUrl(
                $newPath,
                now()->addMinutes(75),
                [
                    'ContentType' => $mime,
                ]
            );
        } catch (\Throwable $e) {
            Cache::forget('classroom_audio_presign:'.$token);
            \Log::error('Classroom audio presign failed', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'direct_upload' => false,
                'message' => 'تعذر تجهيز رابط رفع الملف الصوتي إلى التخزين السحابي.',
            ], 503);
        }

        return response()->json([
            'direct_upload' => true,
            'upload_url' => $signed['url'],
            'upload_token' => $token,
            'content_type' => $mime,
            'headers' => $signed['headers'] ?? [],
        ]);
    }

    /**
     * رفع ملف الصوت عبر السيرفر (fallback عند عدم دعم direct upload).
     */
    public function uploadAudioRecording(Request $request, ClassroomMeeting $meeting)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if (! $meeting->started_at) {
            return response()->json(['message' => 'لا يمكن رفع تسجيل صوتي لاجتماع لم يبدأ بعد.'], 422);
        }

        try {
            $validated = $request->validate([
                'recording_audio' => ['required', 'file', 'max:1048576'],
                'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:43200'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'فشل التحقق من الملف الصوتي المرفوع.',
                'errors' => $e->errors(),
            ], 422);
        }

        $file = $validated['recording_audio'];
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        }
        if (! in_array($ext, ['webm', 'ogg', 'm4a', 'mp3', 'mp4'], true)) {
            return response()->json(['message' => 'امتداد الصوت غير مدعوم.'], 422);
        }

        if ($file->getSize() <= 0) {
            return response()->json(['message' => 'الملف الصوتي فارغ.'], 422);
        }

        $disk = Storage::disk('live_recordings_r2');
        $directory = 'classroom-recordings-audio/'.now()->format('Y/m');
        $baseName = sprintf('meeting-%d-audio-%s', $meeting->id, now()->format('Ymd-His'));
        $fileName = $baseName.'.'.($ext ?: 'webm');
        $newPath = $directory.'/'.$fileName;
        $oldAudioPath = ($meeting->recording_disk === 'live_recordings_r2') ? $meeting->recording_audio_path : null;

        $finalPath = $newPath;
        $finalMime = (string) $file->getMimeType();
        $finalSize = (int) $file->getSize();
        $tempSourcePath = null;
        $tempMp3Path = null;

        try {
            if ($this->shouldConvertAudioToMp3($finalMime, $ext)) {
                if ($this->canConvertAudioLocally()) {
                    $this->ensureFfmpegAvailable();
                    $tempSourcePath = tempnam(sys_get_temp_dir(), 'mx-audio-src-');
                    $tempMp3Path = tempnam(sys_get_temp_dir(), 'mx-audio-mp3-');
                    if ($tempSourcePath === false || $tempMp3Path === false) {
                        throw new \RuntimeException('تعذر تجهيز ملفات مؤقتة لتحويل الصوت.');
                    }
                    file_put_contents($tempSourcePath, file_get_contents($file->getRealPath()));
                    $this->convertLocalAudioFileToMp3($tempSourcePath, $tempMp3Path);

                    $mp3Path = $directory.'/'.$baseName.'-converted.mp3';
                    $stream = fopen($tempMp3Path, 'rb');
                    if (! is_resource($stream)) {
                        throw new \RuntimeException('تعذر فتح ملف mp3 بعد التحويل.');
                    }
                    $disk->put($mp3Path, $stream);
                    fclose($stream);

                    $finalPath = $mp3Path;
                    $finalMime = 'audio/mpeg';
                    $finalSize = (int) filesize($tempMp3Path);
                } elseif ($this->vpsAudioConvertEnabled()) {
                    // لا يمكن تشغيل ffmpeg على الاستضافات المشتركة عند تعطيل exec.
                    // نرفع الملف كما هو ثم نطلب التحويل على VPS عبر روابط موقعة (presigned).
                    $disk->putFileAs($directory, $file, $fileName);
                    $converted = $this->convertStoredAudioPathToMp3ViaVps($disk, $newPath);
                    $finalPath = (string) ($converted['path'] ?? $newPath);
                    $finalMime = (string) ($converted['mime'] ?? 'audio/mpeg');
                    $finalSize = (int) ($converted['size'] ?? $finalSize);
                    if ($finalPath !== $newPath) {
                        try {
                            $disk->delete($newPath);
                        } catch (\Throwable $e) {
                        }
                    }
                } else {
                    throw new \RuntimeException('ffmpeg is unavailable');
                }
            } else {
                $disk->putFileAs($directory, $file, $fileName);
            }
        } catch (\Throwable $e) {
            \Log::error('Classroom audio upload failed', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
            ]);
            $msg = str_contains((string) $e->getMessage(), 'ffmpeg')
                ? 'تعذر تحويل الصوت إلى MP3: ffmpeg غير متاح على الخادم.'
                : 'تعذر رفع/تحويل ملف الصوت إلى MP3.';
            return response()->json([
                'message' => $msg,
            ], 500);
        } finally {
            if ($tempSourcePath && is_file($tempSourcePath)) {
                @unlink($tempSourcePath);
            }
            if ($tempMp3Path && is_file($tempMp3Path)) {
                @unlink($tempMp3Path);
            }
        }

        if ($oldAudioPath && $oldAudioPath !== $finalPath) {
            try {
                $disk->delete($oldAudioPath);
            } catch (\Throwable $e) {
            }
        }

        $meeting->update([
            'recording_disk' => 'live_recordings_r2',
            'recording_audio_path' => $finalPath,
            'recording_audio_mime_type' => $finalMime,
            'recording_audio_size' => $finalSize,
            'recording_audio_duration_seconds' => (int) ($validated['duration_seconds'] ?? 0),
            'recording_uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم رفع وحفظ التسجيل الصوتي بنجاح.',
            'audio_download_url' => $meeting->fresh()->recording_audio_download_url,
        ]);
    }

    /**
     * بعد PUT الناجح للصوت: ربط ملف الصوت بالاجتماع.
     */
    public function completeDirectAudioUpload(Request $request, ClassroomMeeting $meeting)
    {
        @set_time_limit(120);

        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($blocked = $this->ensureRecordingUploadAllowed($meeting)) {
            return $blocked;
        }

        $validated = $request->validate([
            'upload_token' => ['required', 'string', 'size:64'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:43200'],
        ]);

        $cacheKey = 'classroom_audio_presign:'.$validated['upload_token'];
        $payload = Cache::pull($cacheKey);
        if (! is_array($payload)
            || (int) ($payload['meeting_id'] ?? 0) !== (int) $meeting->id
            || (int) ($payload['user_id'] ?? 0) !== (int) $user->id) {
            return response()->json([
                'message' => 'انتهت صلاحية رابط رفع الصوت أو أنه غير صالح.',
            ], 422);
        }

        $path = (string) ($payload['path'] ?? '');
        $mime = (string) ($payload['mime'] ?? 'audio/webm');
        if ($path === '' || str_contains($path, '..')) {
            return response()->json(['message' => 'مسار التخزين غير صالح.'], 422);
        }

        $disk = Storage::disk('live_recordings_r2');
        if (! $this->waitForStorageObject($disk, $path)) {
            return response()->json([
                'message' => 'ملف الصوت غير ظاهر على التخزين بعد. انتظر ثانية ثم أعد التأكيد.',
            ], 422);
        }

        $size = (int) $disk->size($path);
        if ($size <= 0) {
            return response()->json(['message' => 'ملف الصوت المرفوع فارغ.'], 422);
        }

        $maxBytes = 2147483648;
        if ($size > $maxBytes) {
            try {
                $disk->delete($path);
            } catch (\Throwable $e) {
            }

            return response()->json(['message' => 'حجم ملف الصوت يتجاوز الحد المسموح (٢ جيجابايت).'], 422);
        }

        $finalPath = $path;
        $finalMime = $mime;
        $finalSize = $size;

        if ($this->shouldConvertAudioToMp3($mime, pathinfo($path, PATHINFO_EXTENSION))) {
            try {
                if ($this->canConvertAudioLocally()) {
                    $this->ensureFfmpegAvailable();
                    $converted = $this->convertStoredAudioPathToMp3($disk, $path);
                } elseif ($this->vpsAudioConvertEnabled()) {
                    $converted = $this->convertStoredAudioPathToMp3ViaVps($disk, $path);
                } else {
                    throw new \RuntimeException('ffmpeg is unavailable');
                }

                if (is_array($converted)) {
                    $finalPath = (string) ($converted['path'] ?? $path);
                    $finalMime = (string) ($converted['mime'] ?? 'audio/mpeg');
                    $finalSize = (int) ($converted['size'] ?? $size);
                    if ($finalPath !== $path) {
                        try {
                            $disk->delete($path);
                        } catch (\Throwable $e) {
                        }
                    }
                }
            } catch (\Throwable $e) {
                try {
                    $disk->delete($path);
                } catch (\Throwable $deleteErr) {
                }
                Log::warning('Classroom direct audio conversion to mp3 failed (strict)', [
                    'meeting_id' => $meeting->id,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                $message = str_contains((string) $e->getMessage(), 'ffmpeg')
                    ? 'فشل التحويل إلى MP3 لأن ffmpeg غير متاح على الخادم.'
                    : 'فشل تحويل ملف الصوت إلى MP3. أعد المحاولة.';
                return response()->json(['message' => $message], 422);
            }
        }

        $oldAudioPath = ($meeting->recording_disk === 'live_recordings_r2') ? $meeting->recording_audio_path : null;
        if ($oldAudioPath && $oldAudioPath !== $finalPath) {
            try {
                $disk->delete($oldAudioPath);
            } catch (\Throwable $e) {
            }
        }

        $meeting->update([
            'recording_disk' => 'live_recordings_r2',
            'recording_audio_path' => $finalPath,
            'recording_audio_mime_type' => $finalMime,
            'recording_audio_size' => $finalSize,
            'recording_audio_duration_seconds' => (int) ($validated['duration_seconds'] ?? 0),
            'recording_uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم رفع وحفظ التسجيل الصوتي بنجاح.',
            'audio_download_url' => $meeting->fresh()->recording_audio_download_url,
        ]);
    }

    /**
     * إرسال التسجيل/التقرير الصوتي إلى n8n لإنشاء تقرير نصي عن المحاضرة.
     */
    public function generateAiReport(Request $request, ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if (! $meeting->ended_at) {
            return back()->with('error', 'يمكن إنشاء التقرير النصي بعد إنهاء الاجتماع.');
        }

        $existing = ClassroomMeetingReport::where('classroom_meeting_id', $meeting->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existing) {
            return back()->with('info', 'هناك طلب تقرير قيد المعالجة بالفعل لهذا الاجتماع.');
        }

        $meetingFresh = $meeting->fresh();
        $audioUrl = $meetingFresh->recording_audio_download_url;
        $audioMime = strtolower((string) ($meetingFresh->recording_audio_mime_type ?? ''));

        if (! $audioUrl) {
            return back()->with('error', 'يجب رفع التقرير الصوتي بصيغة MP3 أولاً قبل إنشاء التقرير.');
        }
        if ($audioMime !== 'audio/mpeg') {
            return back()->with('error', 'ملف التقرير الصوتي الحالي ليس MP3. أعد الرفع بعد تفعيل ffmpeg.');
        }

        $report = ClassroomMeetingReport::create([
            'classroom_meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'title' => 'تقرير الاجتماع — '.$meeting->title,
            'status' => 'pending',
            'audio_path' => $meetingFresh->recording_audio_path,
            'storage_disk' => $meetingFresh->recording_disk,
        ]);

        $callbackUrl = url('/api/n8n/classroom-meeting-reports/'.$report->id);
        $webhookUrl = IntegrationSetting::get('n8n_live_session_report_webhook', config('services.n8n.live_session_report_webhook'));
        $token = IntegrationSetting::get('n8n_token', config('services.n8n.token'));

        if (! $webhookUrl || ! $token) {
            $report->update(['status' => 'failed']);

            return back()->with('error', 'إعدادات خدمة إنشاء التقارير غير مكتملة. تواصل مع مدير النظام.');
        }

        try {
            $response = Http::timeout(45)
                ->connectTimeout(10)
                ->withHeaders([
                    'X-N8N-Token' => $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post($webhookUrl, [
                    'source' => 'classroom_meeting',
                    'report_id' => $report->id,
                    'classroom_meeting_id' => $meeting->id,
                    'user_id' => $user->id,
                    'title' => $report->title,
                    'meeting_title' => $meeting->title,
                    'meeting_code' => $meeting->code,
                    'meeting_ended_at' => optional($meeting->ended_at)->toIso8601String(),
                    'recording' => [
                        'storage_disk' => $meetingFresh->recording_disk,
                        'audio_path' => $meetingFresh->recording_audio_path,
                        'video_path' => $meetingFresh->recording_path,
                        'audio_mime_type' => $meetingFresh->recording_audio_mime_type,
                        'video_mime_type' => $meetingFresh->recording_mime_type,
                        'audio_download_url' => $audioUrl,
                        'video_download_url' => $meetingFresh->recording_download_url,
                        'download_url' => $audioUrl,
                    ],
                    'callback' => [
                        'url' => $callbackUrl,
                        'method' => 'PATCH',
                        'header' => 'X-N8N-Token',
                    ],
                ]);

            if ($response->successful()) {
                $executionId = $response->json('execution_id');
                if ($executionId) {
                    $report->update([
                        'n8n_execution_id' => $executionId,
                        'status' => 'processing',
                    ]);
                } else {
                    $report->update(['status' => 'processing']);
                }
            } else {
                $report->update(['status' => 'failed']);

                Log::warning('n8n classroom-meeting report webhook failed', [
                    'classroom_meeting_id' => $meeting->id,
                    'report_id' => $report->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return back()->with('error', 'تعذر إرسال طلب إنشاء التقرير حالياً. حاول مرة أخرى لاحقاً.');
            }
        } catch (\Throwable $e) {
            $report->update(['status' => 'failed']);

            Log::error('n8n classroom-meeting report webhook exception', [
                'classroom_meeting_id' => $meeting->id,
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء الاتصال بخدمة إنشاء التقارير. الرجاء المحاولة لاحقاً.');
        }

        return back()->with('success', 'تم إرسال طلب إنشاء التقرير النصي بنجاح، جاري المعالجة.');
    }

    public function destroy(ClassroomMeeting $meeting)
    {
        $user = Auth::user();
        $this->ensureMeetingOwnership($meeting, $user);
        if ($meeting->isLive()) {
            return back()->with('error', 'لا يمكن حذف اجتماع مباشر. قم بإنهائه أولاً.');
        }

        $meeting->delete();

        return redirect()->route('student.classroom.index')->with('success', 'تم حذف الاجتماع.');
    }

    /**
     * Fixed classroom capacity (subscription package gating removed).
     *
     * @return array{classroom_meetings_per_month: int, classroom_max_participants: int, classroom_default_duration_minutes: int, classroom_max_duration_minutes: int}
     */
    private function classroomLimits(): array
    {
        return [
            'classroom_meetings_per_month' => 9999,
            'classroom_max_participants' => 50,
            'classroom_default_duration_minutes' => 60,
            'classroom_max_duration_minutes' => 180,
        ];
    }

    private function monthlyClassroomUsage($user): int
    {
        return ClassroomMeeting::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    private function classroomRoomUrl(ClassroomMeeting $meeting): string
    {
        if (request()->routeIs('instructor.*')) {
            return route('instructor.classroom.room', $meeting);
        }

        return route('student.classroom.room', $meeting);
    }

    private function classroomRoomExitUrl(ClassroomMeeting $meeting, $user, bool $isHost): string
    {
        if ($meeting->one_to_one_session_id) {
            if ($isHost && Route::has('instructor.one-to-one-sessions.show')) {
                return route('instructor.one-to-one-sessions.show', $meeting->one_to_one_session_id);
            }
            if (Route::has('student.one-to-one-sessions.show')) {
                return route('student.one-to-one-sessions.show', $meeting->one_to_one_session_id);
            }
        }

        if ($meeting->tutoring_group_booking_id) {
            if ($isHost && Route::has('instructor.tutoring-bookings.show')) {
                return route('instructor.tutoring-bookings.show', $meeting->tutoring_group_booking_id);
            }
            if (Route::has('student.tutoring-bookings.show')) {
                return route('student.tutoring-bookings.show', $meeting->tutoring_group_booking_id);
            }
        }

        if ($isHost && request()->routeIs('instructor.*') && Route::has('instructor.classroom.index')) {
            return route('instructor.classroom.index');
        }

        return route('dashboard');
    }

    private function ensureMeetingOwnership(ClassroomMeeting $meeting, $user): void
    {
        if (! ClassroomMeetingAccessService::userIsHost($meeting, $user)
            && (int) $meeting->user_id !== (int) $user->id
            && ! $user->isAdmin()) {
            abort(403);
        }
    }

    /**
     * يسمح برفع/إكمال التسجيل بعد بدء الاجتماع — حتى بعد الإنهاء لإتاحة الرفع الجاري.
     * الرفع المباشر إلى R2 يتم من المتصفح (عدة اجتماعات متزامنة دون ضغط على Laravel).
     */
    private function ensureRecordingUploadAllowed(ClassroomMeeting $meeting): ?\Illuminate\Http\JsonResponse
    {
        if (! $meeting->started_at) {
            return response()->json(['message' => 'لا يمكن رفع تسجيل لاجتماع لم يبدأ بعد.'], 422);
        }

        return null;
    }

    private function waitForStorageObject($disk, string $path, int $attempts = 6): bool
    {
        for ($i = 0; $i < $attempts; $i++) {
            if ($disk->exists($path)) {
                return true;
            }
            usleep(350000);
        }

        return $disk->exists($path);
    }

    private function ensureMeetingCanEnter(ClassroomMeeting $meeting, $user): void
    {
        if (ClassroomMeetingAccessService::userCanEnter($meeting, $user)) {
            return;
        }

        if ($user?->isStudent() && Schema::hasTable('one_to_one_sessions')) {
            $session = null;
            if ($meeting->one_to_one_session_id) {
                $session = OneToOneSession::query()->find($meeting->one_to_one_session_id);
            }
            if (! $session) {
                $session = OneToOneSession::query()
                    ->where('classroom_meeting_id', $meeting->id)
                    ->first();
            }
            if ($session) {
                $reason = OneToOneSessionUnlockService::lockReason($session, $user);
                if ($reason) {
                    abort(403, $reason);
                }
            }
        }

        abort(403, 'غير مصرح لك بدخول هذه الغرفة. الدخول من حسابك داخل المنصة فقط.');
    }

    private function normalizeRecordingMime(string $mime): string
    {
        $mime = strtolower(trim($mime));
        $allowed = [
            'video/webm', 'video/mp4', 'video/quicktime', 'video/x-matroska',
            'audio/webm', 'audio/ogg', 'application/octet-stream', 'binary/octet-stream',
        ];
        if ($mime !== '' && in_array($mime, $allowed, true)) {
            return $mime;
        }

        return 'video/webm';
    }

    private function mimeToRecordingExt(string $mime): string
    {
        return match ($mime) {
            'video/mp4', 'video/quicktime' => 'mp4',
            'video/x-matroska' => 'mkv',
            'audio/ogg', 'video/ogg' => 'ogg',
            default => 'webm',
        };
    }

    private function normalizeAudioMime(string $mime): string
    {
        $mime = strtolower(trim($mime));
        $allowed = [
            'audio/webm', 'audio/ogg', 'audio/mp4', 'audio/mpeg',
            'application/octet-stream', 'binary/octet-stream',
        ];
        if ($mime !== '' && in_array($mime, $allowed, true)) {
            return $mime;
        }

        return 'audio/webm';
    }

    private function mimeToAudioExt(string $mime): string
    {
        return match ($mime) {
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/mpeg' => 'mp3',
            default => 'webm',
        };
    }

    private function shouldConvertAudioToMp3(?string $mime, ?string $extension): bool
    {
        $mime = strtolower(trim((string) $mime));
        $extension = strtolower(trim((string) $extension));

        if ($mime === 'audio/mpeg' || $extension === 'mp3') {
            return false;
        }

        return true;
    }

    private function ffmpegBinaryPath(): string
    {
        return (string) env('FFMPEG_PATH', 'ffmpeg');
    }

    private function ensureFfmpegAvailable(): void
    {
        if (! function_exists('exec')) {
            throw new \RuntimeException('ffmpeg is unavailable');
        }
        $ffmpeg = $this->ffmpegBinaryPath();
        $cmd = sprintf('%s -version 2>&1', escapeshellarg($ffmpeg));
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \RuntimeException('ffmpeg is unavailable');
        }
    }

    private function canConvertAudioLocally(): bool
    {
        return function_exists('exec');
    }

    private function vpsAudioConvertEnabled(): bool
    {
        return trim((string) env('VPS_AUDIO_CONVERT_URL', '')) !== ''
            && trim((string) env('VPS_AUDIO_CONVERT_TOKEN', '')) !== '';
    }

    /**
     * تحويل ملف صوت مخزن على R2 إلى MP3 عبر خدمة خارجية (VPS) باستخدام روابط GET/PUT موقّعة.
     *
     * يتطلب:
     * - VPS_AUDIO_CONVERT_URL
     * - VPS_AUDIO_CONVERT_TOKEN
     *
     * ملاحظة: يتطلب تفعيل CORS في R2 للـ bucket للسماح بالـ PUT من VPS.
     */
    private function convertStoredAudioPathToMp3ViaVps($disk, string $path): array
    {
        $url = trim((string) env('VPS_AUDIO_CONVERT_URL', ''));
        $token = (string) env('VPS_AUDIO_CONVERT_TOKEN', '');
        if ($url === '' || $token === '') {
            throw new \RuntimeException('VPS audio convert service is not configured');
        }

        $sourceUrl = $disk->temporaryUrl($path, now()->addMinutes(40));
        $targetPath = preg_replace('/\.[a-z0-9]+$/i', '', $path).'.mp3';

        $signedPut = $disk->temporaryUploadUrl(
            $targetPath,
            now()->addMinutes(40),
            ['ContentType' => 'audio/mpeg']
        );

        $resp = Http::timeout(600)
            ->connectTimeout(10)
            ->withHeaders([
                'X-Mx-Token' => $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'source_url' => $sourceUrl,
                'upload_put_url' => $signedPut['url'],
            ]);

        if (! $resp->successful()) {
            throw new \RuntimeException('VPS audio convert failed: '.$resp->status().' '.$resp->body());
        }

        if (! $disk->exists($targetPath)) {
            // قد يكون هناك تأخير بسيط في ظهور الملف على التخزين.
            usleep(350000);
        }
        if (! $disk->exists($targetPath)) {
            throw new \RuntimeException('VPS audio convert finished but mp3 not found on storage');
        }

        return [
            'path' => $targetPath,
            'mime' => 'audio/mpeg',
            'size' => (int) $disk->size($targetPath),
        ];
    }

    private function convertLocalAudioFileToMp3(string $sourcePath, string $targetMp3Path): void
    {
        $ffmpeg = $this->ffmpegBinaryPath();
        $cmd = sprintf(
            '%s -y -i %s -vn -acodec libmp3lame -b:a 128k %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($sourcePath),
            escapeshellarg($targetMp3Path)
        );
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0 || ! is_file($targetMp3Path) || (int) filesize($targetMp3Path) <= 0) {
            throw new \RuntimeException('فشل تحويل الصوت إلى mp3 عبر ffmpeg. '.implode("\n", $output));
        }
    }

    private function convertStoredAudioPathToMp3($disk, string $path): ?array
    {
        $read = $disk->readStream($path);
        if (! is_resource($read)) {
            return null;
        }

        $tempSource = tempnam(sys_get_temp_dir(), 'mx-r2-audio-src-');
        $tempMp3 = tempnam(sys_get_temp_dir(), 'mx-r2-audio-mp3-');
        if ($tempSource === false || $tempMp3 === false) {
            if (is_resource($read)) {
                fclose($read);
            }
            throw new \RuntimeException('تعذر إنشاء ملف مؤقت لتحويل الصوت.');
        }

        try {
            $write = fopen($tempSource, 'wb');
            if (! is_resource($write)) {
                throw new \RuntimeException('تعذر كتابة الملف المؤقت قبل التحويل.');
            }
            stream_copy_to_stream($read, $write);
            fclose($write);
            fclose($read);

            $this->convertLocalAudioFileToMp3($tempSource, $tempMp3);

            $targetPath = preg_replace('/\.[a-z0-9]+$/i', '', $path).'.mp3';
            $stream = fopen($tempMp3, 'rb');
            if (! is_resource($stream)) {
                throw new \RuntimeException('تعذر فتح ملف mp3 الناتج.');
            }
            $disk->put($targetPath, $stream);
            fclose($stream);

            return [
                'path' => $targetPath,
                'mime' => 'audio/mpeg',
                'size' => (int) filesize($tempMp3),
            ];
        } finally {
            if (is_resource($read)) {
                fclose($read);
            }
            if (is_file($tempSource)) {
                @unlink($tempSource);
            }
            if (is_file($tempMp3)) {
                @unlink($tempMp3);
            }
        }
    }
}
