<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تذكرة دعم جديدة</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f1f5f9; margin: 0; padding: 24px; color: #334155; }
        .box { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; }
        h1 { font-size: 1.25rem; color: #0f172a; margin: 0 0 16px; }
        p { margin: 0 0 10px; font-size: 0.9375rem; line-height: 1.65; }
        .card { background: #f8fafc; border-radius: 12px; padding: 14px; margin: 12px 0; border-right: 4px solid #e11d48; }
        .label { font-size: 0.75rem; color: #64748b; margin-bottom: 4px; }
        .value { font-weight: 600; color: #0f172a; }
        .msg { white-space: pre-wrap; font-weight: normal; font-size: 0.9rem; }
        .btn { display: inline-block; margin-top: 18px; padding: 12px 24px; background: #dc2626; color: #fff !important; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.9375rem; }
        .btn:hover { background: #b91c1c; }
        .note { font-size: 0.8125rem; color: #64748b; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>تذكرة دعم فني جديدة</h1>
        <p>تم إنشاء تذكرة جديدة على المنصة ويُفضّل المتابعة سريعاً.</p>

        <div class="card">
            <div class="label">الطالب</div>
            <div class="value">{{ $ticket->user?->name ?? '—' }} @if($ticket->user?->email) &lt;{{ $ticket->user->email }}&gt; @endif</div>
        </div>

        @if($ticket->inquiryCategory)
        <div class="card">
            <div class="label">التصنيف</div>
            <div class="value">{{ $ticket->inquiryCategory->name }}</div>
        </div>
        @endif

        <div class="card">
            <div class="label">الأولوية</div>
            <div class="value">{{ $ticket->priority }}</div>
        </div>

        <div class="card">
            <div class="label">العنوان</div>
            <div class="value">{{ $ticket->subject }}</div>
        </div>

        <div class="card">
            <div class="label">الرسالة</div>
            <div class="value msg">{{ $ticket->message }}</div>
        </div>

        <a class="btn" href="{{ route('admin.support-tickets.show', $ticket) }}" target="_blank" rel="noopener">فتح التذكرة في لوحة الإدارة</a>

        <p class="note">هذا البريد أُرسل تلقائياً من حصتك عند إنشاء تذكرة دعم. تأكد من ضبط SMTP (مثل Gmail) في إعدادات الموقع.</p>
    </div>
</body>
</html>
