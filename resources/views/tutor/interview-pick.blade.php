<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>اختيار موعد المقابلة | حصتك</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body{font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;background:#f4f6f9;margin:0;color:#0f172a}
        .wrap{max-width:720px;margin:0 auto;padding:24px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:16px}
        .btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:999px;padding:10px 16px;background:#1E4E8C;color:#fff;font-weight:700;cursor:pointer}
        .slot{display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-top:8px}
        .ok{background:#ecfdf5;color:#065f46;padding:10px 12px;border-radius:12px}
        .err{background:#fef2f2;color:#991b1b;padding:10px 12px;border-radius:12px}
    </style>
</head>
<body>
<div class="wrap">
    <h1>اختيار موعد المقابلة التقنية</h1>
    <p>مرحباً {{ $application->full_name }} — اختر وقتاً متاحاً من قائمة الإدارة.</p>

    @if(session('success'))<div class="ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

    @if($current)
        <div class="card">
            <h3>موعدك الحالي</h3>
            <p dir="ltr">{{ $current->scheduled_at?->format('Y-m-d H:i') }}</p>
            <p><a class="btn" href="{{ $current->effectiveJoinUrl() }}" target="_blank" rel="noopener">دخول المقابلة</a></p>
        </div>
    @endif

    <div class="card">
        <h3>المواعيد المتاحة</h3>
        @forelse($slots as $slot)
            <form method="POST" action="{{ route('tutor.interview.book') }}" class="slot">
                @csrf
                <input type="hidden" name="slot_id" value="{{ $slot->id }}">
                <div>
                    <strong dir="ltr">{{ $slot->starts_at?->format('Y-m-d H:i') }}</strong>
                    <div style="font-size:12px;color:#64748b">{{ $slot->title ?: $slot->modeLabel() }} · متبقي {{ $slot->remainingCapacity() }}</div>
                </div>
                <button class="btn" type="submit">احجز</button>
            </form>
        @empty
            <p>لا مواعيد مفتوحة حالياً. راقب إشعاراتك أو تواصل مع التوظيف.</p>
        @endforelse
    </div>
</div>
</body>
</html>
