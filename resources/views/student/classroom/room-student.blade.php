<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $meeting->roomChromeTitle() }} — حصة مباشرة | حصتك</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/hesetak-live-meeting.css') }}?v=hstk-live-1">
    <link rel="stylesheet" href="{{ route('assets.student-timeline.css') }}?v=st-live-private-1">
    <link rel="stylesheet" href="{{ asset('css/classroom-curriculum-presenter.css') }}">
    <script src="{{ asset('js/classroom-curriculum-presenter.js') }}" defer></script>
    <script src="{{ asset('js/classroom-whiteboard-sync.js') }}?v=wb-sync-2"></script>
    <style>
        .hstk-excalidraw-host { width: 100%; height: 100%; min-height: 280px; }
        .hstk-excalidraw-host .excalidraw { width: 100% !important; height: 100% !important; }
        .hstk-excalidraw-loading {
            position: absolute; inset: 0; z-index: 5; display: none;
            align-items: center; justify-content: center;
            background: rgba(15,23,42,0.75); color: #94a3b8; font-size: 14px;
        }
        .hstk-wb-student-draw-lite .excalidraw button[data-testid^="toolbar-"]:not([data-testid="toolbar-freedraw"]):not([data-testid="toolbar-eraser"]):not([data-testid="toolbar-hand"]) {
            display: none !important;
        }
    </style>
    <style>
        :root {
            --st-bg: #f8f9fa;
            --st-ink: #4f4f4f;
            --st-ink-strong: #212523;
            --st-muted: #979797;
            --st-blue: #1E4E8C;
            --st-brand: #1E4E8C;
            --st-gold: #C9952A;
            --st-line: #ebebeb;
            --st-nav: #152A4A;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100%;
            font-family: 'IBM Plex Sans Arabic', 'Lato', 'Rubik', system-ui, sans-serif;
            background: var(--st-bg);
            color: var(--st-ink);
            overflow: hidden;
        }
        .st-live-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            background:
                radial-gradient(1200px 420px at 100% -10%, rgba(9,151,217,.12), transparent 55%),
                radial-gradient(900px 380px at 0% 0%, rgba(11,61,145,.10), transparent 50%),
                var(--st-bg);
        }
        .st-live-top {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            background: linear-gradient(135deg, var(--st-brand) 0%, var(--st-blue) 100%);
            color: #fff;
            box-shadow: 0 10px 28px rgba(11, 61, 145, .22);
        }
        .st-live-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
        }
        .st-live-brand__mark {
            width: 40px; height: 40px;
            border-radius: 12px;
            background: rgba(255,255,255,.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--st-gold);
        }
        .st-live-meta { min-width: 0; }
        .st-live-meta__kicker {
            margin: 0;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            opacity: .85;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .st-live-meta__title {
            margin: 2px 0 0;
            font-size: clamp(0.95rem, 2vw, 1.15rem);
            font-weight: 900;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: min(52vw, 420px);
        }
        .st-live-meta__sub {
            margin: 2px 0 0;
            font-size: 12px;
            font-weight: 600;
            opacity: .9;
        }
        .st-live-live-dot {
            width: 9px; height: 9px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 0 rgba(34,197,94,.45);
            animation: stLivePulse 1.4s infinite;
            display: inline-block;
        }
        @keyframes stLivePulse {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,.45); }
            70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
        }
        .st-live-actions { display: inline-flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .st-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .st-live-pill--gold { background: var(--st-gold); color: #072A66; }
        .st-live-pill--ghost {
            background: transparent;
            color: #fff;
            border: 1.5px solid rgba(255,255,255,.65);
        }
        .st-live-pill--soft {
            background: rgba(255,255,255,.16);
            color: #fff;
            border: 1px solid rgba(255,255,255,.22);
            pointer-events: none;
        }
        .st-live-body {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 6px;
        }
        .st-live-stage {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--st-line);
            background: #071226;
            box-shadow: 0 14px 36px rgba(7, 18, 38, .12);
            position: relative;
        }
        .st-live-stage .lk-room {
            flex: 1;
            min-height: 0;
            height: 100%;
            border-radius: inherit;
        }
        .st-live-stage .lk-main,
        .st-live-stage .lk-body {
            min-height: 0;
            flex: 1;
        }
        @media (min-width: 768px) {
            .st-live-body { padding: 8px; }
        }
        #mx-session-ended {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(248,249,250,.96);
            flex-direction: column; align-items: center; justify-content: center;
            gap: 14px; backdrop-filter: blur(8px);
            padding: 24px; text-align: center;
        }
        #mx-session-ended.show { display: flex; }
        #mx-session-ended .mx-icon {
            width: 72px; height: 72px; border-radius: 20px;
            display: grid; place-items: center;
            background: linear-gradient(135deg, var(--st-brand), var(--st-blue));
            color: var(--st-gold); font-size: 28px;
        }
        #mx-session-ended h2 { color: var(--st-ink-strong); font-size: 1.35rem; font-weight: 900; margin: 0; }
        #mx-session-ended p { color: var(--st-muted); font-size: 14px; margin: 0; font-weight: 600; }
        #mx-redir-bar {
            width: 220px; height: 5px; background: #e5e7eb;
            border-radius: 999px; overflow: hidden;
        }
        #mx-redir-fill {
            height: 100%; background: linear-gradient(90deg, var(--st-brand), var(--st-blue));
            width: 0; transition: width 5s linear;
        }
        @media (max-width: 640px) {
            .st-live-meta__title { max-width: 42vw; }
            .st-live-body { padding: 8px; }
        }
    </style>
