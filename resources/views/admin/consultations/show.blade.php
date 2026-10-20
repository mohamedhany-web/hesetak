@extends('layouts.admin')

@section('title', 'طلب استشارة #'.$consultation->id)
@section('header', 'طلب استشارة #'.$consultation->id)

@section('content')
<div class="space-y-6 max-w-5xl">
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-medium">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 space-y-3">
        <div class="flex flex-wrap justify-between gap-2">
            <h2 class="text-xl font-bold text-slate-900">تفاصيل الطلب</h2>
            <span class="px-3 py-1 rounded-full bg-slate-100 text-sm font-semibold">{{ $consultation->statusLabel() }}</span>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div><dt class="text-slate-500">الطالب</dt><dd class="font-semibold">{{ $consultation->student->name ?? '—' }} — {{ $consultation->student->email ?? '' }}</dd></div>
            <div><dt class="text-slate-500">المدرب</dt><dd class="font-semibold">{{ $consultation->instructor->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">المبلغ</dt><dd class="font-semibold">{{ number_format($consultation->price_amount, 2) }} {{ currency_symbol() }}</dd></div>
            <div><dt class="text-slate-500">المدة</dt><dd class="font-semibold">{{ (int) $consultation->duration_minutes }} دقيقة</dd></div>
            @if($consultation->payment_reference)
            <div class="sm:col-span-2"><dt class="text-slate-500">مرجع التحويل</dt><dd class="font-mono text-xs">{{ $consultation->payment_reference }}</dd></div>
            @endif
            @if($consultation->payment_method)
            <div><dt class="text-slate-500">طريقة الدفع</dt><dd class="font-semibold">
                @if($consultation->payment_method === 'bank_transfer') تحويل بنكي / محفظة
                @elseif($consultation->payment_method === 'cash') نقدي
                @else أخرى @endif
            </dd></div>
            @endif
            @if($consultation->platformWallet)
            <div class="sm:col-span-2"><dt class="text-slate-500">حساب المنصة</dt><dd class="font-semibold">{{ $consultation->platformWallet->name ?? \App\Models\Wallet::typeLabel($consultation->platformWallet->type) }} @if($consultation->platformWallet->account_number)<span class="font-mono text-sm"> — {{ $consultation->platformWallet->account_number }}</span>@endif</dd></div>
            @endif
            @if($consultation->payment_proof)
            <div class="sm:col-span-2">
                <dt class="text-slate-500 mb-2">إيصال الدفع</dt>
                <dd>
                    <a href="{{ storage_asset($consultation->payment_proof) }}" target="_blank" rel="noopener" class="inline-block">
                        <img src="{{ storage_asset($consultation->payment_proof) }}" alt="إيصال" class="max-h-64 rounded-lg border border-slate-200 shadow-sm">
                    </a>
                </dd>
            </div>
            @endif
            @if($consultation->student_message)
            <div class="sm:col-span-2"><dt class="text-slate-500">رسالة الطالب</dt><dd class="text-slate-800 whitespace-pre-line">{{ $consultation->student_message }}</dd></div>
            @endif
            @if($consultation->walletTransaction)
            <div class="sm:col-span-2 rounded-xl bg-violet-50 border border-violet-200 p-4">
                <dt class="text-violet-800 font-bold text-sm mb-2">دفع من المحفظة</dt>
                <dd class="text-sm text-slate-700 space-y-1">
                    <p>معاملة المحفظة #{{ $consultation->walletTransaction->id }} — المبلغ {{ number_format($consultation->walletTransaction->amount, 2) }} {{ currency_symbol() }}</p>
                    <p class="text-xs text-slate-500">{{ $consultation->walletTransaction->created_at?->format('Y-m-d H:i') }}</p>
                    @if($consultation->walletTransaction->notes)
                    <p class="text-xs font-mono bg-white/80 rounded px-2 py-1 border border-violet-100">{{ $consultation->walletTransaction->notes }}</p>
                    @endif
                </dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="font-bold text-slate-900 mb-3">ملاحظات إدارية</h3>
        <form method="POST" action="{{ route('admin.consultations.notes', $consultation) }}">
            @csrf
            <textarea name="admin_notes" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm mb-2">{{ old('admin_notes', $consultation->admin_notes) }}</textarea>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold">حفظ الملاحظات</button>
        </form>
    </div>

    @if(in_array($consultation->status, [\App\Models\ConsultationRequest::STATUS_PENDING, \App\Models\ConsultationRequest::STATUS_PAYMENT_REPORTED], true))
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 space-y-3">
        @if($consultation->status === \App\Models\ConsultationRequest::STATUS_PAYMENT_REPORTED && $consultation->payment_proof)
            <p class="text-sm text-emerald-900">تحقق من إيصال الدفع وحساب المنصة ثم أكّد استلام المبلغ.</p>
        @endif
        <form method="POST" action="{{ route('admin.consultations.confirm-payment', $consultation) }}" class="inline">
            @csrf
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold">تأكيد استلام الدفع</button>
        </form>
    </div>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_AWAITING_VERIFICATION)
    <div class="rounded-2xl border border-violet-200 bg-violet-50/80 p-6 space-y-3">
        <p class="text-sm text-violet-900 font-semibold">طلب قديم: تم خصم المبلغ من <strong>محفظة رصيد الطالب</strong>. راجع تفاصيل الطلب ثم اقبل الطلب للمتابعة إلى الجدولة.</p>
        <form method="POST" action="{{ route('admin.consultations.confirm-payment', $consultation) }}" class="inline">
            @csrf
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-violet-700 hover:bg-violet-800 text-white text-sm font-bold">قبول الطلب والدفع (محفظة)</button>
        </form>
    </div>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_PAID)
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="font-bold text-slate-900 mb-3">جدولة الاستشارة وإنشاء غرفة Classroom</h3>
        <form method="POST" action="{{ route('admin.consultations.schedule', $consultation) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            @include('partials.timezone-select', [
                'value' => old('timezone', $consultation->instructor?->timezoneCode() ?? auth()->user()?->timezoneCode()),
                'class' => 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm',
                'labelClass' => 'block text-xs font-semibold text-slate-600 mb-1',
            ])
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">موعد البدء</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">مدة الجلسة (دقيقة)</label>
                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $consultation->duration_minutes) }}" min="15" max="480" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-bold">جدولة وإنشاء رابط الغرفة</button>
            </div>
        </form>
    </div>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_SCHEDULED && $consultation->classroomMeeting)
    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-6">
        <h3 class="font-bold text-emerald-900 mb-2">الغرفة جاهزة</h3>
        <p class="text-sm text-emerald-800 mb-2">الموعد: <x-app-datetime :at="$consultation->scheduled_at" /></p>
        <p class="text-xs font-mono break-all">{{ url('classroom/join/'.$consultation->classroomMeeting->code) }}</p>
    </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if(!in_array($consultation->status, [\App\Models\ConsultationRequest::STATUS_CANCELLED, \App\Models\ConsultationRequest::STATUS_COMPLETED], true))
        <form method="POST" action="{{ route('admin.consultations.cancel', $consultation) }}" onsubmit="return confirm('إلغاء الطلب؟');">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-rose-300 text-rose-700 text-sm font-semibold">إلغاء الطلب</button>
        </form>
        @endif
        @if($consultation->status === \App\Models\ConsultationRequest::STATUS_SCHEDULED)
        <form method="POST" action="{{ route('admin.consultations.complete', $consultation) }}">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm font-semibold">تسجيل كمكتملة</button>
        </form>
        @endif
        <a href="{{ route('admin.consultations.index') }}" class="px-4 py-2 rounded-lg text-sky-600 text-sm font-semibold hover:underline">رجوع للقائمة</a>
    </div>
</div>
@endsection
