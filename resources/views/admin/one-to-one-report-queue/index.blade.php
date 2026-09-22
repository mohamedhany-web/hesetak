@extends('layouts.admin')

@section('title', 'تقارير الحصص قبل الصرف')
@section('page_title', 'تقارير الحصص (قبل الصرف)')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الطلاب · تشغيل 1:1</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تقارير الحصص قبل الصرف</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">الحصص المكتملة أو المتأخرة بدون تقرير معلم — الصرف موقوف حتى التقرير.</p>
        </div>
        <span class="inline-flex h-9 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 text-xs font-semibold text-amber-800">
            <i class="fas fa-hand-holding-usd"></i>
            الصرف موقوف حتى التقرير
        </span>
    </section>

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">إجمالي بانتظار تقرير</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['total']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مكتملة بلا تقرير</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['completed_no_report']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">مجدولة ومتأخرة</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['past_scheduled']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">معلمون متأثرون</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($kpis['instructors_affected']) }}</p>
        </article>
    </section>

    <form method="GET" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1">
                <label class="{{ $labelClass }}">المعلم</label>
                <select name="instructor_id" class="{{ $fieldClass }}">
                    <option value="0">كل المعلمين</option>
                    @foreach($instructors as $ins)
                        <option value="{{ $ins->id }}" @selected((int) $instructorId === (int) $ins->id)>{{ $ins->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-filter text-xs"></i> تطبيق
            </button>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas text-xs text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">#</th>
                        <th class="px-4 py-3 text-start font-medium">الطالب</th>
                        <th class="px-4 py-3 text-start font-medium">المعلم</th>
                        <th class="px-4 py-3 text-start font-medium">الموعد</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-end font-medium">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-canvas/60">
                            <td class="px-4 py-3 font-mono text-xs tabular-nums">{{ $session->id }}</td>
                            <td class="px-4 py-3 font-medium text-ink">{{ $session->student->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink">{{ $session->instructor->name ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-muted">
                                {{ $session->scheduled_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg border border-amber-100 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800">
                                    {{ $session->statusLabel() }} · بلا تقرير
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('admin.one-to-one-sessions.show', $session) }}"
                                   class="inline-flex h-9 items-center gap-2 rounded-xl border border-line px-3 text-xs font-semibold text-accent hover:bg-accent-soft">
                                    فتح الحصة
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-muted">لا توجد حصص بانتظار تقرير.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $sessions->links() }}</div>
        @endif
    </article>
</div>
@endsection
