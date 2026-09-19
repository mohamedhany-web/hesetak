@extends('layouts.admin')

@section('title', 'توصيف حصة مجانية - حصتك')
@section('page_title', 'توصيف حصة مجانية')

@section('content')
@php
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $area = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $slotsUrl = $slotsUrl ?? route('admin.placement.slots');
@endphp

<div class="space-y-5" id="freeSessionCreate" data-slots-url="{{ $slotsUrl }}">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">التشغيل · حصص مجانية</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">توصيف حصة مجانية يدوياً</h2>
            <p class="mt-1 text-sm text-muted">طالب + معلم + موعد — تنزل في جدولي الطرفين دون خصم من رصيد الباقة</p>
        </div>
        <a href="{{ route('admin.free-trial-bookings.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'حصة مجانية غير مخصومة',
        'body' => 'هذه الحصة تُسجَّل في الحجوزات وتظهر في التقويمين، ولا تُخصم من عدد حصص الباقة.',
        'steps' => [
            'اختر الطالب والمعلم.',
            'حدد موعداً (من جدول المعلم أو يدوياً).',
            'احفظ — تُنشأ غرفة Live ويُشعَر الطرفان.',
        ],
    ])

    @if(session('error'))
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.free-trial-bookings.store') }}" class="space-y-5" id="freeSessionForm">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">1) الطالب</h3>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $label }}" for="studentSearch">بحث عن طالب</label>
                <input type="search" id="studentSearch" autocomplete="off" placeholder="اسم / بريد / جوال…" class="{{ $field }} mb-2">
                <label class="{{ $label }}" for="studentSelect">الطالب *</label>
                <select name="student_id" id="studentSelect" required class="{{ $field }}">
                    <option value="">اختر طالباً…</option>
                    @foreach($students as $student)
                        @php
                            $hay = mb_strtolower(trim($student->name.' '.($student->email ?? '').' '.($student->phone ?? '')), 'UTF-8');
                        @endphp
                        <option value="{{ $student->id }}"
                                data-search="{{ e($hay) }}"
                                @selected((string) old('student_id') === (string) $student->id)>
                            {{ $student->name }}@if($student->email) — {{ $student->email }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">2) المعلم والموعد</h3>
                <p class="mt-0.5 text-xs text-muted">يمكن اختيار موعد من التوافر أو كتابته يدوياً (تجاوز التوافر مسموح للإدارة)</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="instructorSearch">بحث عن معلم</label>
                    <input type="search" id="instructorSearch" autocomplete="off" placeholder="اسم أو بريد…" class="{{ $field }} mb-2">
                    <label class="{{ $label }}" for="instructorSelect">المعلم *</label>
                    <select name="instructor_id" id="instructorSelect" required class="{{ $field }}">
                        <option value="">اختر معلماً…</option>
                        @foreach($instructors as $instructor)
                            @php
                                $iSearch = mb_strtolower(trim($instructor->name.' '.($instructor->email ?? '')), 'UTF-8');
                            @endphp
                            <option value="{{ $instructor->id }}"
                                    data-search="{{ e($iSearch) }}"
                                    data-timezone="{{ e($instructor->timezoneCode()) }}"
                                    @selected((string) old('instructor_id') === (string) $instructor->id)>
                                {{ $instructor->name }} — {{ $instructor->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="slotSelect">موعد مقترح من جدول المعلم</label>
                    <select id="slotSelect" class="{{ $field }}" disabled>
                        <option value="">اختر معلماً أولاً…</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    @include('partials.timezone-select', [
                        'value' => old('timezone'),
                        'class' => $field,
                        'labelClass' => $label,
                        'label' => 'توقيت الموعد',
                        'hint' => 'يُضبط تلقائياً عند اختيار المعلم.',
                    ])
                </div>

                <div>
                    <label class="{{ $label }}" for="scheduledAt">الموعد *</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduledAt" required
                           min="{{ now()->timezone(auth()->user()?->timezoneCode() ?? 'Africa/Cairo')->format('Y-m-d\TH:i') }}"
                           value="{{ old('scheduled_at') }}"
                           class="{{ $field }}" dir="ltr">
                </div>

                <div>
                    <label class="{{ $label }}" for="durationMinutes">المدة</label>
                    <select name="duration_minutes" id="durationMinutes" class="{{ $field }}">
                        @foreach([50 => '50 دقيقة', 30 => '30 دقيقة', 45 => '45 دقيقة', 60 => '60 دقيقة', 90 => '90 دقيقة'] as $mins => $lab)
                            <option value="{{ $mins }}" @selected((string) old('duration_minutes', '50') === (string) $mins)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">3) ملاحظات</h3>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $label }}" for="notes">ملاحظات داخلية</label>
                <textarea name="notes" id="notes" rows="3" class="{{ $area }}" placeholder="اختياري…">{{ old('notes') }}</textarea>
            </div>
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-gift text-xs"></i>
                توصيف الحصة المجانية
            </button>
            <a href="{{ route('admin.free-trial-bookings.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.getElementById('freeSessionCreate');
    if (!root) return;
    const slotsUrl = root.getAttribute('data-slots-url');
    const studentSearch = document.getElementById('studentSearch');
    const studentSelect = document.getElementById('studentSelect');
    const instructorSearch = document.getElementById('instructorSearch');
    const instructorSelect = document.getElementById('instructorSelect');
    const slotSelect = document.getElementById('slotSelect');
    const scheduledAt = document.getElementById('scheduledAt');
    const timezoneSelect = document.querySelector('#freeSessionForm select[name="timezone"]');

    function filterOptions(select, query) {
        const q = (query || '').trim().toLowerCase();
        Array.from(select.options).forEach(function (opt, i) {
            if (i === 0) { opt.hidden = false; return; }
            const hay = (opt.getAttribute('data-search') || opt.textContent || '').toLowerCase();
            opt.hidden = q !== '' && hay.indexOf(q) === -1;
        });
    }

    if (studentSearch && studentSelect) {
        studentSearch.addEventListener('input', function () { filterOptions(studentSelect, studentSearch.value); });
    }
    if (instructorSearch && instructorSelect) {
        instructorSearch.addEventListener('input', function () { filterOptions(instructorSelect, instructorSearch.value); });
    }

    function syncTimezoneFromInstructor() {
        if (!instructorSelect || !timezoneSelect) return;
        const opt = instructorSelect.options[instructorSelect.selectedIndex];
        const tz = opt && opt.getAttribute('data-timezone');
        if (tz) {
            Array.from(timezoneSelect.options).forEach(function (o) {
                if (o.value === tz) timezoneSelect.value = tz;
            });
        }
    }

    function loadSlots() {
        if (!instructorSelect || !slotSelect || !slotsUrl) return;
        const id = instructorSelect.value;
        slotSelect.disabled = true;
        slotSelect.innerHTML = '<option value="">جاري التحميل…</option>';
        if (!id) {
            slotSelect.innerHTML = '<option value="">اختر معلماً أولاً…</option>';
            return;
        }
        fetch(slotsUrl + '?mode=private&instructor_id=' + encodeURIComponent(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            const slots = (data && data.slots) ? data.slots : [];
            slotSelect.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = slots.length ? 'اختر موعداً أو اكتب يدوياً أدناه…' : 'لا مواعيد ظاهرة — اكتب الموعد يدوياً';
            slotSelect.appendChild(empty);
            slots.forEach(function (s) {
                const o = document.createElement('option');
                o.value = s.value || s.starts_at || s.datetime || '';
                o.textContent = s.label || s.value || o.value;
                slotSelect.appendChild(o);
            });
            slotSelect.disabled = false;
        }).catch(function () {
            slotSelect.innerHTML = '<option value="">تعذر التحميل — اكتب الموعد يدوياً</option>';
            slotSelect.disabled = false;
        });
    }

    if (instructorSelect) {
        instructorSelect.addEventListener('change', function () {
            syncTimezoneFromInstructor();
            loadSlots();
        });
        if (instructorSelect.value) {
            syncTimezoneFromInstructor();
            loadSlots();
        }
    }

    if (slotSelect && scheduledAt) {
        slotSelect.addEventListener('change', function () {
            if (!slotSelect.value) return;
            // datetime-local expects YYYY-MM-DDTHH:mm
            var v = slotSelect.value.replace(' ', 'T').slice(0, 16);
            scheduledAt.value = v;
        });
    }
})();
</script>
@endpush
