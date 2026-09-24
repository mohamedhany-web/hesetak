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
</head>
<body class="hstk-live-body">
    <div class="hstk-live-shell" data-role="instructor">
        @include('partials.hesetak-live-chrome', [
            'liveRole' => 'instructor',
            'liveBackUrl' => route('instructor.live-sessions.index'),
            'liveTitle' => $liveSession->title,
            'liveKicker' => 'بث مباشر',
            'liveSubtitle' => e($liveSession->room_name).' · <span id="timer">00:00:00</span>',
            'liveActions' => view('partials.hesetak-live-chrome-actions-instructor', ['liveSession' => $liveSession])->render(),
        ])

        <div class="hstk-live-body">
            <div id="mx-video-stack" class="hstk-live-stage">
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
