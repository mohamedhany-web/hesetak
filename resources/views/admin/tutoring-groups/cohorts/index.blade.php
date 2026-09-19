@extends('layouts.admin')

@section('title', 'دفعات '.$group->title.' - حصتك')
@section('page_title', 'دفعات المجموعة')

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة المحتوى · مجموعات جماعية · دفعات</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $group->title }}</h2>
            <p class="mt-1 text-sm text-muted">كل دفعة = مجموعة طلاب بجدول وسعة وموعد بداية مستقل</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.tutoring-groups.edit', [$type, $group]) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:text-accent">تعديل المجموعة</a>
            <a href="{{ route('admin.tutoring-groups.cohorts.create', $group) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i> دفعة جديدة
            </a>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'ما الفرق بين المجموعة والدفعة؟',
        'body' => 'المجموعة هي العرض العام على الموقع. الدفعة هي التشغيل الفعلي: طلاب محددون + أيام دراسة + سعة مقاعد. يمكنك فتح أكثر من دفعة لنفس العرض.',
        'steps' => [
            'أنشئ دفعة جديدة وحدد الجدول والسعة والحالة.',
            'اضغط «الفصل» لإضافة الطلاب وتوليد الحصص وغرف الاجتماع.',
            'عند امتلاء الدفعة أو انتهاء الفترة أنشئ دفعة تالية.',
        ],
    ])

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-line bg-canvas/60 text-xs font-semibold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">الدفعة</th>
                        <th class="px-4 py-3 text-start">الجدول</th>
                        <th class="px-4 py-3 text-start">البداية</th>
                        <th class="px-4 py-3 text-start">المقاعد</th>
                        <th class="px-4 py-3 text-start">الحالة</th>
                        <th class="px-4 py-3 text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($cohorts as $cohort)
                        <tr class="hover:bg-canvas/40">
                            <td class="px-4 py-3 font-medium text-ink">{{ $cohort->title }}</td>
                            <td class="px-4 py-3 text-xs text-ink-soft">{{ $cohort->scheduleSummary() }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink-soft">{{ $cohort->starts_at?->format('Y-m-d H:i') ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-ink-soft">{{ $cohort->enrolled_count }} / {{ $cohort->capacity }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-accent-soft px-2.5 py-1 text-xs font-semibold text-accent">{{ $cohort->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('admin.tutoring-groups.classes.show', [$group, $cohort]) }}" class="text-accent hover:underline">الفصل</a>
                                <a href="{{ route('admin.tutoring-groups.cohorts.edit', [$group, $cohort]) }}" class="ms-3 text-ink-soft hover:underline">تعديل</a>
                                <form method="POST" action="{{ route('admin.tutoring-groups.cohorts.destroy', [$group, $cohort]) }}" class="inline" onsubmit="return confirm('حذف الدفعة؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ms-3 text-danger hover:underline">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-muted">لا توجد دفعات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cohorts->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $cohorts->links() }}</div>
        @endif
    </div>
</div>
@endsection