</head>
<body>
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
@endphp

    <div id="mx-session-ended">
        <div class="mx-icon"><i class="fas fa-video"></i></div>
        <h2>انتهت الحصة</h2>
        <p>تم إنهاء الحصة الخاصة. سيظهر التسجيل في صفحة الحصة عند اكتمال رفعه.</p>
        <div id="mx-redir-bar"><div id="mx-redir-fill"></div></div>
        <p style="font-size:12px;">سيتم توجيهك تلقائياً...</p>
        <a href="{{ $roomExitUrl }}" class="st-live-pill st-live-pill--gold">
            <i class="fas fa-arrow-left"></i> العودة الآن
        </a>
    </div>

    <div class="st-live-shell">
        <header class="st-live-top">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ $roomExitUrl }}" class="st-live-brand">
                    <span class="st-live-brand__mark"><i class="fas fa-video"></i></span>
                    <span class="hidden sm:inline">{{ config('app.name') }}</span>
                </a>
                @php
                    $roomHostName = $meeting->user?->name
                        ?? $meeting->oneToOneSession?->instructor?->name
                        ?? null;
                @endphp
                <div class="st-live-meta">
                    <p class="st-live-meta__kicker"><span class="st-live-live-dot"></span> حصة خاصة</p>
                    <h1 class="st-live-meta__title">{{ $meeting->roomChromeTitle() }}</h1>
                    <p class="st-live-meta__sub hidden sm:block" id="meeting-timer-chip">
                        @if($roomHostName)
                            {{ $roomHostName }}
                            <span aria-hidden="true"> · </span>
                        @endif
                        مدة الحصة: {{ (int) ($effectiveDurationMinutes ?? 50) }} دقيقة
                    </p>
                </div>
            </div>
            <div class="st-live-actions">
                <span class="st-live-pill st-live-pill--soft" id="meeting-timer-chip-mobile">{{ (int) ($effectiveDurationMinutes ?? 50) }} د</span>
                <button type="button" id="btn-wb-popup-open" class="st-live-pill st-live-pill--ghost" title="السبورة التفاعلية" style="border-color:rgba(245,184,0,.55);background:rgba(245,184,0,.18)">
                    <i class="fas fa-chalkboard"></i>
                    <span class="hidden sm:inline">السبورة</span>
                </button>
                <div id="mx-student-wb-wrap" class="{{ !empty($meeting->allowsParticipantWhiteboard()) ? '' : 'hidden' }}">
                    <button type="button" id="btn-mx-share-draw" class="st-live-pill st-live-pill--ghost" title="رسم فوق العرض" style="border-color:rgba(245,184,0,.55);background:rgba(245,184,0,.18)">
                        <i class="fas fa-pen-fancy"></i>
                        <span class="hidden sm:inline">رسم فوق العرض</span>
                    </button>
                </div>
                <a href="{{ $roomExitUrl }}" class="st-live-pill st-live-pill--ghost" id="student-classroom-leave">
                    <i class="fas fa-sign-out-alt"></i> مغادرة
                </a>
            </div>
        </header>

        <div class="st-live-body">
            <div id="mx-video-stack" class="st-live-stage">
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
                    <div class="flex-1 flex flex-col items-center justify-center gap-3 p-8 text-center text-slate-200">
                        <i class="fas fa-exclamation-triangle text-amber-400 text-3xl"></i>
                        <p class="font-bold text-lg">إعدادات البث غير مكتملة</p>
                        <p class="text-sm text-slate-400 max-w-md">تحقق من مفاتيح LiveKit من لوحة الإدارة → سيرفرات البث.</p>
                        <a href="{{ $roomExitUrl }}" class="st-live-pill st-live-pill--gold mt-2">العودة</a>
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
                overlay.classList.add('show');
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
