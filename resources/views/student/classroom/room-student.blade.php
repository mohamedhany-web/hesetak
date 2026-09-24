<!DOCTYPE html>
<html lang="ar" dir="rtl" class="hstk-live-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $meeting->roomChromeTitle() }} — حصة مباشرة | حصتك</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('assets.hesetak-live-meeting.css') }}?v=hstk-live-3">
    <link rel="stylesheet" href="{{ asset('css/classroom-curriculum-presenter.css') }}">
    <script src="{{ asset('js/classroom-curriculum-presenter.js') }}" defer></script>
    <script src="{{ asset('js/classroom-whiteboard-sync.js') }}?v=wb-sync-2"></script>
    <style>
        .hidden{display:none!important}
        .hstk-excalidraw-host{width:100%;height:100%;min-height:280px}
        .hstk-excalidraw-host .excalidraw{width:100%!important;height:100%!important}
        .hstk-excalidraw-loading{position:absolute;inset:0;z-index:5;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.75);color:#94a3b8;font-size:14px}
        .hstk-wb-student-draw-lite .excalidraw button[data-testid^="toolbar-"]:not([data-testid="toolbar-freedraw"]):not([data-testid="toolbar-eraser"]):not([data-testid="toolbar-hand"]){display:none!important}
    </style>
</head>
<body class="hstk-live-body">
@php
    $roomExitUrl = $roomExitUrl ?? route('dashboard');
    $annPollUrl = \Illuminate\Support\Facades\Route::has('student.classroom.share-annotations')
        ? route('student.classroom.share-annotations', $meeting)
        : '';
    $curriculumStateUrl = \Illuminate\Support\Facades\Route::has('student.classroom.curriculum.state')
        ? route('student.classroom.curriculum.state', $meeting)
        : '';
    $roomStatusUrl = \Illuminate\Support\Facades\Route::has('student.classroom.room.status')
        ? route('student.classroom.room.status', $meeting)
        : '';
    $annPostUrl = \Illuminate\Support\Facades\Route::has('student.classroom.share-annotation')
        ? route('student.classroom.share-annotation', $meeting)
        : '';
    $roomHostName = $meeting->user?->name
        ?? $meeting->oneToOneSession?->instructor?->name
        ?? null;
    $subtitle = '';
    if ($roomHostName) {
        $subtitle .= e($roomHostName).' · ';
    }
    $subtitle .= '<span id="meeting-timer-chip">مدة الحصة: '.(int)($effectiveDurationMinutes ?? 50).' دقيقة</span>';
