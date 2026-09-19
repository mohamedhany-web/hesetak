@extends('layouts.admin')

@section('title', 'تفاصيل التسجيل - ' . config('app.name'))
@section('page_title', 'تفاصيل التسجيل')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $badge = match($enrollment->status) {
        'active' => 'bg-emerald-50 text-emerald-700',
        'pending' => 'bg-amber-50 text-amber-800',
        'completed' => 'bg-accent-soft text-accent',
        'suspended' => 'bg-rose-50 text-rose-700',
        default => 'bg-[#f2f5f4] text-muted',
    };
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسجيلات · #{{ $enrollment->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $enrollment->student->name ?? 'طالب' }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $enrollment->course->title ?? 'برنامج' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $badge }}">{{ $enrollment->status_text }}</span>
            <a href="{{ route('admin.online-enrollments.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">معلومات التسجيل</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-muted">الطالب</p>
                        <p class="mt-1 font-semibold text-ink">{{ $enrollment->student->name ?? '—' }}</p>
                        <p class="text-sm text-muted" dir="ltr">{{ $enrollment->student->email ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">الهاتف</p>
                        <p class="mt-1 text-ink" dir="ltr">{{ $enrollment->student->phone ?? 'غير محدد' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">البرنامج</p>
                        <p class="mt-1 font-semibold text-ink">{{ $enrollment->course->title ?? '—' }}</p>
                        <p class="text-sm text-muted">
                            {{ $enrollment->course?->academicYear?->name }}
                            @if($enrollment->course?->academicSubject?->name)
                                · {{ $enrollment->course->academicSubject->name }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">التقدم</p>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-2 flex-1 rounded-full bg-[#e8eeec]">
                                <div class="h-2 rounded-full bg-accent" style="width: {{ min(100, (float) $enrollment->progress) }}%"></div>
                            </div>
                            <span class="tabular-nums text-sm font-medium text-ink">{{ number_format((float) $enrollment->progress, 0) }}%</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">تاريخ التسجيل</p>
                        <p class="mt-1 tabular-nums text-ink">{{ $enrollment->enrolled_at?->format('Y-m-d H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted">تاريخ التفعيل</p>
                        <p class="mt-1 tabular-nums text-ink">{{ $enrollment->activated_at?->format('Y-m-d H:i') ?? 'غير مفعّل' }}</p>
                    </div>
                    @if($enrollment->activatedBy)
                        <div>
                            <p class="text-xs font-medium text-muted">فعّله</p>
                            <p class="mt-1 text-ink">{{ $enrollment->activatedBy->name }}</p>
                        </div>
                    @endif
                    @if($enrollment->final_price !== null)
                        <div>
                            <p class="text-xs font-medium text-muted">مبلغ التفعيل</p>
                            <p class="mt-1 tabular-nums font-semibold text-ink">{{ number_format((float) $enrollment->final_price, 2) }} {{ currency_symbol() }}</p>
                        </div>
                    @endif
                </div>
                @if($enrollment->notes)
                    <div class="mt-5 rounded-xl border border-line bg-[#f8faf9] px-4 py-3">
                        <p class="text-xs font-medium text-muted">ملاحظات</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $enrollment->notes }}</p>
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">تحديث التقدم</h3>
                <form method="POST" action="{{ route('admin.online-enrollments.update-progress', $enrollment) }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-[160px] flex-1">
                        <label class="{{ $labelClass }}" for="progress">نسبة التقدم %</label>
                        <input type="number" name="progress" id="progress" min="0" max="100" step="0.1" value="{{ old('progress', $enrollment->progress) }}" class="{{ $fieldClass }}">
                    </div>
                    <button type="submit" class="btn-press inline-flex h-11 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">حفظ التقدم</button>
                </form>
                <p class="mt-2 text-xs text-muted">عند وصول التقدم إلى 100٪ تقريباً يُعلَّم التسجيل مكتملاً وقد تُصدر شهادة إن كانت متاحة.</p>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">تحديث الملاحظات</h3>
                <form method="POST" action="{{ route('admin.online-enrollments.update-notes', $enrollment) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="notes" rows="3" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">{{ old('notes', $enrollment->notes) }}</textarea>
                    <button type="submit" class="btn-press inline-flex h-10 items-center rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">حفظ الملاحظات</button>
                </form>
            </article>
        </div>

        <div class="space-y-5">
            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">إجراءات</h3>
                <div class="mt-4 space-y-2">
                    @if($enrollment->status === 'pending')
                        <form method="POST" action="{{ route('admin.online-enrollments.activate', $enrollment) }}" onsubmit="return confirm('تفعيل هذا التسجيل؟');">
                            @csrf
                            <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-accent text-sm font-medium text-white hover:bg-[#0d4f4a]">
                                <i class="fas fa-check text-xs"></i> تفعيل التسجيل
                            </button>
                        </form>
                    @elseif($enrollment->status === 'active')
                        <form method="POST" action="{{ route('admin.online-enrollments.deactivate', $enrollment) }}" onsubmit="return confirm('إيقاف هذا التسجيل؟');">
                            @csrf
                            <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-line text-sm font-medium text-amber-800 hover:bg-amber-50">
                                <i class="fas fa-pause text-xs"></i> إيقاف التفعيل
                            </button>
                        </form>
                    @elseif($enrollment->status === 'suspended')
                        <form method="POST" action="{{ route('admin.online-enrollments.activate', $enrollment) }}" onsubmit="return confirm('إعادة تفعيل التسجيل وفتح البرنامج للطالب؟');">
                            @csrf
                            <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-accent text-sm font-medium text-white hover:bg-[#0d4f4a]">
                                <i class="fas fa-redo text-xs"></i> إعادة التفعيل
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.online-enrollments.destroy', $enrollment) }}" onsubmit="return confirm('حذف هذا التسجيل نهائياً؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-rose-200 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            <i class="fas fa-trash text-xs"></i> حذف التسجيل
                        </button>
                    </form>
                </div>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">ملخص البرنامج</h3>
                <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-xl border border-line px-3 py-3">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ $enrollment->course->lessons_count ?? 0 }}</p>
                        <p class="text-xs text-muted">دروس</p>
                    </div>
                    <div class="rounded-xl border border-line px-3 py-3">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ $enrollment->course->duration_hours ?? '—' }}</p>
                        <p class="text-xs text-muted">ساعة</p>
                    </div>
                    <div class="col-span-2 rounded-xl border border-line px-3 py-3">
                        <p class="text-2xl font-semibold tabular-nums text-ink">{{ $enrollment->course->active_enrollments_count ?? 0 }}</p>
                        <p class="text-xs text-muted">طلاب نشطون في البرنامج</p>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
                <h3 class="text-sm font-semibold text-ink">معلومات النظام</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-muted">ID</dt><dd class="tabular-nums text-ink">{{ $enrollment->id }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">إنشاء</dt><dd class="tabular-nums text-ink">{{ $enrollment->created_at?->format('Y-m-d H:i') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">تحديث</dt><dd class="tabular-nums text-ink">{{ $enrollment->updated_at?->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </article>
        </div>
    </div>
</div>
@endsection
