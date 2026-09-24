<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>انضم إلى فصول حصتك — {{ $code }}</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('assets.hesetak-live-meeting.css') }}?v=hstk-live-3">
    <link rel="stylesheet" href="{{ asset('css/classroom-curriculum-presenter.css') }}">
    <script src="{{ asset('js/classroom-curriculum-presenter.js') }}" defer></script>
    <script src="{{ route('assets.hissatak-meeting-client.js') }}?v=hmc-1"></script>
    <style>
        :root { --st-brand: #1E4E8C; --st-blue: #152A4A; --st-gold: #C9952A; }
        * { font-family: 'IBM Plex Sans Arabic', 'Lato', 'Rubik', system-ui, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 0; background: #f8f9fa; min-height: 100vh; color: #4f4f4f; }
        .room-body { position: relative; display: flex; flex-direction: column; height: calc(100vh - 72px); background: #0b1220; }
        #lk-guest-stage { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 8px; padding: 8px; overflow: auto; }
        .lk-tile { position: relative; background: #0f172a; border: 1px solid #1e293b; border-radius: 1rem; overflow: hidden; min-height: 180px; }
        .lk-tile.is-screen { grid-column: 1 / -1; min-height: 260px; }
        .lk-tile video { width: 100%; height: 100%; object-fit: cover; background: #020617; }
        .lk-tile.is-screen video { object-fit: contain; }
        .lk-tile-label { position: absolute; left: .75rem; bottom: .75rem; background: rgba(15,23,42,.85); color: #e2e8f0; font-size: .7rem; font-weight: 700; padding: .25rem .55rem; border-radius: .5rem; }
        .lk-btn { display: inline-flex; align-items: center; gap: .5rem; border-radius: 999px; background: #1e293b; color: #e2e8f0; padding: .55rem 1rem; font-size: .8rem; font-weight: 700; border: 1px solid #334155; }
        .lk-btn.is-off { background: #7f1d1d; border-color: #991b1b; color: #fecaca; }
        .lk-btn.is-sharing { background: #0e7490; border-color: #06b6d4; color: #ecfeff; }
        .join-card {
            background: #fff;
            border: 1px solid #ebebeb;
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(7,18,38,.08);
        }
        .join-hero-bar {
            background: linear-gradient(135deg, #1E4E8C, #152A4A);
            color: #fff;
        }
    </style>
</head>
<body>
    <div id="join-screen" class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md join-card overflow-hidden">
            <div class="join-hero-bar px-6 py-5 text-center">
                <div class="w-14 h-14 rounded-2xl bg-white/15 text-[var(--st-gold)] flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-video text-2xl"></i>
                </div>
                <h1 class="text-xl font-black m-0">حصة مباشرة — {{ config('app.name') }}</h1>
                <p class="text-white/85 text-sm mt-1 mb-0 font-semibold">انضم إلى غرفة حصتك الآمنة</p>
            </div>
            <div class="p-6">
            @if(!empty($meetingEnded))
                <div class="text-center">
                    <h2 class="text-lg font-bold text-slate-800">انتهى الاجتماع</h2>
                    <p class="text-slate-500 text-sm mt-3 leading-relaxed">قام منظم الاجتماع بإنهائه. لا يمكن الانضمام من هذا الرابط.</p>
                    <p class="text-slate-400 text-xs mt-4">كود الغرفة: <span class="font-mono">{{ $code }}</span></p>
                </div>
            @elseif(!empty($guestJoinBlocked))
                <div class="text-center">
                    <h2 class="text-lg font-bold text-slate-800">اجتماع خاص داخل المنصة</h2>
                    <p class="text-slate-500 text-sm mt-3 leading-relaxed">لا يمكن الدخول برابط ضيف. سجّل الدخول كطالب أو معلم وانضم من صفحة الحصة داخل المنصة.</p>
                    <p class="text-slate-400 text-xs mt-4">كود الغرفة غير قابل للمشاركة الخارجية.</p>
                    <a href="{{ route('login') }}"
                       class="mt-5 inline-flex w-full items-center justify-center px-6 py-3 rounded-xl bg-[var(--st-brand)] hover:opacity-95 text-white font-bold transition-colors">
                        تسجيل الدخول والانضمام
                    </a>
                    <p class="text-amber-700 text-xs mt-4 leading-relaxed bg-amber-50 border border-amber-100 rounded-xl p-3">
                        من التليفون: افتح الرابط في Chrome أو Safari (مش من داخل واتساب)، ثم سجّل الدخول واضغط «انضم الآن».
                    </p>
                </div>
            @elseif(!empty($meetingNotStarted))
                <div class="text-center">
                    <h2 class="text-lg font-bold text-slate-800">المحاضرة لم تبدأ بعد</h2>
                    <p class="text-slate-500 text-sm mt-3 leading-relaxed">انتظر حتى يبدأ المدرب الاجتماع، ثم حدّث الصفحة وانضم.</p>
                    <p class="text-slate-400 text-xs mb-4">كود الغرفة: <span class="font-mono">{{ $code }}</span></p>
                    <button type="button" onclick="window.location.reload()" class="w-full px-6 py-3 rounded-xl bg-[var(--st-brand)] hover:opacity-95 text-white font-bold transition-colors">
                        <i class="fas fa-sync-alt ml-2"></i> تحديث الصفحة
                    </button>
                </div>
            @else
                @if($meeting && $meeting->title)
                    <p class="text-slate-700 text-sm mb-3 text-center font-bold">{{ $meeting->title }}</p>
                @endif
                <p class="text-slate-500 text-xs mb-2 text-center">كود الغرفة: <span class="font-mono font-bold text-[var(--st-blue)] text-lg">{{ $code }}</span></p>
                <p class="text-slate-500 text-xs mb-4 text-center">الحد الأقصى: <span class="font-bold text-amber-600">{{ $maxParticipants }}</span></p>
                @if(empty($livekitConfigured))
                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs p-3 font-semibold">
                        إعدادات Hissatak Meeting غير مكتملة على الخادم. تواصل مع الإدارة.
                    </div>
                @endif
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-slate-700">اسمك (يظهر للمشاركين)</label>
                    <input type="text" id="guest-name" placeholder="أدخل اسمك" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-[var(--st-blue)] focus:border-transparent">
                </div>
                <div class="mt-6">
                    <button type="button" id="btn-join" class="w-full px-6 py-3 rounded-xl bg-[var(--st-gold)] hover:brightness-95 text-[#072A66] font-black transition-colors" {{ empty($livekitConfigured) ? 'disabled' : '' }}>
                        <i class="fas fa-video ml-2"></i> انضم الآن
                    </button>
                </div>
                <p class="text-slate-400 text-xs mt-4 text-center">لا تحتاج إلى حساب. ادخل باسمك وانضم مباشرة.</p>
            @endif
            </div>
        </div>
    </div>

    <div id="meeting-screen" class="hidden h-screen flex flex-col bg-slate-950 text-white">
        <header class="h-[72px] join-hero-bar flex items-center justify-between px-4 sm:px-6 shadow-lg flex-shrink-0 gap-2">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-white/15 text-[var(--st-gold)] flex items-center justify-center shrink-0">
                    <i class="fas fa-video text-lg"></i>
                </span>
                <span class="font-bold text-white truncate">{{ config('app.name') }} Classroom</span>
                <span class="text-white/75 text-sm shrink-0">— {{ $code }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <div id="mx-guest-wb-wrap" class="hidden">
                    <button type="button" id="btn-mx-share-draw-guest"
                            class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-semibold transition-colors border border-white/30">
                        <i class="fas fa-pen-fancy text-[var(--st-gold)]"></i>
                        <span class="hidden sm:inline">رسم فوق العرض</span>
                    </button>
                </div>
                <button type="button" id="btn-leave" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold transition-colors">
                    <i class="fas fa-sign-out-alt"></i> مغادرة
                </button>
            </div>
        </header>
        <div class="room-body">
            <div id="mx-video-stack" class="relative flex-1 min-h-0 flex flex-col">
                <div id="lk-status" class="absolute top-3 left-1/2 -translate-x-1/2 z-20 px-4 py-1.5 rounded-full bg-slate-900/90 text-slate-200 text-xs font-semibold border border-slate-700 hidden"></div>
                <div id="lk-guest-stage"></div>
                <div class="shrink-0 border-t border-slate-800 bg-slate-900/95 px-4 py-3 flex flex-wrap items-center justify-center gap-2">
                    <button type="button" id="lk-toggle-mic" class="lk-btn"><i class="fas fa-microphone"></i> ميكروفون</button>
                    <button type="button" id="lk-toggle-cam" class="lk-btn"><i class="fas fa-video"></i> كاميرا</button>
                    <button type="button" id="lk-toggle-screen" class="lk-btn"><i class="fas fa-desktop"></i> شاشة</button>
                </div>
                @include('partials.hesetak-live-share-annotation', [
                    'mxAnnRole' => 'classroom_guest_emit',
                    'mxAnnPostUrl' => route('classroom.join.share-annotation', $code),
                ])
            </div>
        </div>
    </div>

    @if(empty($meetingEnded) && empty($meetingNotStarted))
    <script>
        const code = @json($code);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let joinToken = null;
        let heartbeatTimer = null;
        let room = null;

        function applyGuestWhiteboardAllowed(on) {
            if (typeof window.__mxShareAnnSetAllowed === 'function') {
                window.__mxShareAnnSetAllowed(!!on);
            }
            var wrap = document.getElementById('mx-guest-wb-wrap');
            if (!wrap) return;
            if (on) wrap.classList.remove('hidden');
            else wrap.classList.add('hidden');
        }

        function setStatus(msg, isError) {
            var el = document.getElementById('lk-status');
            if (!el) return;
            el.textContent = msg;
            el.classList.remove('hidden');
            el.classList.toggle('bg-rose-900/90', !!isError);
        }

        function isInAppBrowser() {
            var ua = navigator.userAgent || '';
            if (/FBAN|FBAV|Instagram|Line\/|WhatsApp|Snapchat|Twitter|TikTok|MicroMessenger/i.test(ua)) return true;
            if (/iPhone|iPod|iPad/i.test(ua) && /AppleWebKit/i.test(ua) && !/Safari/i.test(ua)) return true;
            if (/\bwv\b|; wv\)/i.test(ua)) return true;
            return false;
        }

        async function createSoftLocalTracks(createLocalTracks, VideoPresets, mxLkAudioCapture, wantAudio, wantVideo) {
            if (!wantAudio && !wantVideo) return [];
            try {
                return await createLocalTracks({
                    audio: wantAudio ? mxLkAudioCapture : false,
                    video: wantVideo ? {
                        resolution: (VideoPresets && VideoPresets.h360)
                            ? VideoPresets.h360.resolution
                            : { width: 640, height: 360, frameRate: 24 },
                        facingMode: 'user',
                    } : false,
                });
            } catch (e1) {
                try {
                    return await createLocalTracks({ audio: !!wantAudio, video: !!wantVideo });
                } catch (e2) {
                    if (wantAudio) return await createLocalTracks({ audio: true, video: false });
                    throw e2;
                }
            }
        }

        async function connectHissatakMeeting(livekit, preacquiredTracks) {
            if (!window.LivekitClient) {
                setStatus('تعذر تحميل مكتبة Hissatak Meeting', true);
                return;
            }
            const url = livekit && livekit.livekitUrl;
            const token = livekit && livekit.livekitToken;
            if (!url || !token) {
                setStatus('رمز دخول Hissatak Meeting غير متاح', true);
                return;
            }
            const { Room, RoomEvent, Track, createLocalTracks, createLocalScreenTracks, VideoPresets, AudioPresets } = window.LivekitClient;
            const mxLkAudioCapture = {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            };
            room = new Room({
                adaptiveStream: false,
                dynacast: true,
                subscriberAllowPause: false,
                audioCaptureDefaults: mxLkAudioCapture,
                publishDefaults: {
                    dtx: false,
                    red: true,
                    forceStereo: false,
                    audioPreset: (AudioPresets && AudioPresets.music) ? AudioPresets.music : { maxBitrate: 48_000 },
                },
            });
            const stage = document.getElementById('lk-guest-stage');
            const tiles = new Map();
            let micOn = true, camOn = true, screenOn = false, screenTrack = null;
            let localTracks = Array.isArray(preacquiredTracks) ? preacquiredTracks.slice() : [];

            function tileKey(participant, source) {
                return participant.identity + ':' + source;
            }
            function ensureTile(participant, source) {
                const key = tileKey(participant, source);
                if (tiles.has(key)) return tiles.get(key);
                const el = document.createElement('div');
                el.className = 'lk-tile' + (source === Track.Source.ScreenShare ? ' is-screen' : '');
                const video = document.createElement('video');
                video.autoplay = true;
                video.playsInline = true;
                video.setAttribute('playsinline', '');
                video.muted = !!participant.isLocal;
                const label = document.createElement('div');
                label.className = 'lk-tile-label';
                label.textContent = (participant.name || participant.identity) + (source === Track.Source.ScreenShare ? ' · شاشة' : '');
                el.appendChild(video);
                el.appendChild(label);
                stage.appendChild(el);
                const tile = { el, video };
                tiles.set(key, tile);
                return tile;
            }
            function attachTrack(track, participant) {
                if (!track) return;
                if (track.kind === Track.Kind.Audio || track.kind === 'audio') {
                    if (!participant.isLocal) {
                        const audio = track.attach();
                        audio.autoplay = true;
                        audio.setAttribute('playsinline', '');
                        document.body.appendChild(audio);
                        audio.play?.().catch(function () {});
                    }
                    return;
                }
                if (track.kind === Track.Kind.Video || track.kind === 'video') {
                    const tile = ensureTile(participant, track.source);
                    track.attach(tile.video);
                    tile.video.play?.().catch(function () {});
                }
            }
            function detachTrack(track, participant) {
                if (!track) return;
                track.detach().forEach((el) => el.remove());
                if (track.kind === Track.Kind.Video || track.kind === 'video') {
                    const key = tileKey(participant, track.source);
                    const ref = tiles.get(key);
                    if (ref) { ref.el.remove(); tiles.delete(key); }
                }
            }

            room.on(RoomEvent.TrackSubscribed, (track, _pub, participant) => attachTrack(track, participant));
            room.on(RoomEvent.TrackUnsubscribed, (track, _pub, participant) => detachTrack(track, participant));
            room.on(RoomEvent.LocalTrackPublished, (pub) => {
                if (pub.track) attachTrack(pub.track, room.localParticipant);
            });
            room.on(RoomEvent.LocalTrackUnpublished, (pub) => {
                if (pub.track) detachTrack(pub.track, room.localParticipant);
                if (pub.source === Track.Source.ScreenShare) {
                    screenOn = false;
                    screenTrack = null;
                    document.getElementById('lk-toggle-screen')?.classList.remove('is-sharing');
                }
            });
            room.on(RoomEvent.Disconnected, () => setStatus('انقطع الاتصال', true));
            room.on(RoomEvent.AudioPlaybackStatusChanged, function () {
                if (room.canPlaybackAudio) return;
                setStatus('اضغط أي مكان لتفعيل الصوت', true);
            });

            setStatus('جاري الاتصال...');
            await room.connect(url, token);
            try { await room.startAudio(); } catch (eAudio) {}
            try {
                if (!localTracks.length) {
                    localTracks = await createSoftLocalTracks(createLocalTracks, VideoPresets, mxLkAudioCapture, true, true);
                }
                await Promise.all(localTracks.map((t) => room.localParticipant.publishTrack(t)));
                localTracks.forEach((t) => attachTrack(t, room.localParticipant));
                micOn = localTracks.some((t) => t.kind === Track.Kind.Audio || t.kind === 'audio');
                camOn = localTracks.some((t) => t.kind === Track.Kind.Video || t.kind === 'video');
                document.getElementById('lk-toggle-mic')?.classList.toggle('is-off', !micOn);
                document.getElementById('lk-toggle-cam')?.classList.toggle('is-off', !camOn);
                setStatus(micOn || camOn ? 'متصل' : 'متصل بدون ميكروفون/كاميرا — فعّل من الأزرار', !(micOn || camOn));
            } catch (mediaErr) {
                console.warn(mediaErr);
                localTracks.forEach(function (t) { try { t.stop(); } catch (e) {} });
                micOn = false;
                camOn = false;
                document.getElementById('lk-toggle-mic')?.classList.add('is-off');
                document.getElementById('lk-toggle-cam')?.classList.add('is-off');
                setStatus('متصل بدون ميكروفون/كاميرا — فعّل الأذونات من الأزرار', true);
            }

            document.getElementById('meeting-screen')?.addEventListener('click', function () {
                try { room.startAudio(); } catch (e) {}
            });

            document.getElementById('lk-toggle-mic')?.addEventListener('click', async function () {
                try {
                    micOn = !micOn;
                    await room.localParticipant.setMicrophoneEnabled(micOn, mxLkAudioCapture);
                    this.classList.toggle('is-off', !micOn);
                } catch (e) {
                    micOn = !micOn;
                    setStatus('تعذر تفعيل الميكروفون', true);
                }
            });
            document.getElementById('lk-toggle-cam')?.addEventListener('click', async function () {
                try {
                    camOn = !camOn;
                    await room.localParticipant.setCameraEnabled(camOn);
                    this.classList.toggle('is-off', !camOn);
                } catch (e) {
                    camOn = !camOn;
                    setStatus('تعذر تفعيل الكاميرا', true);
                }
            });
            document.getElementById('lk-toggle-screen')?.addEventListener('click', async function () {
                try {
                    if (screenOn) {
                        if (typeof room.localParticipant.setScreenShareEnabled === 'function') {
                            await room.localParticipant.setScreenShareEnabled(false);
                        } else if (screenTrack) {
                            await room.localParticipant.unpublishTrack(screenTrack);
                            screenTrack.stop();
                            screenTrack = null;
                        }
                        screenOn = false;
                        this.classList.remove('is-sharing');
                        return;
                    }
                    if (typeof room.localParticipant.setScreenShareEnabled === 'function') {
                        await room.localParticipant.setScreenShareEnabled(true, { audio: true });
                    } else {
                        let tracks;
                        try { tracks = await createLocalScreenTracks({ audio: true }); }
                        catch (e) { tracks = await createLocalScreenTracks({ audio: false }); }
                        screenTrack = tracks[0];
                        await room.localParticipant.publishTrack(screenTrack);
                        attachTrack(screenTrack, room.localParticipant);
                    }
                    screenOn = true;
                    this.classList.add('is-sharing');
                } catch (err) {
                    console.error(err);
                    screenOn = false;
                    this.classList.remove('is-sharing');
                    setStatus('تعذر مشاركة الشاشة — اسمح من نافذة المتصفح', true);
                }
            });
        }

        if (isInAppBrowser()) {
            var joinNote = document.createElement('p');
            joinNote.className = 'text-amber-800 text-xs mt-3 text-center leading-relaxed bg-amber-50 border border-amber-200 rounded-xl p-3';
            joinNote.textContent = 'لو فتحت الصفحة من واتساب/إنستجرام: اضغط ⋮ ثم «فتح في المتصفح» عشان الكاميرا والميك يشتغلوا.';
            document.querySelector('#join-screen .join-card .p-6')?.appendChild(joinNote);
        }

        document.getElementById('btn-join')?.addEventListener('click', async function() {
            const name = document.getElementById('guest-name').value.trim() || 'ضيف';
            const btn = document.getElementById('btn-join');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري التحقق...';

            // أذونات الميك/الكاميرا داخل نفس اللمسة قبل طلب الشبكة (مهم للموبايل)
            let warmedTracks = [];
            try {
                if (window.LivekitClient) {
                    const { createLocalTracks, VideoPresets } = window.LivekitClient;
                    warmedTracks = await createSoftLocalTracks(
                        createLocalTracks,
                        VideoPresets,
                        { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
                        true,
                        true
                    );
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري الدخول...';
                }
            } catch (warmErr) {
                console.warn(warmErr);
                warmedTracks = [];
            }

            try {
                const enterResp = await fetch(`/classroom/join/${code}/enter`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ display_name: name })
                });
                const enterData = await enterResp.json();
                if (!enterResp.ok || !enterData.ok) {
                    warmedTracks.forEach(function (t) { try { t.stop(); } catch (e) {} });
                    alert(enterData.message || 'لا يمكن الانضمام الآن.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-video ml-2"></i> انضم الآن';
                    return;
                }
                joinToken = enterData.token;
                if (typeof window.__mxShareAnnSetGuestToken === 'function') {
                    window.__mxShareAnnSetGuestToken(joinToken);
                }
                applyGuestWhiteboardAllowed(!!enterData.allow_participant_whiteboard);
                if (window.MxClassroomCurriculumPresenter) {
                    if (window.__mxCurriculumPresenter && typeof window.__mxCurriculumPresenter.destroy === 'function') {
                        window.__mxCurriculumPresenter.destroy();
                    }
                    window.__mxCurriculumPresenter = window.MxClassroomCurriculumPresenter.attach(null, {
                        isHost: false,
                        guestToken: joinToken,
                        stateUrl: @json(route('classroom.join.curriculum.state', $code)),
                        pollIntervalMs: 1500,
                    });
                }

                document.getElementById('join-screen').classList.add('hidden');
                document.getElementById('meeting-screen').classList.remove('hidden');

                await connectHissatakMeeting(enterData.livekit || {}, warmedTracks);

                var drawGuestBtn = document.getElementById('btn-mx-share-draw-guest');
                if (drawGuestBtn && typeof window.__mxShareAnnOpenToolbar === 'function') {
                    drawGuestBtn.addEventListener('click', function () { window.__mxShareAnnOpenToolbar(); });
                }
                heartbeatTimer = setInterval(async function() {
                    if (!joinToken) return;
                    try {
                        const hbRes = await fetch(`/classroom/join/${code}/heartbeat`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ token: joinToken })
                        });
                        if (hbRes.ok) {
                            const hbData = await hbRes.json();
                            if (typeof hbData.allow_participant_whiteboard !== 'undefined') {
                                applyGuestWhiteboardAllowed(!!hbData.allow_participant_whiteboard);
                            }
                        }
                    } catch (e) {}
                }, 30000);
            } catch (e) {
                console.error(e);
                warmedTracks.forEach(function (t) { try { t.stop(); } catch (err) {} });
                alert('تعذر الاتصال بالخادم. حاول مرة أخرى.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-video ml-2"></i> انضم الآن';
            }
        });

        async function leaveMeetingAndReload() {
            if (heartbeatTimer) clearInterval(heartbeatTimer);
            if (window.__mxCurriculumPresenter && typeof window.__mxCurriculumPresenter.destroy === 'function') {
                try { window.__mxCurriculumPresenter.destroy(); } catch (e) {}
                window.__mxCurriculumPresenter = null;
            }
            try { if (room) await room.disconnect(); } catch (e) {}
            if (joinToken) {
                try {
                    await fetch(`/classroom/join/${code}/leave`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ token: joinToken, _token: csrfToken })
                    });
                } catch (e) {}
            }
            window.location.reload();
        }

        document.getElementById('btn-leave')?.addEventListener('click', leaveMeetingAndReload);
        window.addEventListener('beforeunload', function() {
            if (!joinToken) return;
            navigator.sendBeacon(`/classroom/join/${code}/leave`, new Blob([JSON.stringify({ token: joinToken, _token: csrfToken })], { type: 'application/json' }));
        });
    </script>
    @endif
</body>
</html>
