@extends('layouts.admin')

@section('title', 'الفصل: '.$cohort->title.' - حصتك')
@section('page_title', 'إدارة الفصل')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">{{ $group->title }} · فصل تعليمي</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $cohort->title }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $cohort->scheduleSummary() }}
                · {{ $cohort->enrolled_count }}/{{ $cohort->capacity }} طالب
                · {{ $cohort->statusLabel() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.tutoring-groups.cohorts.edit', [$group, $cohort]) }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">تعديل الدفعة</a>
            <a href="{{ route('admin.tutoring-groups.cohorts.index', $group) }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">كل الدفعات</a>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'تشغيل الفصل خطوة بخطوة',
        'body' => 'هذه غرفة التشغيل اليومية للدفعة: الطلاب، الحصص، وغرف Live. ابدأ بتسجيل الطلاب ثم ولّد الجدول.',
        'steps' => [
            'أضف الطلاب المسجّلين في الدفعة (أو عبر التسكين/الحجوزات).',
            'اضغط توليد الجدول لإنشاء الحصص حسب أيام الدراسة.',
            'تأكد أن لكل حصة غرفة اجتماع جاهزة قبل الموعد.',
            'تابع الحضور والحالة من نفس الصفحة.',
        ],
    ])

    @if(session('success'))
        <div class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm font-medium text-danger">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الحصص</p>
            <p class="mt-1 text-2xl font-semibold text-ink">{{ $cohort->classSessions->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الطلاب النشطون</p>
            <p class="mt-1 text-2xl font-semibold text-ink">{{ $cohort->enrollments->where('status','active')->count() }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الحصة التالية</p>
            <p class="mt-1 text-sm font-semibold text-ink">{{ $nextSession?->starts_at?->format('Y-m-d H:i') ?: '—' }}</p>
        </article>
    </div>

    <section class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.tutoring-groups.classes.generate-schedule', [$group, $cohort]) }}">
            @csrf
            <input type="hidden" name="replace_future" value="0">
            <button type="submit" class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">توليد جدول الحصص</button>
        </form>
        <form method="POST" action="{{ route('admin.tutoring-groups.classes.generate-schedule', [$group, $cohort]) }}">
            @csrf
            <input type="hidden" name="replace_future" value="1">
            <button type="submit" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft" onclick="return confirm('إعادة توليد الحصص المستقبلية غير المرتبطة بغرفة؟')">إعادة توليد المستقبلية</button>
        </form>
        <form method="POST" action="{{ route('admin.tutoring-groups.classes.ensure-rooms', [$group, $cohort]) }}">
            @csrf
            <button type="submit" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">تهيئة غرف Live</button>
        </form>
    </section>

    <div class="grid gap-5 xl:grid-cols-5">
        <div class="space-y-5 xl:col-span-3">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="flex items-center justify-between border-b border-line px-4 py-3">
                    <h3 class="text-sm font-semibold text-ink">جدول الحصص</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-canvas/60 text-xs font-semibold text-muted">
                            <tr>
                                <th class="px-3 py-2 text-start">#</th>
                                <th class="px-3 py-2 text-start">العنوان</th>
                                <th class="px-3 py-2 text-start">الموعد</th>
                                <th class="px-3 py-2 text-start">الحالة</th>
                                <th class="px-3 py-2 text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($cohort->classSessions as $session)
                                <tr>
                                    <td class="px-3 py-2 tabular-nums text-muted">{{ $session->session_number }}</td>
                                    <td class="px-3 py-2 font-medium text-ink">{{ $session->displayTitle() }}</td>
                                    <td class="px-3 py-2 tabular-nums text-ink-soft">
                                        {{ $session->starts_at?->format('Y-m-d H:i') }}
                                        @if($session->ends_at)
                                            <span class="text-muted">→ {{ $session->ends_at->format('H:i') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2"><span class="rounded-full bg-accent-soft px-2 py-0.5 text-xs font-semibold text-accent">{{ $session->statusLabel() }}</span></td>
                                    <td class="px-3 py-2 text-end">
                                        <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                            @if($session->classroomMeeting)
                                                <a href="{{ url('classroom/join/'.$session->classroomMeeting->code) }}" class="text-accent hover:underline" target="_blank">دخول</a>
                                            @else
                                                <form method="POST" action="{{ route('admin.tutoring-groups.classes.sessions.room', [$group, $cohort, $session]) }}">@csrf
                                                    <button class="text-accent hover:underline">غرفة</button>
                                                </form>
                                            @endif
                                            @if($session->status !== 'cancelled' && $session->status !== 'completed')
                                                <form method="POST" action="{{ route('admin.tutoring-groups.classes.sessions.complete', [$group, $cohort, $session]) }}">@csrf
                                                    <button class="text-ink-soft hover:underline">إكمال</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.tutoring-groups.classes.sessions.cancel', [$group, $cohort, $session]) }}" onsubmit="return confirm('إلغاء الحصة؟')">@csrf
                                                    <button class="text-danger hover:underline">إلغاء</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">لا حصص بعد — ولّد الجدول من أيام الدراسة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <h3 class="mb-3 text-sm font-semibold text-ink">إضافة حصة يدوية</h3>
                <form method="POST" action="{{ route('admin.tutoring-groups.classes.sessions.store', [$group, $cohort]) }}" class="grid gap-3 md:grid-cols-2">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs text-muted">العنوان</label>
                        <input name="title" class="{{ $fieldClass }}" placeholder="اختياري">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-muted">البداية</label>
                        <input type="datetime-local" name="starts_at" class="{{ $fieldClass }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-muted">النهاية</label>
                        <input type="datetime-local" name="ends_at" class="{{ $fieldClass }}">
                    </div>
                    <div class="flex items-end">
                        <button class="btn-press inline-flex h-10 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">إضافة</button>
                    </div>
                </form>
            </article>
        </div>

        <div class="space-y-5 xl:col-span-2">
            <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                <div class="border-b border-line px-4 py-3">
                    <h3 class="text-sm font-semibold text-ink">طلاب الفصل</h3>
                </div>
                <ul class="divide-y divide-line">
                    @forelse($cohort->enrollments as $enrollment)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{{ $enrollment->user?->name ?: '—' }}</p>
                                <p class="truncate text-xs text-muted">{{ $enrollment->user?->email }} · {{ $enrollment->statusLabel() }}</p>
                            </div>
                            @if($enrollment->status === 'active')
                                <form method="POST" action="{{ route('admin.tutoring-groups.classes.enrollments.destroy', [$group, $cohort, $enrollment]) }}" onsubmit="return confirm('إلغاء تسجيل الطالب؟')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline">إلغاء</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-muted">لا طلاب مسجلين بعد.</li>
                    @endforelse
                </ul>
            </article>

            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <h3 class="mb-3 text-sm font-semibold text-ink">إضافة طالب (معرف المستخدم)</h3>
                <form method="POST" action="{{ route('admin.tutoring-groups.classes.enrollments.store', [$group, $cohort]) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs text-muted">User ID</label>
                        <input type="number" name="user_id" class="{{ $fieldClass }}" required min="1">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-muted">ملاحظات</label>
                        <input name="notes" class="{{ $fieldClass }}">
                    </div>
                    <button class="btn-press inline-flex h-10 items-center rounded-xl bg-accent px-4 text-sm font-medium text-white">انضمام</button>
                </form>
            </article>
        </div>
    </div>
</div>
@endsection
