@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $canPropose = auth()->check() && auth()->user()->isStudent();
@endphp

@push('head')
<style>
  .mc-sl-note{display:flex;gap:.6rem;align-items:flex-start;padding:.8rem 1rem;border-radius:.85rem;background:color-mix(in srgb,var(--mc-primary,#c9a227) 12%,transparent);border:1px solid color-mix(in srgb,var(--mc-primary,#c9a227) 35%,transparent);font-size:.92rem;line-height:1.7}
  .mc-sl-note i{margin-top:.25rem;color:var(--mc-primary,#c9a227)}
  .mc-sl-panel{display:grid;gap:.85rem;padding:1rem;border:1px solid color-mix(in srgb,var(--mc-ink,#0b1b33) 14%,transparent);border-radius:.9rem}
  .mc-sl-panel[hidden]{display:none}
  .mc-sl-chosen-wrap{display:grid;gap:.4rem}
  .mc-sl-chosen-wrap[hidden]{display:none}
  .mc-sl-grid{display:grid;gap:.65rem;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}
  .mc-sl-grid label{display:grid;gap:.3rem;font-size:.85rem;font-weight:600}
  .mc-sl-field{width:100%;min-height:44px;padding:.5rem .7rem;border-radius:.65rem;border:1px solid color-mix(in srgb,var(--mc-ink,#0b1b33) 22%,transparent);background:#fff;color:inherit;font:inherit}
  .mc-sl-field:disabled{opacity:.55}
  .mc-sl-chips{display:flex;flex-wrap:wrap;gap:.45rem;margin:0;padding:0;list-style:none}
  .mc-sl-chip{display:inline-flex;align-items:center;gap:.45rem;padding:.35rem .35rem .35rem .75rem;border-radius:999px;background:color-mix(in srgb,var(--mc-primary,#c9a227) 18%,transparent);font-size:.88rem}
  .mc-sl-chip button{border:0;background:rgba(0,0,0,.12);border-radius:999px;width:24px;height:24px;cursor:pointer;line-height:1}
  .mc-sl-row{display:grid;gap:.6rem;grid-template-columns:2fr 1fr 1fr 1fr auto;align-items:end;padding:.75rem;border:1px dashed color-mix(in srgb,var(--mc-ink,#0b1b33) 16%,transparent);border-radius:.75rem}
  @media (max-width:640px){.mc-sl-row{grid-template-columns:1fr 1fr}.mc-sl-row .mc-sl-date{grid-column:1/-1}}
  .mc-sl-row label{display:grid;gap:.3rem;font-size:.82rem;font-weight:600}
</style>
@endpush

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'المسار: المعلمون → المواعيد' : 'Flow: teachers → slots' }}</p>
    <h1>{{ $instructor->name }}</h1>
    <p class="mc-lead">{{ $package->name }} · {{ (int)$sessions }} {{ $isRtl ? 'حصة' : 'sessions' }} · {{ format_money($totalPrice, 2) }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1.25rem;max-width:720px">
    <article class="mc-card" style="padding:1.25rem">
      <h2 class="mc-title" style="font-size:1.1rem">{{ $isRtl ? 'اختيار المواعيد' : 'Choose your times' }}</h2>
      <p class="mc-lead" style="margin-top:.35rem">{{ $isRtl ? 'كل الأوقات بتوقيتك مع تمييز صباحاً (AM) / مساءً (PM).' : 'All times are in your timezone with AM/PM.' }} · {{ \App\Support\AppTimezone::label($viewerTz) }}</p>

      @if(session('success'))
        <p class="mc-lead" style="color:#15803d;margin-top:.75rem">{{ session('success') }}</p>
      @endif
      @if(session('error'))
        <p class="mc-lead" style="color:#b91c1c;margin-top:.75rem">{{ session('error') }}</p>
      @endif
      @if($errors->any())
        <p class="mc-lead" style="color:#b91c1c;margin-top:.75rem">{{ $errors->first() }}</p>
      @endif

      <form method="post" action="{{ route('public.package-booking.add-cart', $package) }}" style="display:grid;gap:1.1rem;margin-top:1rem" id="mc-sl-form">
        @csrf
        <input type="hidden" name="instructor_id" value="{{ $instructor->id }}">
        @if($yearId)<input type="hidden" name="year_id" value="{{ (int) $yearId }}">@endif
        @if($subjectId)<input type="hidden" name="subject_id" value="{{ (int) $subjectId }}">@endif
        <input type="hidden" name="curriculum_type" value="{{ $curriculum ?? '' }}">
        <input type="hidden" name="weekly_sessions" value="{{ (int) $weekly }}">
        <input type="hidden" name="weeks" value="{{ (int) $weeks }}">
        <input type="hidden" name="sessions" value="{{ (int) $sessions }}">

        {{-- 1) مواعيد المعلم المتاحة --}}
        <div style="display:grid;gap:.7rem">
          <strong>{{ $isRtl ? '١) مواعيد المعلم المتاحة' : '1) Teacher\'s available times' }}</strong>
          <div class="mc-sl-note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>{{ $isRtl
              ? 'لإظهار المواعيد المتاحة للمعلم اضغط على زر «عرض مواعيد المعلم المتاحة» ثم اختر اليوم والوقت (صباحاً AM / مساءً PM) واضغط «إضافة الموعد».'
              : 'Press “Show teacher\'s available times”, then pick a day and a time (AM / PM) and press “Add time”.' }}</span>
          </div>

          @if(empty($slotsByDate))
            <p class="mc-lead">{{ $isRtl ? 'لا مواعيد منشورة لهذا المعلم حالياً — اقترح مواعيدك في الخطوة التالية.' : 'No published times yet — suggest your own below.' }}</p>
          @else
            <div>
              <button type="button" class="mc-btn mc-btn--md mc-btn--outline" id="mc-sl-toggle" aria-expanded="false" aria-controls="mc-sl-panel">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                {{ $isRtl ? 'عرض مواعيد المعلم المتاحة' : 'Show teacher\'s available times' }}
              </button>
            </div>
            <div class="mc-sl-panel" id="mc-sl-panel" hidden>
              <div class="mc-sl-grid">
                <label>{{ $isRtl ? 'اليوم' : 'Day' }}
                  <select class="mc-sl-field" id="mc-sl-date">
                    <option value="">{{ $isRtl ? 'اختر اليوم…' : 'Choose a day…' }}</option>
                    @foreach($slotsByDate as $dateKey => $day)
                      <option value="{{ $dateKey }}">{{ $day['label'] }} ({{ $dateKey }})</option>
                    @endforeach
                  </select>
                </label>
                <label>{{ $isRtl ? 'الوقت' : 'Time' }}
                  <select class="mc-sl-field" id="mc-sl-time" disabled>
                    <option value="">{{ $isRtl ? 'اختر اليوم أولاً' : 'Choose a day first' }}</option>
                  </select>
                </label>
              </div>
              <div>
                <button type="button" class="mc-btn mc-btn--md mc-btn--primary" id="mc-sl-add" disabled>
                  <i class="fas fa-plus" aria-hidden="true"></i> {{ $isRtl ? 'إضافة الموعد' : 'Add time' }}
                </button>
              </div>
            </div>
          @endif

          <div id="mc-sl-chosen-wrap" class="mc-sl-chosen-wrap" hidden>
            <span style="font-size:.85rem;font-weight:600">{{ $isRtl ? 'المواعيد التي اخترتها:' : 'Your chosen times:' }}</span>
            <ul class="mc-sl-chips" id="mc-sl-chosen"></ul>
          </div>
          <div id="mc-sl-hidden"></div>
        </div>

        {{-- 2) مواعيد يقترحها الزائر --}}
        <div style="border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent);padding-top:1rem;display:grid;gap:.7rem">
          <strong>{{ $isRtl ? '٢) اقترح مواعيد من عندك (اختياري)' : '2) Suggest your own times (optional)' }}</strong>
          <p class="mc-lead" style="margin:0">{{ $isRtl ? 'اختر التاريخ والساعة والفترة (AM صباحاً / PM مساءً). يصل اقتراحك إلى حساب المعلم ليقبله أو يرفضه.' : 'Pick date, hour and period (AM/PM). Your suggestion goes to the teacher to accept or reject.' }}</p>
          <div id="mc-sl-rows" style="display:grid;gap:.6rem"></div>
          <div>
            <button type="button" class="mc-btn mc-btn--md mc-btn--outline" id="mc-sl-add-row">
              <i class="fas fa-plus" aria-hidden="true"></i> {{ $isRtl ? 'إضافة موعد مقترح' : 'Add suggested time' }}
            </button>
          </div>
          <label style="display:grid;gap:.3rem;font-weight:600;font-size:.9rem">{{ $isRtl ? 'ملاحظات للمعلم' : 'Notes for the teacher' }}
            <textarea name="schedule_notes" rows="2" maxlength="1000" class="mc-sl-field" placeholder="{{ $isRtl ? 'مثال: أفضّل أيام الجمعة والسبت' : 'e.g. I prefer weekends' }}"></textarea>
          </label>
        </div>

        <div style="display:grid;gap:.65rem;border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent);padding-top:1rem">
          @if($canPropose)
            <div class="mc-sl-note">
              <i class="fas fa-paper-plane" aria-hidden="true"></i>
              <span>{{ $isRtl ? 'لإرسال المواعيد التي اخترتها أو اقترحتها إلى حساب المعلم للموافقة أو الرفض اضغط «إرسال المواعيد للمعلم».' : 'Press “Send times to teacher” to send your chosen/suggested times for acceptance or rejection.' }}</span>
            </div>
          @else
            <div class="mc-sl-note">
              <i class="fas fa-lock" aria-hidden="true"></i>
              <span>{{ $isRtl ? 'سجّل الدخول بحساب طالب لتتمكن من إرسال المواعيد للمعلم.' : 'Sign in as a student to send times to the teacher.' }}</span>
            </div>
          @endif
          <div style="display:flex;flex-wrap:wrap;gap:.65rem">
            <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary" formaction="{{ route('public.package-booking.propose', $package) }}">
              <i class="fas fa-paper-plane" aria-hidden="true"></i> {{ $isRtl ? 'إرسال المواعيد للمعلم' : 'Send times to teacher' }}
            </button>
            <button type="submit" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'أضف للسلة وتابع' : 'Add to cart & continue' }}</button>
            <a href="{{ route('public.package-booking.teachers', ['servicePackage' => $package->id, 'curriculum' => $curriculum ?? '', 'year' => $yearId, 'subject' => $subjectId, 'weekly' => $weekly, 'weeks' => $weeks]) }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $isRtl ? 'رجوع للمعلمين' : 'Back to teachers' }}</a>
          </div>
        </div>
      </form>
    </article>
  </div>
</section>

<script>
(function () {
  var isRtl = @json($isRtl);
  var slotsByDate = @json($slotsByDate);
  var form = document.getElementById('mc-sl-form');
  if (!form) return;

  var chosen = {}; // iso => label
  var chosenList = document.getElementById('mc-sl-chosen');
  var chosenWrap = document.getElementById('mc-sl-chosen-wrap');
  var hiddenBox = document.getElementById('mc-sl-hidden');

  function renderChosen() {
    chosenList.innerHTML = '';
    hiddenBox.innerHTML = '';
    var keys = Object.keys(chosen).sort();
    chosenWrap.hidden = keys.length === 0;
    keys.forEach(function (iso) {
      var li = document.createElement('li');
      li.className = 'mc-sl-chip';
      var span = document.createElement('span');
      span.textContent = chosen[iso];
      var rm = document.createElement('button');
      rm.type = 'button';
      rm.setAttribute('aria-label', isRtl ? 'حذف الموعد' : 'Remove time');
      rm.textContent = '×';
      rm.addEventListener('click', function () { delete chosen[iso]; renderChosen(); });
      li.appendChild(span); li.appendChild(rm);
      chosenList.appendChild(li);
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = 'suggested_slots[]'; input.value = iso;
      hiddenBox.appendChild(input);
    });
  }

  var toggle = document.getElementById('mc-sl-toggle');
  var panel = document.getElementById('mc-sl-panel');
  var dateSel = document.getElementById('mc-sl-date');
  var timeSel = document.getElementById('mc-sl-time');
  var addBtn = document.getElementById('mc-sl-add');

  if (toggle && panel) {
    toggle.addEventListener('click', function () {
      var open = panel.hidden;
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  if (dateSel && timeSel && addBtn) {
    dateSel.addEventListener('change', function () {
      var day = slotsByDate[dateSel.value];
      timeSel.innerHTML = '';
      addBtn.disabled = true;
      if (!day) {
        timeSel.disabled = true;
        timeSel.innerHTML = '<option value="">' + (isRtl ? 'اختر اليوم أولاً' : 'Choose a day first') + '</option>';
        return;
      }
      var first = document.createElement('option');
      first.value = ''; first.textContent = isRtl ? 'اختر الوقت…' : 'Choose a time…';
      timeSel.appendChild(first);
      [['AM', isRtl ? 'صباحاً (AM)' : 'Morning (AM)'], ['PM', isRtl ? 'مساءً (PM)' : 'Afternoon / Evening (PM)']].forEach(function (g) {
        var items = day.slots.filter(function (s) { return s.period === g[0]; });
        if (!items.length) return;
        var group = document.createElement('optgroup');
        group.label = g[1];
        items.forEach(function (s) {
          var o = document.createElement('option');
          o.value = s.iso; o.textContent = s.time;
          group.appendChild(o);
        });
        timeSel.appendChild(group);
      });
      timeSel.disabled = false;
    });
    timeSel.addEventListener('change', function () { addBtn.disabled = !timeSel.value; });
    addBtn.addEventListener('click', function () {
      if (!timeSel.value) return;
      var day = slotsByDate[dateSel.value];
      chosen[timeSel.value] = (day ? day.label : '') + ' — ' + timeSel.options[timeSel.selectedIndex].textContent;
      renderChosen();
      timeSel.value = ''; addBtn.disabled = true;
    });
  }

  // مواعيد يقترحها الزائر
  var rowsBox = document.getElementById('mc-sl-rows');
  var addRowBtn = document.getElementById('mc-sl-add-row');
  var rowIndex = 0;
  var today = new Date(); today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
  var minDate = today.toISOString().slice(0, 10);

  function hourOptions() {
    var out = '';
    for (var h = 1; h <= 12; h++) out += '<option value="' + h + '">' + h + '</option>';
    return out;
  }

  function addRow() {
    var i = rowIndex++;
    var row = document.createElement('div');
    row.className = 'mc-sl-row';
    row.innerHTML =
      '<label class="mc-sl-date">' + (isRtl ? 'التاريخ' : 'Date') + '<input type="date" class="mc-sl-field" name="proposed[' + i + '][date]" min="' + minDate + '"></label>' +
      '<label>' + (isRtl ? 'الساعة' : 'Hour') + '<select class="mc-sl-field" name="proposed[' + i + '][hour]">' + hourOptions() + '</select></label>' +
      '<label>' + (isRtl ? 'الدقيقة' : 'Min') + '<select class="mc-sl-field" name="proposed[' + i + '][minute]"><option>00</option><option>15</option><option>30</option><option>45</option></select></label>' +
      '<label>' + (isRtl ? 'الفترة' : 'Period') + '<select class="mc-sl-field" name="proposed[' + i + '][period]"><option value="AM">AM ' + (isRtl ? 'صباحاً' : '') + '</option><option value="PM">PM ' + (isRtl ? 'مساءً' : '') + '</option></select></label>' +
      '<button type="button" class="mc-btn mc-btn--sm mc-btn--soft" aria-label="' + (isRtl ? 'حذف' : 'Remove') + '">×</button>';
    row.querySelector('button').addEventListener('click', function () { row.remove(); });
    rowsBox.appendChild(row);
  }
  if (addRowBtn) addRowBtn.addEventListener('click', addRow);
  addRow();
})();
</script>
@endsection
