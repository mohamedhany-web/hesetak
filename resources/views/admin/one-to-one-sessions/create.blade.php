@extends('layouts.admin')

@section('title', 'تسكين حصة خاصة')
@section('page_title', 'تسكين حصة 1:1')

@section('content')
@php
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $area = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $selectedEntitlementId = (int) ($selectedEntitlementId ?? 0);
    $slotsUrl = $slotsUrl ?? route('admin.placement.slots');
    $placementUrl = $placementUrl ?? route('admin.placement.create', ['mode' => 'private']);
    $grantUrl = $grantUrl ?? route('admin.student-entitlements.create');
@endphp

<div class="space-y-5" id="oneToOneCreate"
     data-slots-url="{{ $slotsUrl }}">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الطلاب والخدمات · حصص 1:1</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">تسكين طالب مع معلم 1:1</h2>
            <p class="mt-1 text-sm text-muted">من رصيد حصص خاصة أو عام — مع التحقق من توافر المعلم عند اختيار الموعد</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $placementUrl }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-accent/30 bg-surface px-4 text-sm font-medium text-accent transition hover:bg-accent/5">
                <i class="fas fa-magic text-xs"></i>
                معالج التسكين
            </a>
            <a href="{{ route('admin.one-to-one-sessions.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                رجوع للقائمة
            </a>
        </div>
    </section>

    @include('admin.partials.workflow-guide', [
        'title' => 'تسكين حصة خاصة',
        'body' => 'هذه الصفحة لتثبيت حصة 1:1 مباشرة. إن احتجت مساراً أوسع (مجموعات أيضاً) استخدم معالج التسكين.',
        'steps' => [
            'اختر الطالب والرصيد المناسب.',
            'اختر المعلم ثم موعداً من الأوقات المتاحة فقط.',
            'احفظ — تُنشأ الحصة وتظهر في قائمة 1:1.',
        ],
    ])

    @if(session('error'))
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    @if($entitlements->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            لا يوجد طلاب برصيد خاص قابل للحجز حالياً.
            <a href="{{ $grantUrl }}" class="ms-1 font-semibold text-accent underline">منح رصيد الآن</a>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.one-to-one-sessions.store') }}" class="space-y-5" id="oneToOneForm">
        @csrf

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">1) الطالب والرصيد</h3>
                <p class="mt-0.5 text-xs text-muted">ابحث بالطالب ثم اختر الرصيد القابل للحجز</p>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $label }}" for="entitlementSearch">بحث عن طالب / رصيد</label>
                <input type="search" id="entitlementSearch" autocomplete="off"
                       placeholder="بحث بالاسم أو البريد أو الجوال…"
                       class="{{ $field }} mb-2"
                       aria-label="بحث عن طالب">
                <label class="{{ $label }}" for="entitlementSelect">الطالب والرصيد *</label>
                <select name="student_service_entitlement_id" id="entitlementSelect" required
                        class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                        size="8">
                    <option value="">اختر طالباً ورصيداً…</option>
                    @foreach($entitlements as $entitlement)
                        @php
                            $u = $entitlement->user;
                            $left = \App\Services\StudentEntitlementService::bookableUnitsLeft($entitlement);
                            $scopeLabel = \App\Models\ServicePackage::scopes()[$entitlement->scope] ?? $entitlement->scope;
                            $hay = mb_strtolower(trim(($u?->name ?? '').' '.($u?->email ?? '').' '.($u?->phone ?? '').' '.$scopeLabel), 'UTF-8');
                        @endphp
                        <option value="{{ $entitlement->id }}"
                                data-search="{{ e($hay) }}"
                                @selected((string) old('student_service_entitlement_id', $selectedEntitlementId) === (string) $entitlement->id)>
                            {{ $u?->name ?? '—' }} — {{ $left }} حصة — {{ $scopeLabel }}@if($u?->email) — {{ $u->email }}@endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-[11px] text-muted">يظهر فقط من لديهم رصيد خاص أو عام قابل للحجز</p>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">2) المعلم والموعد</h3>
                <p class="mt-0.5 text-xs text-muted">اختر معلماً ثم موعداً من جدول توافره — أو اترك الموعد فارغاً</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
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
                    <label class="{{ $label }}" for="slotSelect">موعد متاح من جدول المعلم</label>
                    <select id="slotSelect" class="{{ $field }}" disabled>
                        <option value="">اختر معلماً أولاً لتحميل المواعيد…</option>
                    </select>
                    <p id="slotsHint" class="mt-2 text-xs text-muted">عند اختيار موعد من القائمة يُملأ الحقل أدناه تلقائياً.</p>
                </div>

                <div class="md:col-span-2">
                    @include('partials.timezone-select', [
                        'value' => old('timezone'),
                        'class' => $field,
                        'labelClass' => $label,
                        'label' => 'توقيت المعلم',
                        'hint' => 'الساعة حسب منطقة المعلم. تتحدد تلقائياً عند اختيار المعلم.',
                    ])
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="scheduledAt">الموعد (اختياري)</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduledAt"
                           min="{{ now()->timezone(auth()->user()?->timezoneCode() ?? 'Africa/Cairo')->format('Y-m-d\TH:i') }}"
                           value="{{ old('scheduled_at') }}"
                           class="{{ $field }}" dir="ltr">
                    <p class="mt-1 text-[11px] text-muted">الساعة حسب توقيت المعلم فوق. لو الجدول فاضي بعد واتساب اكتب الموعد مباشرة.</p>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">3) ملاحظات</h3>
                <p class="mt-0.5 text-xs text-muted">اختياري — للمراجعة الداخلية</p>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $label }}" for="notes">ملاحظات داخلية</label>
                <textarea name="notes" id="notes" rows="3" class="{{ $area }}" placeholder="مثال: تسكين من طلب ولي الأمر…">{{ old('notes') }}</textarea>
            </div>
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white {{ $entitlements->isEmpty() ? 'opacity-50 pointer-events-none' : '' }}">
                <i class="fas fa-user-check text-xs"></i>
                إنشاء التسكين
            </button>
            <a href="{{ route('admin.one-to-one-sessions.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var root = document.getElementById('oneToOneCreate');
  if (!root) return;
  var slotsUrl = root.dataset.slotsUrl;

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

  var entitlementSelect = document.getElementById('entitlementSelect');
  var entitlementSearch = document.getElementById('entitlementSearch');
  var instructorSelect = document.getElementById('instructorSelect');
  var instructorSearch = document.getElementById('instructorSearch');
  var slotSelect = document.getElementById('slotSelect');
  var scheduledAt = document.getElementById('scheduledAt');
  var slotsHint = document.getElementById('slotsHint');

  bindSearch(entitlementSearch, entitlementSelect);
  bindSearch(instructorSearch, instructorSelect);

  function toLocalInput(value) {
    var tzEl = document.getElementById('timezoneSelect');
    var tz = tzEl && tzEl.value ? tzEl.value : 'UTC';
    if (window.hesetakDateTimeLocal && /[zZ]|[+-]\d{2}:?\d{2}$/.test(String(value))) {
      return window.hesetakDateTimeLocal(value, tz);
    }
    if (!value) return '';
    var s = String(value).replace(' ', 'T');
    return s.length >= 16 ? s.slice(0, 16) : s;
  }

  function loadSlots() {
    if (!slotSelect) return;
    slotSelect.disabled = true;
    slotSelect.innerHTML = '<option value="">جارٍ تحميل المواعيد…</option>';
    slotsHint.textContent = 'المواعيد من جدول توافر المعلم فقط.';
    slotsHint.className = 'mt-2 text-xs text-muted';

    if (!instructorSelect.value) {
      slotSelect.innerHTML = '<option value="">اختر معلماً أولاً لتحميل المواعيد…</option>';
      return;
    }

    fetch(slotsUrl + '?mode=private&instructor_id=' + encodeURIComponent(instructorSelect.value), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        slotSelect.innerHTML = '';
        var ph = document.createElement('option');
        ph.value = '';
        ph.textContent = 'بدون موعد الآن / أو اختر من القائمة';
        slotSelect.appendChild(ph);

        if (!data.ok || !(data.slots || []).length) {
          slotsHint.textContent = (data && data.empty_hint) || 'لا مواعيد متاحة لهذا المعلم حالياً.';
          slotsHint.className = 'mt-2 text-xs text-amber-700';
          slotSelect.disabled = false;
          return;
        }

        (data.slots || []).forEach(function (s) {
          var o = document.createElement('option');
          o.value = s.starts_at;
          o.textContent = s.label || s.starts_at;
          slotSelect.appendChild(o);
        });
        slotSelect.disabled = false;
        slotsHint.textContent = 'تم العثور على ' + data.slots.length + ' موعد متاح · مدة الحصة ' + (data.duration_minutes || '') + ' دقيقة';
        slotsHint.className = 'mt-2 text-xs text-emerald-700';
      })
      .catch(function () {
        slotSelect.innerHTML = '<option value="">تعذر تحميل المواعيد</option>';
        slotsHint.textContent = 'حدث خطأ أثناء تحميل المواعيد.';
        slotsHint.className = 'mt-2 text-xs text-danger';
      });
  }

  if (instructorSelect) {
    instructorSelect.addEventListener('change', function () {
      var opt = instructorSelect.options[instructorSelect.selectedIndex];
      var tz = opt && opt.getAttribute('data-timezone');
      var tzEl = document.getElementById('timezoneSelect');
      if (tz && tzEl) tzEl.value = tz;
      loadSlots();
    });
    if (instructorSelect.value) {
      instructorSelect.dispatchEvent(new Event('change'));
    }
  }

  if (slotSelect && scheduledAt) {
    slotSelect.addEventListener('change', function () {
      if (slotSelect.value) {
        scheduledAt.value = toLocalInput(slotSelect.value);
      }
    });
  }
})();
</script>
@endpush
