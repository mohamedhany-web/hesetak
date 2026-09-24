<!DOCTYPE html>
<html lang="ar" dir="rtl" class="hstk-live-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $liveSession->title }} — حصة مباشرة | حصتك</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('assets.hesetak-live-meeting.css') }}?v=hstk-live-3">
    <style>.hidden{display:none!important}</style>
</head>
<body class="hstk-live-body">
    <div id="mx-session-ended" class="hstk-live-ended">
        <div class="hstk-live-ended__icon"><i class="fas fa-broadcast-tower"></i></div>
        <h2>انتهت الجلسة</h2>
        <p>قام المدرب بإنهاء البث المباشر</p>
        <div class="hstk-live-ended__bar"><div id="mx-redir-fill" class="hstk-live-ended__fill"></div></div>
        <p style="font-size:12px;">سيتم توجيهك تلقائياً...</p>
        <a href="{{ route('student.live-sessions.index') }}" class="hstk-live-btn hstk-live-btn--gold">
            <i class="fas fa-arrow-left"></i> العودة الآن
        </a>
    </div>

    <div class="hstk-live-shell" data-role="student">
        @include('partials.hesetak-live-chrome', [
            'liveRole' => 'student',
            'liveBackUrl' => route('student.live-sessions.index'),
            'liveTitle' => $liveSession->title,
            'liveKicker' => 'بث مباشر',
            'liveSubtitle' => $liveSession->instructor?->name,
            'liveActions' => view('partials.hesetak-live-chrome-actions-student', [
                'allowStudentWhiteboard' => $allowStudentWhiteboard ?? false,
                'showWhiteboard' => false,
                'leaveFormId' => 'student-live-leave-form',
                'leaveAction' => route('student.live-sessions.leave', $liveSession),
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
                        'lkLeaveUrl' => route('student.live-sessions.index'),
                        'lkStartAudio' => !($liveSession->mute_on_join ?? false),
                        'lkStartVideo' => !($liveSession->video_off_on_join ?? false),
                        'lkAllowScreenShare' => $allowScreenShare ?? true,
                    ])
                @else
                    <div class="hstk-live-empty">
                        <div class="hstk-live-empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <p class="hstk-live-empty__title">إعدادات البث غير مكتملة</p>
                        <p class="hstk-live-empty__text">تحقق من مفاتيح Hissatak Meeting ونطاق البث من لوحة الإدارة.</p>
                        <a href="{{ route('student.live-sessions.index') }}" class="hstk-live-btn hstk-live-btn--gold">العودة للجلسات</a>
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
                overlay.classList.add('is-show');
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