@endphp

    <div id="mx-session-ended" class="hstk-live-ended">
        <div class="hstk-live-ended__icon"><i class="fas fa-video"></i></div>
        <h2>انتهت الحصة</h2>
        <p>تم إنهاء الحصة الخاصة. سيظهر التسجيل في صفحة الحصة عند اكتمال رفعه.</p>
        <div class="hstk-live-ended__bar"><div id="mx-redir-fill" class="hstk-live-ended__fill"></div></div>
        <p style="font-size:12px;">سيتم توجيهك تلقائياً...</p>
        <a href="{{ $roomExitUrl }}" class="hstk-live-btn hstk-live-btn--gold">
            <i class="fas fa-arrow-left"></i> العودة الآن
        </a>
    </div>

    <span id="meeting-timer-chip-mobile" class="hidden" aria-hidden="true"></span>
    <div class="hstk-live-shell" data-role="student">
        @include('partials.hesetak-live-chrome', [
            'liveRole' => 'student',
            'liveBackUrl' => $roomExitUrl,
            'liveTitle' => $meeting->roomChromeTitle(),
            'liveKicker' => 'حصة خاصة',
            'liveSubtitle' => $subtitle,
            'liveActions' => view('partials.hesetak-live-chrome-actions-student', [
                'allowStudentWhiteboard' => !empty($meeting->allowsParticipantWhiteboard()),
                'showWhiteboard' => true,
                'leaveUrl' => $roomExitUrl,
                'leaveLinkId' => 'student-classroom-leave',
            ])->render(),
        ])

        <div class="hstk-live-body">
            <div id="mx-video-stack" class="hstk-live-stage">
                @if(!empty($livekitConfigured) && !empty($livekitToken) && !empty($livekitUrl))
                    @include('partials.hesetak-live-room', [
                        'livekitUrl' => $livekitUrl,
                        'livekitToken' => $livekitToken,
                        'user' => $user,
                        'lkRole' => 'participant',
                        'lkTheme' => 'student',
                        'lkLeaveUrl' => $roomExitUrl,
                        'lkAllowScreenShare' => false,
                        'lkHideLeave' => false,
                    ])
                @else
                    <div class="hstk-live-empty">
                        <div class="hstk-live-empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <p class="hstk-live-empty__title">إعدادات البث غير مكتملة</p>
                        <p class="hstk-live-empty__text">تحقق من مفاتيح LiveKit من لوحة الإدارة → سيرفرات البث.</p>
                        <a href="{{ $roomExitUrl }}" class="hstk-live-btn hstk-live-btn--gold">العودة</a>
                    </div>
                @endif

                @if($annPollUrl)
                    @include('partials.hesetak-live-share-annotation', [
                        'mxAnnRole' => 'emit_and_poll',
                        'mxAnnPostUrl' => $annPostUrl,
                        'mxAnnPollUrl' => $annPollUrl,
                        'mxAnnSelfKey' => (string) ($user->id ?? auth()->id() ?? ''),
                    ])
                @endif
            </div>
        </div>
    </div>

    @php
        $studentCanDrawWb = !empty($meeting->allowsParticipantWhiteboard());
        $hstkWbUiMode = $studentCanDrawWb ? 'student_lite' : 'full';
        $wbStateUrl = \Illuminate\Support\Facades\Route::has('student.classroom.whiteboard.state')
            ? route('student.classroom.whiteboard.state', $meeting)
            : '';
        $wbPushUrl = \Illuminate\Support\Facades\Route::has('student.classroom.whiteboard.push')
            ? route('student.classroom.whiteboard.push', $meeting)
            : '';
    @endphp
    {{-- يجب تعريف خيارات المزامنة قبل تضمين السبورة --}}
    <script>
        window.__mxWbSyncOptions = {
            role: 'participant',
            canEmit: {{ $studentCanDrawWb ? 'true' : 'false' }},
            canReceive: true,
            mergeRemote: {{ $studentCanDrawWb ? 'true' : 'false' }},
            viewOnly: {{ $studentCanDrawWb ? 'false' : 'true' }},
            stateUrl: @json($wbStateUrl),
            pushUrl: @json($wbPushUrl),
            csrf: @json(csrf_token()),
        };
    </script>
    @include('partials.hesetak-live-whiteboard-popup', ['hstkWbUiMode' => $hstkWbUiMode])

    <script>
        (function () {
            var roomExitUrl = @json($roomExitUrl);
            var roomStatusUrl = @json($roomStatusUrl);
            var meetingEndsAt = {!! json_encode(optional($meetingEndsAt ?? null)->toIso8601String()) !!};
            var timerChip = document.getElementById('meeting-timer-chip');
            var timerChipMobile = document.getElementById('meeting-timer-chip-mobile');
            var curriculumStateUrl = @json($curriculumStateUrl);
            var allowWbInitially = {{ !empty($meeting->allowsParticipantWhiteboard()) ? 'true' : 'false' }};
            var sessionEnded = false;

            var leaveLink = document.getElementById('student-classroom-leave');
            var lkLeave = document.getElementById('lk-leave');
            if (leaveLink && lkLeave) {
                lkLeave.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.location.href = roomExitUrl;
                });
            }

            function showSessionEndedAndRedirect() {
                if (sessionEnded) return;
                sessionEnded = true;
                if (typeof window.__mxLkLeaveRoom === 'function') {
                    window.__mxLkLeaveRoom();
                }
                var overlay = document.getElementById('mx-session-ended');
                var fill = document.getElementById('mx-redir-fill');
                if (!overlay) {
                    window.location.href = roomExitUrl;
                    return;
                }
                overlay.classList.add('is-show');
                setTimeout(function () { if (fill) fill.style.width = '100%'; }, 100);
                setTimeout(function () { window.location.href = roomExitUrl; }, 5500);
            }

            function tickMeetingTimer() {
                if (sessionEnded) return;
                if (!meetingEndsAt) return;
                var end = new Date(meetingEndsAt).getTime();
                var diff = end - Date.now();
                if (diff <= 0) {
                    if (timerChip) timerChip.textContent = 'انتهت المدة المسموح بها';
                    if (timerChipMobile) timerChipMobile.textContent = 'انتهت';
                    showSessionEndedAndRedirect();
                    return;
                }
                var mins = Math.floor(diff / 60000);
                var secs = Math.floor((diff % 60000) / 1000);
                var fullText = 'الوقت المتبقي: ' + mins + ':' + String(secs).padStart(2, '0');
                var shortText = mins + ':' + String(secs).padStart(2, '0');
                if (timerChip) timerChip.textContent = fullText;
                if (timerChipMobile) timerChipMobile.textContent = shortText;
            }
            setInterval(tickMeetingTimer, 1000);
            tickMeetingTimer();

            if (roomStatusUrl) {
                setInterval(function () {
                    if (sessionEnded) return;
                    fetch(roomStatusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) {
                            if (!data || sessionEnded) return;
                            if (data.ended === true) {
                                showSessionEndedAndRedirect();
                                return;
                            }
                            if (typeof data.allow_participant_whiteboard !== 'undefined') {
                                applyAllow(!!data.allow_participant_whiteboard);
                            }
                        })
                        .catch(function () {});
                }, 4000);
            }

            var wrap = document.getElementById('mx-student-wb-wrap');
            var drawBtn = document.getElementById('btn-mx-share-draw');
            function applyAllow(on) {
                if (typeof window.__mxShareAnnSetAllowed === 'function') {
                    window.__mxShareAnnSetAllowed(!!on);
                }
                if (window.__mxWbSyncOptions) {
                    window.__mxWbSyncOptions.canEmit = !!on;
                    window.__mxWbSyncOptions.mergeRemote = !!on;
                    window.__mxWbSyncOptions.viewOnly = !on;
                }
                var root = document.getElementById('mx-excalidraw-root');
                if (root) root.setAttribute('data-view-only', on ? '0' : '1');
                if (!wrap) return;
                if (on) wrap.classList.remove('hidden');
                else wrap.classList.add('hidden');
            }
            if (drawBtn && typeof window.__mxShareAnnOpenToolbar === 'function') {
                drawBtn.addEventListener('click', function () { window.__mxShareAnnOpenToolbar(); });
            }
            applyAllow(allowWbInitially);

            if (curriculumStateUrl) {
                function attachCurriculumViewer() {
                    if (!window.MxClassroomCurriculumPresenter || window.__mxCurriculumPresenter) return;
                    window.__mxCurriculumPresenter = window.MxClassroomCurriculumPresenter.attach(null, {
                        isHost: false,
                        catalogUrl: '',
                        presentUrl: '',
                        stateUrl: curriculumStateUrl,
                        slideUpdateUrl: '',
                        stopUrl: '',
                        pollIntervalMs: 1500,
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', attachCurriculumViewer);
                } else {
                    attachCurriculumViewer();
                }
                setTimeout(attachCurriculumViewer, 50);
            }
        })();
    </script>
</body>
</html>
