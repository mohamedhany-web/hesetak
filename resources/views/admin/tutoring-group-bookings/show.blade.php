@extends('layouts.admin')

@section('title', 'تفاصيل حجز مجموعة - حصتك')
@section('page_title', 'تفاصيل الحجز')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">حجوزات المجموعات · #{{ $booking->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $booking->tutoringGroup?->title }}</h2>
        </div>
        <a href="{{ route('admin.tutoring-group-bookings.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع
        </a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'معالجة هذا الحجز',
        'body' => 'راجع بيانات الطالب والمجموعة والموعد، ثم حدّث الحالة (تأكيد / إلغاء) أو عدّل التعيين إن لزم قبل موعد الحصة.',
        'steps' => [
            'تأكد من المجموعة/الدفعة والمعلم والموعد.',
            'أكّد الحجز إن كان المقعد متاحاً.',
            'إن وُجدت غرفة اجتماع راجع رابطها قبل الحصة.',
        ],
    ])

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm font-medium text-danger">{{ session('error') }}</div>
    @endif

    @if($booking->classroomMeeting)
        <article class="rounded-2xl border border-accent/25 bg-accent-soft/30 p-4 shadow-soft sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-accent">Live Meeting</p>
                    <p class="mt-1 text-sm text-ink">رمز الغرفة: <span class="font-mono font-bold" dir="ltr">{{ $booking->classroomMeeting->code }}</span></p>
                    <p class="text-xs text-muted">موعد: {{ $booking->classroomMeeting->scheduled_for?->format('Y-m-d H:i') }}</p>
                </div>
                <a href="{{ url('classroom/join/'.$booking->classroomMeeting->code) }}" target="_blank" rel="noopener" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                    <i class="fas fa-video"></i> دخول الحصة
                </a>
            </div>
        </article>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الحجز</h3>
            </div>
            <dl class="space-y-3 p-4 text-sm sm:p-5">
                <div class="flex justify-between gap-3"><dt class="text-muted">النوع</dt><dd class="font-medium text-ink">{{ $booking->tutoringGroup?->typeLabel() }}</dd></div>
                @if($booking->cohort)
                    <div class="flex justify-between gap-3"><dt class="text-muted">الدفعة</dt><dd class="font-medium text-ink">{{ $booking->cohort->title }}</dd></div>
                @endif
                @if($booking->package)
                    <div class="flex justify-between gap-3"><dt class="text-muted">الباقة</dt><dd class="font-medium text-ink">{{ $booking->package->name }}</dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt class="text-muted">المدرب</dt><dd class="font-medium text-ink">{{ $booking->instructor?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">الطالب</dt><dd class="font-medium text-ink">{{ $booking->contactName() }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">الهاتف</dt><dd class="font-medium text-ink">{{ $booking->contactPhone() ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">البريد</dt><dd class="font-medium text-ink">{{ $booking->contactEmail() ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">من</dt><dd class="font-medium tabular-nums text-ink">{{ $booking->starts_at?->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">إلى</dt><dd class="font-medium tabular-nums text-ink">{{ $booking->ends_at?->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">الحالة</dt><dd class="font-medium text-accent">{{ $booking->statusLabel() }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">الدفع</dt><dd class="font-medium text-ink">{{ $booking->paymentStatusLabel() }}</dd></div>
                @if($booking->entitlement)
                    <div class="rounded-xl border border-line bg-canvas/50 p-3">
                        <div class="flex justify-between gap-3"><dt class="text-muted">مصدر الرصيد</dt><dd class="font-medium text-ink">رصيد #{{ $booking->entitlement->id }}</dd></div>
                        <div class="mt-2 flex justify-between gap-3"><dt class="text-muted">المتبقي الكلي</dt><dd class="font-medium text-ink">{{ $booking->entitlement->unitsLeft() }} / {{ $booking->entitlement->units_total }}</dd></div>
                        <div class="mt-2 flex justify-between gap-3"><dt class="text-muted">القابل للحجز الآن</dt><dd class="font-medium text-accent">{{ \App\Services\StudentEntitlementService::bookableUnitsLeft($booking->entitlement) }}</dd></div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('admin.student-entitlements.index', ['search' => $booking->user?->email]) }}" class="text-xs font-semibold text-accent">عرض أرصدة الطالب</a>
                            @if($booking->order)
                                <a href="{{ route('admin.orders.show', $booking->order) }}" class="text-xs font-semibold text-accent">الطلب #{{ $booking->order_id }}</a>
                            @endif
                        </div>
                    </div>
                @endif
                @if($booking->student_notes)
                    <div><dt class="mb-1 text-muted">ملاحظات الطالب</dt><dd class="text-ink">{{ $booking->student_notes }}</dd></div>
                @endif
            </dl>
        </article>

        <div class="space-y-5">
        @if(! in_array($booking->status, [\App\Models\TutoringGroupBooking::STATUS_COMPLETED, \App\Models\TutoringGroupBooking::STATUS_CANCELLED], true))
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">إعادة التسكين</h3>
                <p class="mt-1 text-xs text-muted">تغيير المجموعة أو المعلم أو الموعد يحدّث غرفة Live الحالية أيضاً.</p>
            </div>
            <form method="POST" action="{{ route('admin.tutoring-group-bookings.update-assignment', $booking) }}" class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                @csrf
                @method('PATCH')
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">المجموعة</label>
                    <select name="tutoring_group_id" class="{{ $fieldClass }}" required>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) $booking->tutoring_group_id === (int) $group->id)>{{ $group->title }} — {{ $group->typeLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">المعلم</label>
                    <select name="instructor_id" class="{{ $fieldClass }}" required>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected((int) $booking->instructor_id === (int) $instructor->id)>{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">الموعد</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $booking->starts_at?->format('Y-m-d\TH:i')) }}" class="{{ $fieldClass }}" required dir="ltr">
                </div>
                <input type="hidden" name="admin_notes" value="{{ $booking->admin_notes }}">
                <button class="btn-press inline-flex h-10 items-center justify-center rounded-xl border border-accent/30 px-4 text-sm font-medium text-accent sm:col-span-2">حفظ التسكين الجديد</button>
            </form>
        </article>
        @endif

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">تحديث الحالة</h3>
                <p class="mt-1 text-xs text-muted">تأكيد الحجز ينشئ غرفة Live Meeting تلقائياً.</p>
            </div>
            <form method="POST" action="{{ route('admin.tutoring-group-bookings.update-status', $booking) }}" class="space-y-4 p-4 sm:p-5">
                @csrf
                @method('PATCH')
                <div>
                    <label class="{{ $labelClass }}" for="status">الحالة</label>
                    <select id="status" name="status" class="{{ $fieldClass }}" required>
                        @foreach(['pending'=>'قيد المراجعة','confirmed'=>'مؤكد (+ Live)','cancelled'=>'ملغي','completed'=>'مكتمل'] as $val => $lab)
                            <option value="{{ $val }}" @selected($booking->status === $val)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="admin_notes">ملاحظات الإدارة</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" class="{{ $areaClass }}">{{ old('admin_notes', $booking->admin_notes) }}</textarea>
                </div>
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">حفظ الحالة</button>
            </form>
            <form method="POST" action="{{ route('admin.tutoring-group-bookings.destroy', $booking) }}" class="border-t border-line p-4 sm:p-5" onsubmit="return confirm('حذف الحجز؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-press inline-flex h-10 items-center rounded-xl border border-line px-4 text-sm font-medium text-danger hover:bg-danger/5">حذف الحجز</button>
            </form>
        </article>
        </div>
    </div>
</div>
@endsection
