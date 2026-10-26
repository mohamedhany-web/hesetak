@extends('layouts.admin')

@section('title', 'الحضور والغياب - ' . config('app.name'))
@section('page_title', 'الحضور والغياب')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التسجيلات · الحضور</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إدارة الحضور والغياب</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">فلترة ومراجعة سجلات الحضور لجميع المحاضرات.</p>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-soft">
            <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-soft">
            <i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.attendance.index') }}" class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label for="lecture_id" class="{{ $labelClass }}">المحاضرة</label>
                <select name="lecture_id" id="lecture_id" class="{{ $fieldClass }}">
                    <option value="">كل المحاضرات</option>
                    @foreach($lectures as $lecture)
                        <option value="{{ $lecture->id }}" {{ request('lecture_id') == $lecture->id ? 'selected' : '' }}>
                            {{ $lecture->title }}{{ $lecture->course ? ' - ' . $lecture->course->title : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="lecture_status" class="{{ $labelClass }}">حالة الجلسة / المحاضرة</label>
                <select name="lecture_status" id="lecture_status" class="{{ $fieldClass }}">
                    <option value="">كل الجلسات</option>
                    <option value="past" {{ request('lecture_status') == 'past' ? 'selected' : '' }}>مكتملة أو فات موعدها</option>
                    <option value="completed" {{ request('lecture_status') == 'completed' ? 'selected' : '' }}>مكتملة فقط</option>
                    <option value="scheduled" {{ request('lecture_status') == 'scheduled' ? 'selected' : '' }}>مجدولة</option>
                    <option value="in_progress" {{ request('lecture_status') == 'in_progress' ? 'selected' : '' }}>جارية</option>
                </select>
            </div>
            <div>
                <label for="status" class="{{ $labelClass }}">حالة حضور الطالب</label>
                <select name="status" id="status" class="{{ $fieldClass }}">
                    <option value="">كل الحالات</option>
                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>حاضر</option>
                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>متأخر</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>جزئي</option>
                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>غائب</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-press inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                    <i class="fas fa-search text-xs"></i> بحث
                </button>
                @if(request()->anyFilled(['lecture_id', 'status', 'lecture_status']))
                    <a href="{{ route('admin.attendance.index') }}"
                       class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-muted transition hover:bg-accent-soft hover:text-accent"
                       title="إعادة تعيين">
                        <i class="fas fa-times text-xs"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-3">
            <h3 class="text-sm font-semibold text-ink">سجلات الحضور</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-sm">
                <thead>
                    <tr class="border-b border-line text-right text-xs font-medium text-muted">
                        <th class="px-4 py-3">المحاضرة</th>
                        <th class="px-4 py-3">الطالب</th>
                        <th class="px-4 py-3">الحالة</th>
                        <th class="px-4 py-3">الدقائق</th>
                        <th class="px-4 py-3">النسبة</th>
                        <th class="px-4 py-3">التاريخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($records as $record)
                        <tr class="hover:bg-[#f8faf9]">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.attendance.lecture', $record->lecture_id) }}" class="font-medium text-accent hover:underline">
                                    {{ $record->lecture->title ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-ink">{{ $record->student->name ?? 'غير محدد' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusClasses = [
                                        'present' => 'bg-emerald-50 text-emerald-700',
                                        'late' => 'bg-amber-50 text-amber-800',
                                        'partial' => 'bg-accent-soft text-accent',
                                        'absent' => 'bg-rose-50 text-rose-700',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $statusClasses[$record->status] ?? 'bg-[#f2f5f4] text-muted' }}">
                                    {{ $record->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-muted">{{ (int) ($record->attendance_minutes ?? 0) }}</td>
                            <td class="px-4 py-3 tabular-nums text-muted">{{ number_format((float) ($record->attendance_percentage ?? 0), 1) }}%</td>
                            <td class="px-4 py-3 text-xs tabular-nums text-muted">{{ optional($record->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-muted">لا توجد سجلات حضور مطابقة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="border-t border-line px-4 py-3">
                {{ $records->appends(request()->query())->links() }}
            </div>
        @endif
    </article>
</div>
@endsection
