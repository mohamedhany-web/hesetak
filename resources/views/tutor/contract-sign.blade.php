<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>توقيع عقد التعاون | حصتك</title>
    <style>
        body{font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;background:#f8fafc;margin:0;color:#0f172a}
        .wrap{max-width:760px;margin:0 auto;padding:24px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:16px}
        canvas{border:1px dashed #94a3b8;border-radius:12px;width:100%;height:160px;touch-action:none;background:#fff}
        .btn{border:0;border-radius:999px;padding:10px 18px;background:#1E4E8C;color:#fff;font-weight:800;cursor:pointer}
        .btn-ghost{background:#e2e8f0;color:#0f172a}
        .ok{background:#ecfdf5;color:#065f46;padding:10px 12px;border-radius:12px;margin-bottom:12px}
        .err{background:#fef2f2;color:#991b1b;padding:10px 12px;border-radius:12px;margin-bottom:12px}
        pre{white-space:pre-wrap;background:#f1f5f9;padding:12px;border-radius:12px}
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ $agreement->title }}</h1>
    <p>رقم الاتفاقية: <strong dir="ltr">{{ $agreement->agreement_number }}</strong></p>

    @if(session('success'))<div class="ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

    <div class="card">
        <h3>تفاصيل الأجر</h3>
        <p>النوع: {{ $agreement->billing_type }}</p>
        @if($agreement->salary_per_session)<p>أجر الجلسة: {{ $agreement->salary_per_session }}</p>@endif
        @if($agreement->monthly_amount)<p>الراتب الشهري: {{ $agreement->monthly_amount }}</p>@endif
        @if($agreement->rate)<p>القيمة: {{ $agreement->rate }}</p>@endif
    </div>

    <div class="card">
        <h3>الشروط</h3>
        <pre>{{ $agreement->terms }}</pre>
    </div>

    @if($agreement->isSigned())
        <div class="card">
            <p>تم التوقيع بواسطة {{ $agreement->signer_name }} في {{ $agreement->signed_at?->format('Y-m-d H:i') }}</p>
            <a class="btn" href="{{ route('tutor.contract.pdf', $token) }}">تنزيل نسخة PDF</a>
        </div>
    @else
        <form method="POST" action="{{ route('tutor.contract.sign.submit', $token) }}" class="card" id="sign-form">
            @csrf
            <label>الاسم الكامل للتوقيع</label>
            <input type="text" name="signer_name" required value="{{ old('signer_name', $application?->full_name) }}" style="width:100%;padding:10px;border-radius:10px;border:1px solid #cbd5e1;margin:8px 0 16px">
            <label>ارسم توقيعك</label>
            <canvas id="sig" width="700" height="160"></canvas>
            <input type="hidden" name="signature_data" id="signature_data">
            <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
                <button type="button" class="btn btn-ghost" id="clear-sig">مسح</button>
                <button type="submit" class="btn">توقيع واعتماد العقد</button>
            </div>
        </form>
        <script>
            (function () {
                var canvas = document.getElementById('sig');
                var ctx = canvas.getContext('2d');
                var drawing = false;
                function pos(e) {
                    var r = canvas.getBoundingClientRect();
                    var x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
                    var y = (e.touches ? e.touches[0].clientY : e.clientY) - r.top;
                    return { x: x * (canvas.width / r.width), y: y * (canvas.height / r.height) };
                }
                function start(e) { drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
                function move(e) { if (!drawing) return; var p = pos(e); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#0f172a'; ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
                function end() { drawing = false; }
                canvas.addEventListener('mousedown', start); canvas.addEventListener('mousemove', move); window.addEventListener('mouseup', end);
                canvas.addEventListener('touchstart', start, {passive:false}); canvas.addEventListener('touchmove', move, {passive:false}); canvas.addEventListener('touchend', end);
                document.getElementById('clear-sig').onclick = function () { ctx.clearRect(0,0,canvas.width,canvas.height); };
                document.getElementById('sign-form').addEventListener('submit', function (e) {
                    var blank = document.createElement('canvas'); blank.width = canvas.width; blank.height = canvas.height;
                    if (canvas.toDataURL() === blank.toDataURL()) { e.preventDefault(); alert('ارسم التوقيع أولاً'); return; }
                    document.getElementById('signature_data').value = canvas.toDataURL('image/png');
                });
            })();
        </script>
    @endif
</div>
</body>
</html>
