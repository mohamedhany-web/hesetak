<!DOCTYPE html>
<html lang="ar" dir="rtl" class="hstk-live-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $liveSession->title }} — حصة مباشرة (إدارة) | حصتك</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('assets.hesetak-live-meeting.css') }}?v=hstk-live-3">
    <style>
        .hstk-live-admin-strip {
            flex-shrink: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 14px;
            border-bottom: 1px solid rgba(201, 149, 42, 0.22);
            background: rgba(15, 23, 42, 0.72);
            color: #e2e8f0;
            font-size: 12px;
            font-weight: 700;
        }
        .hstk-live-admin-strip__meta {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            align-items: center;
            color: #94a3b8;
        }
        .hstk-live-admin-strip__meta strong { color: #f8fafc; }
        .hstk-live-admin-strip__hint { color: #C9952A; }
    </style>
</head>
<body class="hstk-live-body">
@php
    $liveActions = trim(view('partials.hesetak-live-chrome-actions-admin', [
        'liveSession' => $liveSession,
    ])->render());
    $hostName = $liveSession->instructor?->name ?? $liveSession->host?->name ?? '—';
@endphp
<div class="hstk-live-shell" data-role="admin">
    @include('partials.hesetak-live-chrome', [
        'liveRole' => 'admin',
        'liveBackUrl' => route('admin.live-sessions.show', $liveSession),
        'liveTitle' => $liveSession->title,
        'liveKicker' => 'مراقبة بث مباشر',
        'liveSubtitle' => e($liveSession->room_name),
        'liveActions' => $liveActions,
    ])

    <div class="hstk-live-admin-strip">
        <div class="hstk-live-admin-strip__meta">
            <span>المضيف: <strong>{{ $hostName }}</strong></span>
            <span>الحالة: <strong>{{ $liveSession->status ?? 'live' }}</strong></span>
            <span>الغرفة: <strong dir="ltr">{{ $liveSession->room_name }}</strong></span>
        </div>
        <div class="hstk-live-admin-strip__hint">
            أدوات الاجتماع أسفل الشاشة: ميكروفون · كاميرا · مشاركة شاشة · مغادرة / إنهاء
        </div>
    </div>

    <div class="hstk-live-body">
        <div class="hstk-live-stage">
            @if(!empty($livekitConfigured) && !empty($livekitToken) && !empty($livekitUrl))
                @include('partials.hesetak-live-room', [
                    'livekitUrl' => $livekitUrl,
                    'livekitToken' => $livekitToken,
                    'user' => $user,
                    'lkRole' => 'host',
                    'lkTheme' => 'admin',
                    'lkLeaveUrl' => route('admin.live-sessions.show', $liveSession),
                    'lkHostEndFormId' => 'admin-end-session-form',
                    'lkStartAudio' => true,
                    'lkStartVideo' => true,
                    'lkAllowScreenShare' => $allowScreenShare ?? true,
                ])
            @else
                <div class="hstk-live-empty">
                    <div class="hstk-live-empty__icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <p class="hstk-live-empty__title">إعدادات Hissatak Meeting غير مكتملة</p>
                    <p class="hstk-live-empty__text">تأكد من ضبط مفاتيح البث على السيرفر، ثم أعد فتح الغرفة.</p>
                    <a href="{{ route('admin.live-sessions.show', $liveSession) }}" class="hstk-live-btn hstk-live-btn--gold">العودة للتفاصيل</a>
                </div>
            @endif
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.live-sessions.end', $liveSession) }}" id="admin-end-session-form" class="hidden" hidden>
    @csrf
</form>

<script>
    document.getElementById('admin-end-session-form')?.addEventListener('submit', function (e) {
        if (window.__mxLkHostSessionEnded) return;
        if (!confirm('إنهاء البث للجميع؟ سيتم إغلاق الغرفة أمام المشاركين.')) {
            e.preventDefault();
            return;
        }
        window.__mxLkHostSessionEnded = true;
    });
    document.getElementById('admin-end-session-btn')?.addEventListener('click', function () {
        document.getElementById('admin-end-session-form')?.requestSubmit();
    });
    document.getElementById('admin-copy-room')?.addEventListener('click', async function () {
        var room = this.getAttribute('data-room') || '';
        try {
            await navigator.clipboard.writeText(room);
            this.querySelector('.lbl') && (this.querySelector('.lbl').textContent = 'تم النسخ');
            setTimeout(() => {
                var lbl = this.querySelector('.lbl');
                if (lbl) lbl.textContent = 'نسخ الغرفة';
            }, 1600);
        } catch (e) {
            prompt('انسخ اسم الغرفة:', room);
        }
    });
</script>
</body>
</html>
