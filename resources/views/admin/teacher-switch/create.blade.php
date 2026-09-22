@extends('layouts.admin')

@section('title', 'تبديل معلم بدون خصم رصيد')
@section('page_title', 'تبديل معلم (بدون خصم رصيد)')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-sm text-emerald-900">
        <p class="font-semibold">تبديل المعلم لا يخصم من رصيد الباقة</p>
        <p class="mt-1 text-xs text-emerald-800/90">يُنقل تعيين الحصة فقط (والمعلم في غرفة الاجتماع إن وُجدت) دون استهلاك وحدات الباقة.</p>
    </div>

    <section>
        <p class="text-xs font-medium text-muted">تشغيل المعلمين</p>
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تبديل معلم (بدون خصم رصيد)</h2>
    </section>

    <form method="GET" action="{{ route('admin.teacher-switch.create') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <label class="{{ $labelClass }}">الطالب</label>
        <div class="flex flex-wrap gap-3">
            <select name="student_id" class="{{ $fieldClass }} min-w-[240px] flex-1" onchange="this.form.submit()">
                <option value="0">اختر طالباً…</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((int) $studentId === (int) $student->id)>{{ $student->name }} — {{ $student->email }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($studentId > 0)
        <form method="POST" action="{{ route('admin.teacher-switch.store') }}" class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-soft">
            @csrf
            <input type="hidden" name="student_id" value="{{ $studentId }}">

            <div>
                <label class="{{ $labelClass }}">الحصة الحالية (مفتوحة)</label>
                <select name="one_to_one_session_id" required class="{{ $fieldClass }}">
                    <option value="">اختر حصة…</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected((int) $sessionId === (int) $session->id || (int) old('one_to_one_session_id') === (int) $session->id)>
                            #{{ $session->id }} · {{ $session->instructor->name ?? '—' }} · {{ $session->statusLabel() }}
                            @if($session->scheduled_at) · {{ $session->scheduled_at->format('Y-m-d H:i') }} @endif
                        </option>
                    @endforeach
                </select>
                @if($sessions->isEmpty())
                    <p class="mt-2 text-xs text-amber-700">لا توجد حصص مفتوحة لهذا الطالب.</p>
                @endif
            </div>

            <div>
                <label class="{{ $labelClass }}">المعلم الجديد</label>
                <select name="new_instructor_id" required class="{{ $fieldClass }}">
                    <option value="">اختر معلماً…</option>
                    @foreach($instructors as $ins)
                        <option value="{{ $ins->id }}" @selected((int) old('new_instructor_id') === (int) $ins->id)>{{ $ins->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white"
                    {{ $sessions->isEmpty() ? 'disabled' : '' }}>
                <i class="fas fa-exchange-alt text-xs"></i>
                تنفيذ التبديل بدون خصم
            </button>
        </form>
    @endif
</div>
@endsection
