<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $liveSession->title }} — حصة مباشرة | حصتك</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/hesetak-live-meeting.css') }}?v=hstk-live-1">
    <link rel="stylesheet" href="{{ route('assets.student-timeline.css') }}?v=st-live-1">
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
            background: #ef4444;
            box-shadow: 0 0 0 0 rgba(239,68,68,.55);
            animation: stLivePulse 1.4s infinite;
            display: inline-block;
        }
        @keyframes stLivePulse {
            0% { box-shadow: 0 0 0 0 rgba(239,68,68,.55); }
            70% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
            100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
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
        }
        .st-live-pill--gold { background: var(--st-gold); color: #072A66; }
        .st-live-pill--ghost {
            background: transparent;
            color: #fff;
            border: 1.5px solid rgba(255,255,255,.65);
        }
        .st-live-pill--warn {
            background: rgba(245,184,0,.2);
            color: #fff7d6;
            border: 1px solid rgba(245,184,0,.45);
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
    <div id="mx-session-ended">
        <div class="mx-icon"><i class="fas fa-broadcast-tower"></i></div>
        <h2>انتهت الجلسة</h2>
        <p>قام المدرب بإنهاء البث المباشر</p>
        <div id="mx-redir-bar"><div id="mx-redir-fill"></div></div>
        <p style="font-size:12px;">سيتم توجيهك تلقائياً...</p>
        <a href="{{ route('student.live-sessions.index') }}" class="st-live-pill st-live-pill--gold">
            <i class="fas fa-arrow-left"></i> العودة الآن
        </a>
    </div>

    <div class="st-live-shell">
        <header class="st-live-top">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('student.live-sessions.index') }}" class="st-live-brand">
                    <span class="st-live-brand__mark"><i class="fas fa-broadcast-tower"></i></span>
                    <span class="hidden sm:inline">{{ config('app.name') }}</span>
                </a>
                <div class="st-live-meta">
                    <p class="st-live-meta__kicker"><span class="st-live-live-dot"></span> بث مباشر</p>
                    <h1 class="st-live-meta__title">{{ $liveSession->title }}</h1>
                    @if($liveSession->instructor)
                        <p class="st-live-meta__sub hidden sm:block">{{ $liveSession->instructor->name }}</p>
                    @endif
                </div>
            </div>
            <div class="st-live-actions">
                <div id="mx-student-wb-wrap" class="{{ ($allowStudentWhiteboard ?? false) ? '' : 'hidden' }}">
                    <button type="button" id="btn-mx-share-draw" class="st-live-pill st-live-pill--warn" title="رسم فوق ما يظهر في الاجتماع">
                        <i class="fas fa-pen-fancy"></i>
                        <span class="hidden sm:inline">رسم فوق البث</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('student.live-sessions.leave', $liveSession) }}" class="inline m-0" id="student-live-leave-form">
                    @csrf
                    <button type="submit" class="st-live-pill st-live-pill--ghost">
                        <i class="fas fa-sign-out-alt"></i> مغادرة
                    </button>
                </form>
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
                        'lkLeaveUrl' => route('student.live-sessions.index'),
                        'lkStartAudio' => !($liveSession->mute_on_join ?? false),
                        'lkStartVideo' => !($liveSession->video_off_on_join ?? false),
                        'lkAllowScreenShare' => $allowScreenShare ?? true,
                    ])
                @else
                    <div class="flex-1 flex flex-col items-center justify-center gap-3 p-8 text-center text-slate-200">
                        <i class="fas fa-exclamation-triangle text-amber-400 text-3xl"></i>
                        <p class="font-bold text-lg">إعدادات البث غير مكتملة</p>
                        <p class="text-sm text-slate-400 max-w-md">تحقق من مفاتيح LiveKit في إعدادات السيرفر ونطاق البث من لوحة الإدارة → سيرفرات البث.</p>
                        <a href="{{ route('student.live-sessions.index') }}" class="st-live-pill st-live-pill--gold mt-2">العودة للجلسات</a>
                    </div>
                @endif
                @include('partials.hesetak-live-share-annotation', [
                    'mxAnnRole' => 'student_emit',
                    'mxAnnPostUrl' => route('student.live-sessions.share-annotation', $liveSession),
                ])
            </div>
        </div>
    </div>

    <script>
        (function () {
            var leaveForm = document.getElementById('student-live-leave-form');
            var lkLeave = document.getElementById('lk-leave');
            if (leaveForm && lkLeave) {
                lkLeave.addEventListener('click', function (e) {
                    e.preventDefault();
                    leaveForm.submit();
                });
            }
        })();
    </script>

    <script>
        (function () {
            var indexUrl = @json(route('student.live-sessions.index'));
            var statusUrl = @json(route('student.live-sessions.status', $liveSession));
            var wrap = document.getElementById('mx-student-wb-wrap');
            var drawBtn = document.getElementById('btn-mx-share-draw');

            function showSessionEndedAndRedirect() {
                var overlay = document.getElementById('mx-session-ended');
                var fill = document.getElementById('mx-redir-fill');
                if (!overlay) {
                    window.location.href = indexUrl;
                    return;
                }
                overlay.classList.add('show');
                setTimeout(function () { if (fill) fill.style.width = '100%'; }, 100);
                setTimeout(function () { window.location.href = indexUrl; }, 5500);
            }

            function applyAllow(on) {
                if (typeof window.__mxShareAnnSetAllowed === 'function') {
                    window.__mxShareAnnSetAllowed(!!on);
                }
                if (!wrap) return;
                if (on) wrap.classList.remove('hidden');
                else wrap.classList.add('hidden');
            }

            if (drawBtn && typeof window.__mxShareAnnOpenToolbar === 'function') {
                drawBtn.addEventListener('click', function () { window.__mxShareAnnOpenToolbar(); });
            }
            applyAllow({{ ($allowStudentWhiteboard ?? false) ? 'true' : 'false' }});

            setInterval(function () {
                fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;
                        if (data.status === 'ended' || data.ended === true) {
                            showSessionEndedAndRedirect();
                            return;
                        }
                        if (typeof data.allow_student_whiteboard !== 'undefined') {
                            applyAllow(!!data.allow_student_whiteboard);
                        }
                    })
                    .catch(function () {});
            }, 12000);
        })();
    </script>
</body>
</html>
