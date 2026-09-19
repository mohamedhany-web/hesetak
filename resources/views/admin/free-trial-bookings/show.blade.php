@extends('layouts.admin')

@section('title', 'تفاصيل حجز الحصة المجانية - حصتك')
@section('page_title', 'تفاصيل الحجز')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $statusLabel = match ($booking->status) {
        'pending' => 'طلب / بانتظار',
        'confirmed' => 'مؤكد',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        default => $booking->status,
    };
    $statusTone = match ($booking->status) {
        'pending' => 'bg-amber-100 text-amber-800',
        'confirmed' => 'bg-accent-soft text-accent',
        'completed' => 'bg-canvas-muted text-muted',
        'cancelled' => 'bg-danger/10 text-danger',
        default => 'bg-canvas-muted text-muted',
    };
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">حصة مجانية · بدون خصم من رصيد الباقة</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">{{ $booking->name }}</h2>
            <p class="mt-1 text-sm text-muted">حجز #{{ $booking->id }} · {{ $booking->created_at?->diffForHumans() }}</p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.free-trial-bookings.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
            @if($booking->oneToOneSession && Route::has('admin.one-to-one-sessions.show'))
                <a href="{{ route('admin.one-to-one-sessions.show', $booking->oneToOneSession) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-accent/30 bg-surface px-4 text-sm font-medium text-accent transition hover:bg-accent/5">
                    <i class="fas fa-chalkboard-teacher text-xs"></i>
                    فتح الحصة في الجدول
                </a>
            @endif
            <a href="{{ route('admin.free-trial-bookings.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                توصيف جديد
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-calendar-day text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الموعد</p>
            <p class="mt-1 text-base font-semibold tabular-nums tracking-tight text-ink">
                <x-app-datetime :at="$booking->starts_at" :timezone="$booking->timezone" pattern="Y-m-d · g:i A" />
            </p>
            @if($booking->timezone || $booking->us_state)
                <p class="mt-1 text-xs text-muted">
                    @if($booking->us_state)ولاية: {{ $booking->us_state }} · @endif
                    @if($booking->timezone){{ \App\Support\AppTimezone::label($booking->timezone) }}@endif
                </p>
            @endif
            @php
                $slotQuality = \App\Support\AppTimezone::slotQuality(
                    $booking->starts_at,
                    $booking->timezone ?: \App\Support\AppTimezone::academy()
                );
                $qLabels = \App\Support\AppTimezone::qualityLabels($slotQuality);
            @endphp
            <p class="mt-1 text-xs {{ $slotQuality === 'good' ? 'text-emerald-700' : ($slotQuality === 'caution' ? 'text-amber-700' : 'text-rose-700') }}">
                جودة التوقيت للطالب: {{ $qLabels['ar'] }}
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-metal/15 text-metal">
                <i class="fas fa-hourglass-half text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">المدة</p>
            <p class="mt-1 text-base font-semibold tracking-tight text-ink">{{ (int) $booking->duration_minutes }} دقيقة</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $statusTone }}">
                <i class="fas fa-flag text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">الحالة</p>
            <p class="mt-1">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $statusTone }}">{{ $statusLabel }}</span>
            </p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <div class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent">
                <i class="fas fa-envelope text-sm"></i>
            </div>
            <p class="mt-3 text-xs text-muted">التواصل</p>
            <p class="mt-1 truncate text-sm font-semibold text-ink" title="{{ $booking->email }}">{{ $booking->email ?: '—' }}</p>
            <p class="mt-0.5 text-sm text-muted" dir="ltr">{{ $booking->phone ?: '—' }}</p>
        </article>
    </section>

    <div class="grid gap-5 lg:grid-cols-5">
        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft lg:col-span-3">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">بيانات الحجز</h3>
                <p class="mt-0.5 text-xs text-muted">تفاصيل الطالب وهدف التعلّم المرتبط بهذا الموعد</p>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-line bg-canvas/60 p-4">
                        <p class="text-xs font-medium text-muted">الاسم</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $booking->name }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/60 p-4">
                        <p class="text-xs font-medium text-muted">البريد</p>
                        <p class="mt-1 text-sm font-semibold text-ink break-all">{{ $booking->email ?: '—' }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/60 p-4">
                        <p class="text-xs font-medium text-muted">الهاتف / واتساب</p>
                        <p class="mt-1 text-sm font-semibold text-ink" dir="ltr">{{ $booking->phone ?: '—' }}</p>
                        @if($booking->whatsappUrl())
                            <a href="{{ $booking->whatsappUrl() }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline">
                                <i class="fab fa-whatsapp"></i> فتح واتساب
                            </a>
                        @endif
                    </div>
                    <div class="rounded-xl border border-line bg-canvas/60 p-4">
                        <p class="text-xs font-medium text-muted">تاريخ الإنشاء</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $booking->created_at?->format('Y-m-d H:i') ?: '—' }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-line bg-canvas/60 p-4">
                    <p class="text-xs font-medium text-muted">الغرض من التعلم</p>
                    <p class="mt-1 text-sm leading-7 text-ink">{{ $booking->goalLabel('ar') }}</p>
                </div>

                @if($booking->instructor)
                    <div class="rounded-xl border border-line bg-canvas/60 p-4">
                        <p class="text-xs font-medium text-muted">المعلم المختار</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $booking->instructor->name }}</p>
                        <p class="mt-0.5 text-sm text-muted">{{ $booking->instructor->email }}</p>
                    </div>
                @endif

                @if($booking->user)
                    <div class="rounded-xl border border-line bg-canvas/60 p-4">
                        <p class="text-xs font-medium text-muted">حساب مسجّل على المنصة</p>
                        <p class="mt-1 text-sm font-semibold text-ink">{{ $booking->user->name }}</p>
                        <p class="mt-0.5 text-sm text-muted">{{ $booking->user->email }}</p>
                    </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft lg:col-span-2">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">تحديث الحالة</h3>
                <p class="mt-0.5 text-xs text-muted">غيّر الحالة وأضف ملاحظات داخلية للمتابعة</p>
            </div>
            <div class="p-4 sm:p-5">
                <form method="post" action="{{ route('admin.free-trial-bookings.update-status', $booking) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="{{ $labelClass }}" for="status">الحالة</label>
                        <select id="status" name="status" class="{{ $fieldClass }}">
                            <option value="pending" @selected($booking->status === 'pending')>طلب / بانتظار</option>
                            <option value="confirmed" @selected($booking->status === 'confirmed')>مؤكد</option>
                            <option value="completed" @selected($booking->status === 'completed')>مكتمل</option>
                            <option value="cancelled" @selected($booking->status === 'cancelled')>ملغي</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="instructor_id">المعلم المعيّن</label>
                        <select id="instructor_id" name="instructor_id" class="{{ $fieldClass }}">
                            <option value="">— بدون تعيين —</option>
                            @foreach(($instructors ?? []) as $ins)
                                <option value="{{ $ins->id }}" @selected((string) old('instructor_id', $booking->instructor_id) === (string) $ins->id)>
                                    {{ $ins->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="recommended_academic_year_id">السنة المقترحة بعد التقييم</label>
                        <select id="recommended_academic_year_id" name="recommended_academic_year_id" class="{{ $fieldClass }}">
                            <option value="">— بدون توصية —</option>
                            @foreach(($schoolYears ?? []) as $sy)
                                <option value="{{ $sy->id }}" @selected((string) old('recommended_academic_year_id', $booking->recommended_academic_year_id) === (string) $sy->id)>
                                    {{ $sy->level_number ? $sy->level_number.'. ' : '' }}{{ $sy->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="notes">ملاحظات الحجز</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="ملاحظات عامة…">{{ old('notes', $booking->notes) }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="admin_notes">ملاحظات الإدارة (توصية المستوى)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="4" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="سبب التوصية / ملاحظات للمتابعة…">{{ old('admin_notes', $booking->admin_notes) }}</textarea>
                    </div>
                    <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                        <i class="fas fa-save text-xs"></i>
                        حفظ التحديث
                    </button>
                </form>

                <form method="post" action="{{ route('admin.free-trial-bookings.destroy', $booking) }}" class="mt-4 border-t border-line pt-4" onsubmit="return confirm('حذف الحجز نهائياً؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-press inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-danger/30 bg-surface px-4 text-sm font-medium text-danger transition hover:bg-danger/5">
                        <i class="fas fa-trash text-xs"></i>
                        حذف الحجز
                    </button>
                </form>
            </div>
        </article>
    </div>
</div>
@endsection
