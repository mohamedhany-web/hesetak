@extends('layouts.admin')

@section('title', 'تسكين جديد')
@section('page_title', 'تسكين طالب')

@section('content')
@php
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $area = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $mode = $mode ?? 'private';
@endphp

<div class="space-y-5" id="placementWizard"
     data-context-url="{{ route('admin.placement.student-context') }}"
     data-slots-url="{{ route('admin.placement.slots') }}"
     data-grant-url="{{ $grantUrl }}"
     data-mode="{{ $mode }}">

    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الطلاب والخدمات · التسكين</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تسكين طالب مع معلم</h2>
            <p class="mt-1 text-sm text-muted">الإدارة تكتب اليوم والساعة بنفسها — جدول المعلم اختياري للمساعدة فقط. الطالب يشوف الموعد بتوقيته تلقائياً.</p>
        </div>
        <a href="{{ route('admin.placement.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للوحة التسكين
        </a>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'معالج التسكين',
                        'body' => 'للحصص الفردية يُفضّل التثبيت الشهري: حدد عدد الأسابيع ومواعيد كل أسبوع بدل حجز حصة بحصة.',
        'steps' => [
            'اختر الطالب والباقة (الرصيد يجب أن يكفي عدد الحصص).',
            'للفردي: ثبّت شهرياً واكتب اليوم والساعة هنا — لا يشترط أن يكون المعلم قد ضبط جدوله.',
            'اختياري: احفظ نفس المواعيد في جدول المعلم لتظهر لاحقاً في القائمة.',
            'راجع الملخص وثبّت التسكين.',
        ],
    ])

    @if(session('error'))
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        <span class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
            <i class="fas fa-user text-xs"></i> فردي 1:1
        </span>
    </div>

    <form method="POST" action="{{ route('admin.placement.store') }}" class="space-y-5" id="placementForm">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">1) الطالب والباقة</h3>
                <p class="mt-0.5 text-xs text-muted">ابحث عن الطالب ثم اختر الرصيد القابل للحجز</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="studentSearch">الطالب *</label>
                    <input type="search" id="studentSearch" autocomplete="off"
                           placeholder="بحث بالاسم أو البريد أو الجوال…"
                           class="{{ $field }} mb-2"
                           aria-label="بحث عن طالب">
                    <select name="student_id" id="studentSelect" required class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" size="8">
                        <option value="">اختر طالباً…</option>
                        @foreach($students as $student)
                            @php
                                $searchHaystack = mb_strtolower(
                                    trim($student->name.' '.($student->email ?? '').' '.($student->phone ?? '')),
                                    'UTF-8'
                                );
                            @endphp
                            <option value="{{ $student->id }}"
                                    data-search="{{ e($searchHaystack) }}"
                                    @selected((string) old('student_id', $selectedStudentId) === (string) $student->id)>
                                {{ $student->name }} — {{ $student->email }}@if($student->phone) · {{ $student->phone }}@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-muted">اكتب في البحث لتقليص القائمة ثم اختر الطالب</p>
                </div>

                <div>
                    <label class="{{ $label }}" for="entitlementSelect">الباقة / الرصيد *</label>
                    <select name="student_service_entitlement_id" id="entitlementSelect" required class="{{ $field }}">
                        <option value="">اختر الطالب أولاً…</option>
                    </select>
                    <div id="packageStatus" class="mt-3 rounded-xl border border-line bg-canvas/60 px-3 py-2.5 text-xs text-muted">
                        سيظهر هنا إن كان الطالب مشتركاً في باقة أم لا.
                    </div>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">
                    @if($mode === 'private')
                        2) المعلم والموعد
                    @else
                        2) المجموعة والموعد
                    @endif
                </h3>
                    <p class="mt-0.5 text-xs text-muted">اكتب اليوم والساعة بنفسك. قائمة التوافر تظهر فقط إن كان للمعلم جدول محفوظ — وليست شرطاً للتسكين.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                @if($mode === 'private')
                    <div class="md:col-span-2">
                        <label class="{{ $label }}" for="instructorSearch">المعلم *</label>
                        <input type="search" id="instructorSearch" autocomplete="off"
                               placeholder="بحث عن معلم بالاسم أو البريد…"
                               class="{{ $field }} mb-2"
                               aria-label="بحث عن معلم">
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
                        <p class="{{ $label }}">طريقة التثبيت *</p>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-line bg-canvas/50 p-3 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/40">
                                <input type="radio" name="booking_style" value="monthly" class="mt-1" {{ old('booking_style', 'monthly') === 'monthly' ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-semibold text-ink">تثبيت شهري</span>
                                    <span class="mt-0.5 block text-[11px] text-muted">أنت تختار عدد الأسابيع ومواعيد كل أسبوع</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-line bg-canvas/50 p-3 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/40">
                                <input type="radio" name="booking_style" value="multi" class="mt-1" {{ old('booking_style') === 'multi' ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-semibold text-ink">عدة مواعيد</span>
                                    <span class="mt-0.5 block text-[11px] text-muted">اختر أكثر من حصة دفعة واحدة</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-line bg-canvas/50 p-3 has-[:checked]:border-accent has-[:checked]:bg-accent-soft/40">
                                <input type="radio" name="booking_style" value="single" class="mt-1" {{ old('booking_style') === 'single' ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-semibold text-ink">حصة واحدة</span>
                                    <span class="mt-0.5 block text-[11px] text-muted">للحالات الاستثنائية فقط</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-2 grid gap-4 sm:grid-cols-2">
                        @include('partials.timezone-select', [
                            'value' => old('timezone'),
                            'class' => $field,
                            'labelClass' => $label,
                            'label' => 'توقيت المعلم (مقاطعة الحصة)',
                            'hint' => 'الساعة اللي هتكتبها هتتحسب بتوقيت المعلم. الطالب في ولاية تانية هيشوف المعادل بتوقيته.',
                        ])
                        <div>
                            <label class="{{ $label }}" for="manualScheduledAt">الموعد بتوقيت المعلم</label>
                            <input type="datetime-local" name="manual_scheduled_at" id="manualScheduledAt"
                                   value="{{ old('manual_scheduled_at') }}"
                                   class="{{ $field }}" dir="ltr">
                            <p class="mt-1 text-[11px] text-muted">مثال: المعلم قال 12 ظهر مصر → اكتب 12:00 والمنطقة مصر.</p>
                        </div>
                    </div>

                    <div id="monthlyPanel" class="md:col-span-2 space-y-3">
                        @php
                            $oldWeeks = (int) old('weeks', 4);
                            $oldWeeklyPerWeek = (int) old('weekly_per_week', 2);
                            $oldWeeklyPerWeek = max(1, min(7, $oldWeeklyPerWeek ?: 2));
                            $oldWeeks = max(1, min(16, $oldWeeks ?: 4));
                            $dayLabels = $dayLabels ?? \App\Services\OneToOneAvailabilityService::dayLabels();
                        @endphp
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="{{ $label }}" for="weeksInput">عدد الأسابيع</label>
                                <input type="number" name="weeks" id="weeksInput" class="{{ $field }}" dir="ltr"
                                       min="1" max="16" step="1" value="{{ $oldWeeks }}" inputmode="numeric">
                                <div class="mt-2 flex flex-wrap gap-1.5" id="weeksPresets">
                                    @foreach([1, 2, 4, 6, 8, 12, 16] as $w)
                                        <button type="button" data-weeks="{{ $w }}"
                                                class="inline-flex h-8 items-center rounded-lg border border-line bg-canvas px-2.5 text-xs font-semibold text-ink-soft hover:border-accent/40 hover:text-accent {{ (int) $oldWeeks === (int) $w ? 'border-accent bg-accent-soft/50 text-accent' : '' }}">
                                            {{ $w }} أسبوع
                                        </button>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-[11px] text-muted">من أسبوع واحد حتى 16 أسبوعاً.</p>
                            </div>
                            <div>
                                <label class="{{ $label }}" for="weeklyPerWeekInput">مواعيد كل أسبوع</label>
                                <input type="number" name="weekly_per_week" id="weeklyPerWeekInput" class="{{ $field }}" dir="ltr"
                                       min="1" max="7" step="1" value="{{ $oldWeeklyPerWeek }}" inputmode="numeric">
                                <div class="mt-2 flex flex-wrap gap-1.5" id="weeklyPerWeekPresets">
                                    @foreach([1, 2, 3, 4, 5] as $n)
                                        <button type="button" data-weekly="{{ $n }}"
                                                class="inline-flex h-8 items-center rounded-lg border border-line bg-canvas px-2.5 text-xs font-semibold text-ink-soft hover:border-accent/40 hover:text-accent {{ (int) $oldWeeklyPerWeek === (int) $n ? 'border-accent bg-accent-soft/50 text-accent' : '' }}">
                                            {{ $n }} {{ $n === 1 ? 'موعد' : 'مواعيد' }}
                                        </button>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-[11px] text-muted">موعد واحد في اليوم… حتى 7 أيام.</p>
                            </div>
                        </div>
                        <p class="text-xs text-muted">اليوم والساعة بتوقيت المعلم. اكتبهم يدوياً بعد واتساب — حتى لو القائمة فوق فاضية تماماً.</p>
                        <div class="grid gap-3 sm:grid-cols-2" id="weeklySlotsGrid">
                            @foreach(range(0, 6) as $wi)
                                <div class="weekly-slot-card rounded-xl border border-line p-3 space-y-2 {{ $wi >= $oldWeeklyPerWeek ? 'hidden' : '' }}" data-weekly-index="{{ $wi }}">
                                    <label class="{{ $label }}" for="weeklySlot{{ $wi }}">الموعد الأسبوعي {{ $wi + 1 }} {{ $wi === 0 ? '*' : '(اختياري إن لم تحتاجه)' }}</label>
                                    <select id="weeklySlot{{ $wi }}" class="{{ $field }}">
                                        <option value="">اختياري — من جدول المعلم إن وُجد…</option>
                                    </select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select name="weekly_slots[{{ $wi }}][day_of_week]" id="weeklyDay{{ $wi }}" class="{{ $field }}">
                                            <option value="">اليوم</option>
                                            @foreach($dayLabels as $dayNum => $dayName)
                                                <option value="{{ $dayNum }}" @selected((string) old('weekly_slots.'.$wi.'.day_of_week') === (string) $dayNum)>{{ $dayName }}</option>
                                            @endforeach
                                        </select>
                                        <input type="time" name="weekly_slots[{{ $wi }}][time]" id="weeklyTime{{ $wi }}" class="{{ $field }}" dir="ltr" value="{{ old('weekly_slots.'.$wi.'.time') }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p id="monthlyHint" class="text-xs text-muted">سيُولَّد الجدول تلقائياً ويُحجز من الرصيد دفعة واحدة.</p>
                        <label class="flex items-start gap-2 rounded-xl border border-line bg-canvas/50 p-3 text-sm text-ink">
                            <input type="hidden" name="save_as_teacher_schedule" value="0">
                            <input type="checkbox" name="save_as_teacher_schedule" value="1" class="mt-1" @checked(old('save_as_teacher_schedule', '1') !== '0')>
                            <span>
                                <span class="block font-semibold">احفظ هذه المواعيد في جدول المعلم</span>
                                <span class="mt-0.5 block text-[11px] text-muted">حتى تظهر لاحقاً في القائمة وتقدر تسكن عليها طلاب تانيين بدون إعادة الكتابة. يمكن تعديل الجدول أيضاً من صفحة المعلم ← تبويب الجدول.</span>
                            </span>
                        </label>
                    </div>

                    <div id="multiPanel" class="md:col-span-2 hidden">
                        <label class="{{ $label }}" for="multiSlotSelect">المواعيد المتاحة (متعدد) *</label>
                        <select name="scheduled_ats[]" id="multiSlotSelect" multiple size="8" class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" disabled>
                            <option value="" disabled>اختر المعلم لتحميل المواعيد…</option>
                        </select>
                        <p id="multiHint" class="mt-2 text-xs text-muted">اضغط Ctrl/Cmd لاختيار أكثر من موعد مع نفس المعلم.</p>
                    </div>

                    <div id="singlePanel" class="md:col-span-2 hidden">
                        <label class="{{ $label }}" for="slotSelect">من جدول التوافر (اختياري)</label>
                        <select name="scheduled_at" id="slotSelect" class="{{ $field }}" disabled>
                            <option value="">إن كان للمعلم جدول — اختر لملء الحقل فوق</option>
                        </select>
                        <p id="slotsHint" class="mt-2 text-xs text-muted">اختياري. التسكين الأساسي بالساعة المكتوبة بتوقيت المعلم.</p>
                    </div>
                @else
                    <div>
                        <label class="{{ $label }}" for="groupSearch">المجموعة *</label>
                        <input type="search" id="groupSearch" autocomplete="off"
                               placeholder="بحث باسم المجموعة أو المعلم…"
                               class="{{ $field }} mb-2"
                               aria-label="بحث عن مجموعة">
                        <select name="tutoring_group_id" id="groupSelect" required class="{{ $field }}">
                            <option value="">اختر مجموعة…</option>
                            @foreach($groups as $group)
                                @php
                                    $gSearch = mb_strtolower(trim($group->title.' '.($group->instructor?->name ?? '').' '.$group->type), 'UTF-8');
                                @endphp
                                <option value="{{ $group->id }}"
                                        data-instructor="{{ $group->instructor_id }}"
                                        data-type="{{ $group->type }}"
                                        data-search="{{ e($gSearch) }}"
                                        @selected((string) old('tutoring_group_id') === (string) $group->id)>
                                    {{ $group->title }} — {{ $group->typeLabel() }} — {{ $group->instructor?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="instructorSelect">المعلم (من المجموعة أو بديل)</label>
                        <select name="instructor_id" id="instructorSelect" class="{{ $field }}">
                            <option value="">من المجموعة تلقائياً</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected((string) old('instructor_id') === (string) $instructor->id)>
                                    {{ $instructor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}" for="slotSelect">الموعد المتاح *</label>
                        <select name="scheduled_at" id="slotSelect" required class="{{ $field }}" disabled>
                            <option value="">اختر المجموعة لتحميل المواعيد…</option>
                        </select>
                        <p id="slotsHint" class="mt-2 text-xs text-muted">المواعيد تُسحب من جدول توافر المعلم فقط.</p>
                    </div>
                @endif
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">3) ملاحظات</h3>
                <p class="mt-0.5 text-xs text-muted">اختياري — للمراجعة الداخلية فقط</p>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $label }}" for="notes">ملاحظات داخلية</label>
                <textarea name="notes" id="notes" rows="3" class="{{ $area }}" placeholder="مثال: تسكين من مكالمة ولي الأمر…">{{ old('notes') }}</textarea>
            </div>
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" id="submitBtn" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-user-check text-xs"></i>
                تأكيد التسكين
            </button>
            <a href="{{ route('admin.placement.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var root = document.getElementById('placementWizard');
  if (!root) return;

  var contextUrl = root.dataset.contextUrl;
  var slotsUrl = root.dataset.slotsUrl;
  var grantUrl = root.dataset.grantUrl || '';
  var mode = root.dataset.mode || 'private';

  var studentSelect = document.getElementById('studentSelect');
  var studentSearch = document.getElementById('studentSearch');
  var entitlementSelect = document.getElementById('entitlementSelect');
  var instructorSelect = document.getElementById('instructorSelect');
  var instructorSearch = document.getElementById('instructorSearch');
  var groupSelect = document.getElementById('groupSelect');
  var groupSearch = document.getElementById('groupSearch');
  var slotSelect = document.getElementById('slotSelect');
  var multiSlotSelect = document.getElementById('multiSlotSelect');
  var weeklySlotEls = [0, 1, 2, 3, 4, 5, 6].map(function (i) {
    return {
      index: i,
      wrap: document.querySelector('[data-weekly-index="' + i + '"]'),
      select: document.getElementById('weeklySlot' + i),
      day: document.getElementById('weeklyDay' + i),
      time: document.getElementById('weeklyTime' + i)
    };
  });
  var weeksInput = document.getElementById('weeksInput');
  var weeklyPerWeekInput = document.getElementById('weeklyPerWeekInput');
  var weeklySlot0 = weeklySlotEls[0].select;
  var monthlyPanel = document.getElementById('monthlyPanel');
  var multiPanel = document.getElementById('multiPanel');
  var singlePanel = document.getElementById('singlePanel');
  var monthlyHint = document.getElementById('monthlyHint');
  var multiHint = document.getElementById('multiHint');
  var packageStatus = document.getElementById('packageStatus');
  var slotsHint = document.getElementById('slotsHint');
  var submitBtn = document.getElementById('submitBtn');
  var timezoneSelect = document.getElementById('timezoneSelect');
  var manualScheduledAt = document.getElementById('manualScheduledAt');
  var preselectedEntitlement = @json((string) old('student_service_entitlement_id', $selectedEntitlementId ?: ''));

  function bookingStyle() {
    var el = document.querySelector('input[name="booking_style"]:checked');
    return el ? el.value : 'single';
  }

  function bindSearch(input, select) {
    if (!input || !select) return;
    var options = Array.prototype.slice.call(select.querySelectorAll('option'));
    function applyFilter() {
      var q = (input.value || '').trim().toLowerCase();
      options.forEach(function (opt) {
        if (!opt.value) { opt.hidden = false; return; }
        if (opt.selected) { opt.hidden = false; return; }
        var hay = (opt.getAttribute('data-search') || opt.textContent || '').toLowerCase();
        opt.hidden = q.length > 0 && hay.indexOf(q) === -1;
      });
    }
    input.addEventListener('input', applyFilter);
    input.addEventListener('search', applyFilter);
    select.addEventListener('change', applyFilter);
  }

  bindSearch(studentSearch, studentSelect);
  bindSearch(instructorSearch, instructorSelect);
  bindSearch(groupSearch, groupSelect);

  function setOptions(select, options, placeholder) {
    if (!select) return;
    select.innerHTML = '';
    var ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholder;
    select.appendChild(ph);
    options.forEach(function (opt) {
      var o = document.createElement('option');
      o.value = opt.value;
      o.textContent = opt.label;
      if (opt.disabled) o.disabled = true;
      select.appendChild(o);
    });
  }

  function syncWeeklyHidden(selectEl, dayEl, timeEl) {
    if (!selectEl || !dayEl || !timeEl) return;
    var v = selectEl.value || '';
    if (!v) return;
    var parts = v.split('|');
    if (parts[0]) dayEl.value = parts[0];
    if (parts[1]) timeEl.value = parts[1];
  }

  function applyInstructorTimezone() {
    if (!instructorSelect || !timezoneSelect) return;
    var opt = instructorSelect.options[instructorSelect.selectedIndex];
    var tz = opt && opt.getAttribute('data-timezone');
    if (tz) timezoneSelect.value = tz;
  }

  function clampInt(value, min, max, fallback) {
    var n = parseInt(value, 10);
    if (isNaN(n)) return fallback;
    return Math.max(min, Math.min(max, n));
  }

  function filledWeeklyCount() {
    return weeklySlotEls.filter(function (row) {
      return row.wrap && !row.wrap.classList.contains('hidden')
        && row.day && row.day.value && row.time && row.time.value;
    }).length;
  }

  function markPreset(group, attr, value) {
    if (!group) return;
    group.querySelectorAll('button').forEach(function (btn) {
      var on = String(btn.getAttribute(attr)) === String(value);
      btn.classList.toggle('border-accent', on);
      btn.classList.toggle('bg-accent-soft/50', on);
      btn.classList.toggle('text-accent', on);
    });
  }

  function updateMonthlyPreview() {
    if (!monthlyHint) return;
    var weeks = clampInt(weeksInput && weeksInput.value, 1, 16, 4);
    var planned = clampInt(weeklyPerWeekInput && weeklyPerWeekInput.value, 1, 7, 2);
    var filled = filledWeeklyCount();
    var perWeek = filled > 0 ? filled : planned;
    var total = perWeek * weeks;
    if (total > 40) {
      monthlyHint.textContent = 'الحد في الحجز الواحد 40 حصة. قلّل الأسابيع أو المواعيد الأسبوعية (الآن ' + total + ').';
      monthlyHint.className = 'text-xs text-danger';
      return;
    }
    monthlyHint.innerHTML = 'سيُحجز <strong>' + total + '</strong> حصة = '
      + perWeek + ' موعد/أسبوع × ' + weeks + ' أسبوع'
      + (filled ? '' : ' — عبّئ اليوم والساعة لكل موعد.');
    monthlyHint.className = filled ? 'text-xs text-emerald-700' : 'text-xs text-muted';
  }

  function syncWeeklySlotVisibility() {
    var n = clampInt(weeklyPerWeekInput && weeklyPerWeekInput.value, 1, 7, 2);
    if (weeklyPerWeekInput) weeklyPerWeekInput.value = String(n);
    weeklySlotEls.forEach(function (row, i) {
      if (!row.wrap) return;
      var show = i < n;
      row.wrap.classList.toggle('hidden', !show);
      if (!show) {
        if (row.select) row.select.value = '';
        if (row.day) row.day.value = '';
        if (row.time) row.time.value = '';
      }
    });
    markPreset(document.getElementById('weeklyPerWeekPresets'), 'data-weekly', n);
    updateMonthlyPreview();
    refreshSubmit();
  }

  function syncPanels() {
    if (mode !== 'private') return;
    var style = bookingStyle();
    if (monthlyPanel) monthlyPanel.classList.toggle('hidden', style !== 'monthly');
    if (multiPanel) multiPanel.classList.toggle('hidden', style !== 'multi');
    if (singlePanel) singlePanel.classList.toggle('hidden', style !== 'single');
    if (slotSelect) slotSelect.required = false;
    if (weeklySlot0) weeklySlot0.required = false;
    refreshSubmit();
  }

  function refreshSubmit() {
    if (mode === 'private') {
      var style = bookingStyle();
      if (weeklySlotEls[0].day) weeklySlotEls[0].day.required = style === 'monthly';
      if (weeklySlotEls[0].time) weeklySlotEls[0].time.required = style === 'monthly';
    }
  }

  function loadStudentContext() {
    setOptions(entitlementSelect, [], 'جارٍ التحقق من الباقة…');
    packageStatus.textContent = 'جارٍ التحقق…';
    packageStatus.className = 'mt-3 rounded-xl border border-line bg-canvas/60 px-3 py-2.5 text-xs text-muted';
    refreshSubmit();

    if (!studentSelect.value) {
      setOptions(entitlementSelect, [], 'اختر الطالب أولاً…');
      packageStatus.textContent = 'سيظهر هنا إن كان الطالب مشتركاً في باقة أم لا.';
      refreshSubmit();
      return;
    }

    fetch(contextUrl + '?student_id=' + encodeURIComponent(studentSelect.value), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) {
          packageStatus.textContent = data.message || 'تعذر تحميل رصيد الطالب';
          packageStatus.className = 'mt-3 rounded-xl border border-danger/30 bg-danger/5 px-3 py-2.5 text-xs text-danger';
          setOptions(entitlementSelect, [], 'لا رصيد');
          refreshSubmit();
          return;
        }

        var bookable = (data.bookable_entitlements || []).filter(function (e) {
          if (mode === 'private') return e.kind === 'private';
          return e.kind === 'group' || e.scope === 'global';
        });

        if (!data.has_package || bookable.length === 0) {
          packageStatus.innerHTML = 'الطالب <strong>غير مشترك</strong> في باقة مناسبة لهذا النوع من التسكين.'
            + (grantUrl ? ' <a class="font-semibold text-accent underline" href="' + grantUrl + (studentSelect.value ? '?user_id=' + studentSelect.value : '') + '">منح رصيد الآن</a>' : '');
          packageStatus.className = 'mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800';
          setOptions(entitlementSelect, [], 'لا يوجد رصيد قابل للحجز');
          refreshSubmit();
          return;
        }

        packageStatus.textContent = 'مشترك · خاص: ' + (data.private_units || 0) + ' · مجموعات: ' + (data.group_units || 0);
        packageStatus.className = 'mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs text-emerald-800';

        setOptions(entitlementSelect, bookable.map(function (e) {
          return {
            value: String(e.id),
            label: (e.package || e.scope_label) + ' — ' + e.units_left + ' حصة'
              + (e.group_title ? ' — ' + e.group_title : '')
              + (e.expires_at ? ' — حتى ' + e.expires_at : '')
          };
        }), 'اختر الرصيد…');
        if (preselectedEntitlement) entitlementSelect.value = preselectedEntitlement;
        refreshSubmit();
      })
      .catch(function () {
        packageStatus.textContent = 'حدث خطأ أثناء التحقق من الباقة';
        packageStatus.className = 'mt-3 rounded-xl border border-danger/30 bg-danger/5 px-3 py-2.5 text-xs text-danger';
        refreshSubmit();
      });
  }

  function fillWeeklyFromWindows(windows) {
    var opts = (windows || []).map(function (w) {
      return { value: w.day_of_week + '|' + w.start_time, label: w.label || (w.day_label + ' · ' + w.start_time) };
    });
    weeklySlotEls.forEach(function (row, i) {
      var keepDay = row.day ? row.day.value : '';
      var keepTime = row.time ? row.time.value : '';
      setOptions(row.select, opts, i === 0 ? 'اختياري — من جدول المعلم…' : 'اختياري — من الجدول…');
      if (row.select) row.select.disabled = false;
      if (row.day && keepDay) row.day.value = keepDay;
      if (row.time && keepTime) row.time.value = keepTime;
    });
    updateMonthlyPreview();
  }

  function loadSlots() {
    if (slotSelect) {
      slotSelect.disabled = true;
      setOptions(slotSelect, [], 'جارٍ تحميل المواعيد المتاحة…');
    }
    if (multiSlotSelect) {
      multiSlotSelect.disabled = true;
      multiSlotSelect.innerHTML = '';
    }
    if (slotsHint) {
      slotsHint.textContent = 'المواعيد تُسحب من جدول توافر المعلم فقط.';
      slotsHint.className = 'mt-2 text-xs text-muted';
    }
    refreshSubmit();

    var url = slotsUrl + '?mode=' + encodeURIComponent(mode);
    if (mode === 'private') {
      if (!instructorSelect.value) {
        setOptions(slotSelect, [], 'اختر معلماً أولاً…');
        weeklySlotEls.forEach(function (row, i) {
          var keepDay = row.day ? row.day.value : '';
          var keepTime = row.time ? row.time.value : '';
          setOptions(row.select, [], i === 0 ? 'اختر معلماً أولاً…' : 'اختياري…');
          if (row.select) row.select.disabled = false;
          if (row.day && keepDay) row.day.value = keepDay;
          if (row.time && keepTime) row.time.value = keepTime;
        });
        refreshSubmit();
        return;
      }
      url += '&instructor_id=' + encodeURIComponent(instructorSelect.value);
    } else {
      if (!groupSelect || !groupSelect.value) {
        setOptions(slotSelect, [], 'اختر مجموعة أولاً…');
        refreshSubmit();
        return;
      }
      url += '&tutoring_group_id=' + encodeURIComponent(groupSelect.value);
      var opt = groupSelect.options[groupSelect.selectedIndex];
      if (opt && opt.dataset.instructor && instructorSelect && !instructorSelect.value) {
        instructorSelect.value = opt.dataset.instructor;
      }
    }

    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) {
          setOptions(slotSelect, [], data.message || 'تعذر التحميل');
          if (slotsHint) slotsHint.textContent = data.message || '';
          refreshSubmit();
          return;
        }
        var slots = data.slots || [];
        if (mode === 'private') {
          var fromSlots = {};
          (slots || []).forEach(function (s) {
            var key = (s.day_of_week || '') + '|' + (s.time || '');
            if (s.day_of_week && s.time && !fromSlots[key]) {
              fromSlots[key] = {
                day_of_week: s.day_of_week,
                start_time: s.time,
                label: (data.day_labels && data.day_labels[s.day_of_week] ? data.day_labels[s.day_of_week] : ('يوم ' + s.day_of_week)) + ' · ' + s.time
              };
            }
          });
          var slotWindows = Object.keys(fromSlots).map(function (k) { return fromSlots[k]; });
          fillWeeklyFromWindows(slotWindows.length ? slotWindows : (data.weekly_windows || []));
          if (multiSlotSelect) {
            multiSlotSelect.innerHTML = '';
            slots.forEach(function (s) {
              var o = document.createElement('option');
              o.value = s.starts_at;
              o.textContent = s.label || s.starts_at;
              multiSlotSelect.appendChild(o);
            });
            multiSlotSelect.disabled = slots.length === 0;
            if (multiHint) {
              multiHint.textContent = slots.length
                ? 'اختر عدة مواعيد (Ctrl/Cmd) · ' + slots.length + ' متاح'
                : (data.empty_hint || 'لا مواعيد');
            }
          }
        }

        if (data.timezone && timezoneSelect) {
          timezoneSelect.value = data.timezone;
        }

        if (!slots.length) {
          setOptions(slotSelect, [], 'لا مواعيد متاحة');
          if (slotsHint) {
            slotsHint.textContent = data.empty_hint || 'لا مواعيد';
            slotsHint.className = 'mt-2 text-xs text-amber-700';
          }
          refreshSubmit();
          return;
        }
        setOptions(slotSelect, slots.map(function (s) {
          return { value: s.starts_at, label: s.label || s.starts_at };
        }), 'اختر موعداً متاحاً…');
        if (slotSelect) slotSelect.disabled = false;
        if (slotsHint) {
          slotsHint.textContent = 'تم العثور على ' + slots.length + ' موعد متاح · مدة الحصة ' + (data.duration_minutes || '') + ' دقيقة';
          slotsHint.className = 'mt-2 text-xs text-emerald-700';
        }
        refreshSubmit();
      })
      .catch(function () {
        setOptions(slotSelect, [], 'خطأ في التحميل');
        refreshSubmit();
      });
  }

  studentSelect.addEventListener('change', loadStudentContext);
  entitlementSelect.addEventListener('change', refreshSubmit);
  if (instructorSelect) instructorSelect.addEventListener('change', function () {
    applyInstructorTimezone();
    if (mode === 'private') loadSlots();
    else refreshSubmit();
  });
  if (groupSelect) groupSelect.addEventListener('change', loadSlots);
  if (slotSelect) slotSelect.addEventListener('change', function () {
    if (slotSelect.value && manualScheduledAt && window.hesetakDateTimeLocal) {
      var tz = timezoneSelect && timezoneSelect.value ? timezoneSelect.value : 'Africa/Cairo';
      var local = window.hesetakDateTimeLocal(slotSelect.value, tz);
      if (local) manualScheduledAt.value = local;
    }
    refreshSubmit();
  });
  if (multiSlotSelect) multiSlotSelect.addEventListener('change', refreshSubmit);
  if (manualScheduledAt) manualScheduledAt.addEventListener('input', refreshSubmit);
  weeklySlotEls.forEach(function (row) {
    if (row.select) row.select.addEventListener('change', function () {
      syncWeeklyHidden(row.select, row.day, row.time);
      updateMonthlyPreview();
      refreshSubmit();
    });
    if (row.day) row.day.addEventListener('change', function () {
      updateMonthlyPreview();
      refreshSubmit();
    });
    if (row.time) row.time.addEventListener('input', function () {
      updateMonthlyPreview();
      refreshSubmit();
    });
  });
  if (weeksInput) {
    weeksInput.addEventListener('input', function () {
      var weeks = clampInt(weeksInput.value, 1, 16, 4);
      markPreset(document.getElementById('weeksPresets'), 'data-weeks', weeks);
      updateMonthlyPreview();
    });
    weeksInput.addEventListener('change', function () {
      weeksInput.value = String(clampInt(weeksInput.value, 1, 16, 4));
      markPreset(document.getElementById('weeksPresets'), 'data-weeks', weeksInput.value);
      updateMonthlyPreview();
    });
  }
  if (weeklyPerWeekInput) {
    weeklyPerWeekInput.addEventListener('input', syncWeeklySlotVisibility);
    weeklyPerWeekInput.addEventListener('change', syncWeeklySlotVisibility);
  }
  var weeksPresets = document.getElementById('weeksPresets');
  if (weeksPresets) {
    weeksPresets.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-weeks]');
      if (!btn || !weeksInput) return;
      weeksInput.value = btn.getAttribute('data-weeks');
      markPreset(weeksPresets, 'data-weeks', weeksInput.value);
      updateMonthlyPreview();
    });
  }
  var weeklyPresets = document.getElementById('weeklyPerWeekPresets');
  if (weeklyPresets) {
    weeklyPresets.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-weekly]');
      if (!btn || !weeklyPerWeekInput) return;
      weeklyPerWeekInput.value = btn.getAttribute('data-weekly');
      syncWeeklySlotVisibility();
    });
  }
  document.querySelectorAll('input[name="booking_style"]').forEach(function (el) {
    el.addEventListener('change', syncPanels);
  });

  var placementForm = document.getElementById('placementForm');
  if (placementForm) {
    placementForm.addEventListener('submit', function () {
      if (entitlementSelect) entitlementSelect.disabled = false;
      if (slotSelect) slotSelect.disabled = false;
      if (multiSlotSelect) multiSlotSelect.disabled = false;
      weeklySlotEls.forEach(function (row) {
        if (row.select) row.select.disabled = false;
        if (row.day) row.day.disabled = false;
        if (row.time) row.time.disabled = false;
      });
      if (submitBtn) submitBtn.disabled = false;
    });
  }

  if (studentSelect.value) loadStudentContext();
  applyInstructorTimezone();
  if (mode === 'private' && instructorSelect && instructorSelect.value) loadSlots();
  if (mode === 'group' && groupSelect && groupSelect.value) loadSlots();
  syncPanels();
  syncWeeklySlotVisibility();
  refreshSubmit();
})();
</script>
@endpush
