<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $liveSession->title }} — حصة مباشرة | حصتك</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/hesetak-live-meeting.css') }}?v=hstk-live-1">
    <link rel="stylesheet" href="{{ route('assets.instructor-panel.css') }}?v=su-live-1">
    <style>
        html, body {
            margin: 0; padding: 0; height: 100%;
            overflow: hidden;
            font-family: var(--su-font);
            background: var(--su-bg);
            color: var(--su-ink);
        }
        .su-live-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            background:
                radial-gradient(900px 360px at 100% -5%, rgba(149,164,252,.14), transparent 50%),
                radial-gradient(700px 300px at 0% 0%, rgba(255,203,154,.10), transparent 45%),
                var(--su-bg);
        }
        .su-live-top {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: var(--su-topbar-h);
            padding: 10px 16px;
            border-bottom: 1px solid var(--su-line);
            background: color-mix(in srgb, var(--su-bg-2) 88%, transparent);
            backdrop-filter: blur(10px);
        }
        .su-live-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--su-ink);
            font-weight: 700;
        }
        .su-live-brand__mark {
            width: 40px; height: 40px;
            border-radius: var(--su-r-12);
            display: inline-grid;
            place-items: center;
            background: var(--su-card-2);
            color: #C9952A;
        }
        .su-live-meta { min-width: 0; }
        .su-live-meta__kicker {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            color: var(--su-ink-40);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .su-live-meta__title {
            margin: 2px 0 0;
            font-size: clamp(0.95rem, 2vw, 1.1rem);
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: min(48vw, 420px);
        }
        .su-live-meta__sub {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--su-ink-40);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .su-live-dot {
            width: 8px; height: 8px;
            border-radius: 999px;
            background: var(--su-danger);
            box-shadow: 0 0 0 0 rgba(239,68,68,.5);
            animation: suLivePulse 1.4s infinite;
        }
        @keyframes suLivePulse {
            0% { box-shadow: 0 0 0 0 rgba(239,68,68,.5); }
            70% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
            100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
        }
        .su-live-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: flex-end;
        }
        .su-live-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 12px;
            border-radius: var(--su-r-12);
            border: 1px solid var(--su-line);
            background: var(--su-bg-2);
            color: var(--su-ink);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
        }
        .su-live-btn:hover { border-color: var(--su-accent-lilac); }
        .su-live-btn--peach {
            background: color-mix(in srgb, var(--su-accent-peach) 28%, var(--su-bg-2));
            border-color: color-mix(in srgb, var(--su-accent-peach) 45%, var(--su-line));
        }
        .su-live-btn--mint {
            background: color-mix(in srgb, var(--su-accent-mint) 22%, var(--su-bg-2));
            border-color: color-mix(in srgb, var(--su-accent-mint) 40%, var(--su-line));
        }
        .su-live-btn--danger {
            background: color-mix(in srgb, var(--su-danger) 88%, #000);
            border-color: var(--su-danger);
            color: #fff;
        }
        .su-live-btn.is-recording {
            background: color-mix(in srgb, var(--su-danger) 85%, #000);
            color: #fff;
            border-color: var(--su-danger);
        }
        .su-live-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 10px;
            border-radius: var(--su-r-12);
            border: 1px solid var(--su-line);
            background: var(--su-card-4);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }
        .su-live-body {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 12px;
        }
        .su-live-stage {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            border-radius: var(--su-r-16);
            overflow: hidden;
            border: 1px solid var(--su-line);
            background: var(--su-bg-2);
            box-shadow: 0 12px 32px rgba(0,0,0,.18);
            position: relative;
        }
        @keyframes recPulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
        #record-icon.recording { animation: recPulse 1s infinite; }
        #mx-rec-toast {
            position: fixed;
            top: 84px;
            left: 50%;
            transform: translateX(-50%);
            background: color-mix(in srgb, var(--su-danger) 92%, #000);
            color: #fff;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 9999;
            box-shadow: 0 8px 24px rgba(239,68,68,.35);
        }
        #mx-rec-toast.is-visible { display: flex; }
        #mx-rec-dot { width:8px;height:8px;background:#fff;border-radius:50%;animation:recPulse 1s infinite; }
        @media (max-width: 720px) {
            .su-live-meta__title { max-width: 36vw; }
            .su-live-btn span.lbl { display: none; }
            .su-live-body { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="su-live-shell">
        <header class="su-live-top">
            <div style="display:flex;align-items:center;gap:12px;min-width:0">
                <a href="{{ route('instructor.live-sessions.index') }}" class="su-live-brand">
                    <span class="su-live-brand__mark"><i class="fas fa-broadcast-tower"></i></span>
                    <span class="hidden-sm">{{ config('app.name') }}</span>
                </a>
                <div class="su-live-meta">
                    <p class="su-live-meta__kicker"><span class="su-live-dot" aria-hidden="true"></span> بث مباشر</p>
                    <h1 class="su-live-meta__title">{{ $liveSession->title }}</h1>
                    <p class="su-live-meta__sub">
                        <span>{{ $liveSession->room_name }}</span>
                        ·
                        <span id="timer">00:00:00</span>
                    </p>
                </div>
            </div>
            <div class="su-live-actions">
                <button type="button" id="btn-wb-popup-open" class="su-live-btn su-live-btn--peach" title="السبورة التفاعلية">
                    <i class="fas fa-chalkboard"></i>
                    <span class="lbl">السبورة</span>
                </button>
                <label class="su-live-check" title="الطلاب يرسمون فوق البث">
                    <input type="checkbox" id="mx-toggle-student-wb" {{ $liveSession->allowsStudentWhiteboard() ? 'checked' : '' }}>
                    <span class="lbl">رسم الطلاب</span>
                </label>
                <span id="mx-auto-rec-badge" class="su-live-btn su-live-btn--danger hidden" style="pointer-events:none;opacity:.85" title="تسجيل تلقائي للجلسة">
                    <span class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></span>
                    <span class="lbl">REC</span>
                </span>
                <form method="POST" action="{{ route('instructor.live-sessions.end', $liveSession) }}" class="inline" style="margin:0" id="end-session-form" onsubmit="return handleEndSession(event);">
                    @csrf
                    <button type="submit" class="su-live-btn su-live-btn--danger">
                        <i class="fas fa-stop"></i>
                        <span class="lbl">إنهاء البث</span>
                    </button>
                </form>
            </div>
        </header>

        <div class="su-live-body">
            <div id="mx-video-stack" class="su-live-stage">
                @include('partials.hesetak-live-room', [
                    'livekitUrl' => $livekitUrl,
                    'livekitToken' => $livekitToken,
                    'user' => $user,
                    'lkRole' => 'host',
                    'lkTheme' => 'instructor',
                    'lkLeaveUrl' => route('instructor.live-sessions.show', $liveSession),
                    'lkHostEndFormId' => 'end-session-form',
                    'lkAllowScreenShare' => $allowScreenShare ?? true,
                ])
                @include('partials.hesetak-live-share-annotation', [
                    'mxAnnRole' => 'viewer_poll',
                    'mxAnnPollUrl' => route('instructor.live-sessions.share-annotations', $liveSession),
                ])
            </div>
        </div>
    </div>

    @include('partials.hesetak-live-whiteboard-popup')
    <style>.hidden-sm{display:inline}@media(max-width:640px){.hidden-sm{display:none}}</style>
    <script>
        const startTime = new Date('{{ $liveSession->started_at->toISOString() }}');
        function updateTimer() {
            const diff = Math.floor((Date.now() - startTime) / 1000);
            const h = Math.floor(diff / 3600), m = Math.floor((diff % 3600) / 60), s = diff % 60;
            var el = document.getElementById('timer');
            if (el) el.textContent = String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        const csrfToken = '{{ csrf_token() }}';
        const autoRecBadge = document.getElementById('mx-auto-rec-badge');
        const studentWbUrl = '{{ route("instructor.live-sessions.student-whiteboard", $liveSession) }}';
        const mxStudentWbToggle = document.getElementById('mx-toggle-student-wb');
        let mxStudentWbSaving = false;
        if (mxStudentWbToggle) {
            mxStudentWbToggle.addEventListener('change', async function () {
                if (mxStudentWbSaving) return;
                mxStudentWbSaving = true;
                const want = mxStudentWbToggle.checked;
                try {
                    const r = await fetch(studentWbUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ allow: want }),
                    });
                    if (!r.ok) mxStudentWbToggle.checked = !want;
                } catch (e) {
                    mxStudentWbToggle.checked = !want;
                } finally {
                    mxStudentWbSaving = false;
                }
            });
        }

        const audioPresignUrl = '{{ route("instructor.live-sessions.audio.presign", $liveSession) }}';
        const audioCompleteUrl = '{{ route("instructor.live-sessions.audio.complete", $liveSession) }}';
        let audioRecorder = null, audioStream = null, audioChunks = [];
        let audioStartedAt = null, audioUploadFinalized = false, audioUploadInFlight = false;

        function pickAudioMimeType() {
            if (!window.MediaRecorder || typeof MediaRecorder.isTypeSupported !== 'function') return '';
            return ['audio/webm;codecs=opus','audio/webm','audio/ogg;codecs=opus','audio/ogg']
                .find(m => MediaRecorder.isTypeSupported(m)) || '';
        }

        async function startAutoAudioRecording() {
            if (audioRecorder || !window.MediaRecorder) return;
            try {
                const cap = (typeof window.__mxLkGetRecordCapture === 'function')
                    ? window.__mxLkGetRecordCapture()
                    : null;
                const tracks = (cap?.audioTracks || []).filter(function (t) {
                    return t && t.readyState === 'live' && t.enabled !== false;
                });
                if (!tracks.length) return;
                audioStream = new MediaStream([tracks[0]]);
                const mimeType = pickAudioMimeType();
                audioRecorder = mimeType ? new MediaRecorder(audioStream, { mimeType }) : new MediaRecorder(audioStream);
                audioChunks = [];
                audioStartedAt = Date.now();
                audioRecorder.ondataavailable = e => { if (e.data?.size > 0) audioChunks.push(e.data); };
                audioRecorder.start(1000);
                if (autoRecBadge) autoRecBadge.classList.remove('hidden');
            } catch (e) { console.warn('Auto audio recording failed:', e); }
        }

        async function uploadAudioBlob(blob, durationSeconds) {
            if (!blob || blob.size <= 0) return;
            const presignRes = await fetch(audioPresignUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ content_type: blob.type || 'audio/webm' }),
            });
            if (!presignRes.ok) return;
            const presign = await presignRes.json();
            if (!presign.direct_upload || !presign.upload_url) return;
            const uploadHeaders = Object.assign({}, presign.headers || {});
            if (!uploadHeaders['Content-Type'] && !uploadHeaders['content-type']) {
                uploadHeaders['Content-Type'] = presign.content_type || blob.type || 'audio/webm';
            }
            const putRes = await fetch(presign.upload_url, { method: 'PUT', headers: uploadHeaders, body: blob });
            if (!putRes.ok) return;
            await fetch(audioCompleteUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ upload_token: presign.upload_token, duration_seconds: Math.max(1, Math.floor(durationSeconds || 0)) }),
            });
        }

        async function stopAndUploadAutoAudio() {
            if (audioUploadFinalized || audioUploadInFlight) return;
            if (!audioRecorder) return;
            audioUploadInFlight = true;
            try {
                if (audioRecorder.state !== 'inactive') {
                    await new Promise(resolve => { audioRecorder.addEventListener('stop', resolve, { once: true }); audioRecorder.stop(); });
                }
                const mimeType = audioRecorder.mimeType || 'audio/webm';
                const blob = new Blob(audioChunks, { type: mimeType });
                const duration = audioStartedAt ? ((Date.now() - audioStartedAt) / 1000) : 0;
                await uploadAudioBlob(blob, duration);
                audioUploadFinalized = true;
            } catch (e) { console.warn('Auto audio upload failed:', e); }
            finally {
                audioStream?.getTracks().forEach(t => t.stop());
                audioStream = null; audioRecorder = null; audioChunks = []; audioUploadInFlight = false;
            }
        }

        setTimeout(function () { startAutoAudioRecording(); }, 10000);

        window.addEventListener('pagehide', function () {
            try {
                var payload = new FormData();
                payload.append('_token', '{{ csrf_token() }}');
                navigator.sendBeacon('{{ route("instructor.live-sessions.leave-presence", $liveSession) }}', payload);
            } catch (e) {}
        });

        window.__mxLiveHostEndSession = async function (alreadyConfirmed) {
            const fakeEvent = { preventDefault: function () {} };
            return handleEndSession(fakeEvent, alreadyConfirmed);
        };

        async function handleEndSession(e, alreadyConfirmed) {
            if (!alreadyConfirmed && !confirm('هل تريد إنهاء البث المباشر للجميع؟\n\nسيتم إغلاق الغرفة أمام الطلاب — لا تكتفي بمغادرة الصفحة.')) return false;
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            window.__mxLkHostSessionEnded = true;
            const form = document.getElementById('end-session-form');
            const btn = form?.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ الإنهاء...'; }
            await stopAndUploadAutoAudio();
            form?.submit();
            return false;
        }

        window.addEventListener('beforeunload', function () {
            if (!audioUploadFinalized && audioRecorder) stopAndUploadAutoAudio();
        });
    </script>
</body>
</html>
