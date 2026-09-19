@extends('layouts.admin')

@section('title', 'تسكين طالب يدوياً - حصتك')
@section('page_title', 'تسكين طالب في حصة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="mx-auto max-w-4xl space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التشغيل · تسكين يدوي</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">إنشاء حجز من رصيد طالب</h2>
            <p class="mt-1 text-sm text-muted">اختر الرصيد والمجموعة والمعلم والموعد. يتأكد النظام من النطاق والرصيد وتعارض جدول المعلم.</p>
        </div>
        <a href="{{ route('admin.tutoring-group-bookings.index') }}" class="inline-flex h-9 items-center rounded-xl border border-line bg-surface px-4 text-sm text-ink">رجوع</a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'تسكين يدوي من رصيد',
        'body' => 'استخدم هذه الصفحة عندما يكون للطالب رصيد حصص وتريد تثبيت حجز دون انتظار طلب من الموقع.',
        'steps' => [
            'اختر رصيد الطالب المناسب للمجموعة.',
            'حدد المجموعة/الدفعة والمعلم والموعد المتاح.',
            'احفظ — يخصم النظام من الرصيد ويمنع تعارض جدول المعلم.',
        ],
    ])

    @if(session('error'))
        <div class="rounded-2xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm font-medium text-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">
            <ul class="list-disc space-y-1 pe-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tutoring-group-bookings.store') }}" class="grid gap-5 lg:grid-cols-3">
        @csrf
        <article class="space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-soft lg:col-span-2">
            <div>
                <label class="{{ $labelClass }}" for="entitlement">الطالب والرصيد المتاح *</label>
                <select id="entitlement" name="student_service_entitlement_id" required class="{{ $fieldClass }}">
                    <option value="">اختر رصيد الطالب…</option>
                    @foreach($entitlements as $entitlement)
                        @php $bookable = \App\Services\StudentEntitlementService::bookableUnitsLeft($entitlement); @endphp
                        <option value="{{ $entitlement->id }}"
                                data-scope="{{ $entitlement->scope }}"
                                data-group="{{ $entitlement->tutoring_group_id }}"
                                @selected((string) old('student_service_entitlement_id', $selectedEntitlementId) === (string) $entitlement->id)>
                            {{ $entitlement->user?->name }} — {{ $bookable }} متاح من {{ $entitlement->units_total }}
                            — {{ \App\Models\ServicePackage::scopes()[$entitlement->scope] ?? $entitlement->scope }}
                            @if($entitlement->tutoringGroup) — {{ $entitlement->tutoringGroup->title }} @endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-muted">تعرض القائمة الأرصدة النشطة التي تحتوي على وحدة قابلة للحجز فقط.</p>
            </div>

            <div>
                <label class="{{ $labelClass }}" for="group">المجموعة / الفصل *</label>
                <select id="group" name="tutoring_group_id" required class="{{ $fieldClass }}">
                    <option value="">اختر المجموعة…</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" data-instructor="{{ $group->instructor_id }}" @selected((string) old('tutoring_group_id') === (string) $group->id)>
                            {{ $group->title }} — {{ $group->typeLabel() }} — {{ $group->duration_minutes ?: 60 }} دقيقة
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="instructor">المعلم *</label>
                    <select id="instructor" name="instructor_id" required class="{{ $fieldClass }}">
                        <option value="">اختر المعلم…</option>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected((string) old('instructor_id') === (string) $instructor->id)>{{ $instructor->name }} — {{ $instructor->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="starts_at">موعد البداية *</label>
                    <input id="starts_at" type="datetime-local" name="starts_at" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('starts_at') }}" required class="{{ $fieldClass }}" dir="ltr">
                </div>
            </div>

            <div>
                <label class="{{ $labelClass }}" for="admin_notes">ملاحظات داخلية</label>
                <textarea id="admin_notes" name="admin_notes" rows="4" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink">{{ old('admin_notes') }}</textarea>
            </div>
        </article>

        <aside class="h-fit space-y-4 rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="font-semibold text-ink">ما الذي سيحدث؟</h3>
            <ol class="space-y-3 text-sm text-ink-soft">
                <li class="flex gap-2"><span class="font-bold text-accent">1.</span><span>حجز وحدة من رصيد الطالب بدون خصمها الآن.</span></li>
                <li class="flex gap-2"><span class="font-bold text-accent">2.</span><span>إنشاء الحجز وربطه بالطلب والرصيد والمعلم.</span></li>
                <li class="flex gap-2"><span class="font-bold text-accent">3.</span><span>تأكيد الحجز وإنشاء غرفة Live وإشعار الطرفين.</span></li>
                <li class="flex gap-2"><span class="font-bold text-accent">4.</span><span>تُخصم الوحدة بعد إكمال الحصة فقط.</span></li>
            </ol>
            <input type="hidden" name="confirm_now" value="1">
            <button class="btn-press inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-user-check"></i> تسكين وتأكيد الحجز
            </button>
        </aside>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const group = document.getElementById('group');
    const instructor = document.getElementById('instructor');
    group.addEventListener('change', function () {
        const suggested = group.options[group.selectedIndex]?.dataset.instructor;
        if (suggested && !instructor.value) instructor.value = suggested;
    });
});
</script>
@endpush
@endsection
