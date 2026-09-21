<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $liveSession->title }} — حصة مباشرة (إدارة) | حصتك</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/hesetak-live-meeting.css') }}?v=hstk-live-1">
    <style>
        * { font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; }
        body { margin: 0; background: #0f172a; height: 100vh; overflow: hidden; }
        .room-body { display: flex; flex-direction: column; height: calc(100vh - 64px); min-height: 0; }
    </style>
</head>
<body>
<header class="h-16 border-b border-white/10 bg-[#152A4A]/95 px-4 sm:px-6 flex items-center justify-between gap-3">
    <div class="min-w-0 flex items-center gap-3">
        <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="text-slate-400 hover:text-white transition-colors shrink-0">
            <i class="fas fa-arrow-right"></i>
        </a>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold text-rose-300">
                    <span class="size-1.5 rounded-full bg-rose-400 animate-pulse"></span> LIVE
                </span>
                <h1 class="truncate text-sm font-bold text-white sm:text-base">{{ $liveSession->title }}</h1>
            </div>
            <p class="truncate font-mono text-[11px] text-slate-500">{{ $liveSession->room_name }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="hidden sm:inline-flex h-9 items-center rounded-xl border border-slate-600 px-3 text-xs font-semibold text-slate-200 hover:bg-slate-800">
            التفاصيل
        </a>
        <form method="POST" action="{{ route('admin.live-sessions.end', $liveSession) }}" id="admin-end-session-form">
            @csrf
            <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-xl bg-rose-600 px-3 text-xs font-bold text-white hover:bg-rose-500">
                <i class="fas fa-stop"></i> إنهاء البث
            </button>
        </form>
    </div>
</header>

<div class="room-body">
    @if(!empty($livekitConfigured) && !empty($livekitToken) && !empty($livekitUrl))
        @include('partials.hesetak-live-room', [
            'livekitUrl' => $livekitUrl,
            'livekitToken' => $livekitToken,
            'user' => $user,
            'lkRole' => 'host',
            'lkLeaveUrl' => route('admin.live-sessions.show', $liveSession),
            'lkHostEndFormId' => 'admin-end-session-form',
            'lkStartAudio' => true,
            'lkStartVideo' => true,
            'lkAllowScreenShare' => $allowScreenShare ?? true,
        ])
    @else
        <div class="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center">
            <div class="inline-flex size-14 items-center justify-center rounded-2xl bg-slate-800 text-amber-400">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <p class="text-base font-bold text-white">إعدادات LiveKit غير مكتملة</p>
            <p class="max-w-md text-sm text-slate-400">تأكد من ضبط <code class="text-slate-300">LIVEKIT_API_KEY</code> و <code class="text-slate-300">LIVEKIT_API_SECRET</code> على السيرفر، ثم أعد فتح الغرفة.</p>
            <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="mt-2 inline-flex h-10 items-center rounded-xl bg-slate-100 px-4 text-sm font-semibold text-slate-900">العودة للتفاصيل</a>
        </div>
    @endif
</div>
<script>
    document.getElementById('admin-end-session-form')?.addEventListener('submit', function (e) {
        if (window.__mxLkHostSessionEnded) return;
        if (!confirm('إنهاء البث للجميع؟ سيتم إغلاق الغرفة أمام المشاركين.')) {
            e.preventDefault();
            return;
        }
        window.__mxLkHostSessionEnded = true;
    });
</script>
</body>
</html>
