<form method="POST" action="{{ route('student.one-to-one-sessions.book-instructor', $profile->user) }}" class="mc-tp-form" id="glTpBookForm">
  @csrf
  <div class="mc-tp-style">
    <label class="mc-tp-choice">
      <input type="radio" name="booking_style" value="monthly" checked>
      <span>
        <strong>{{ $isRtl ? 'تثبيت شهري (موصى به)' : 'Monthly lock (recommended)' }}</strong>
        <small>{{ $isRtl ? 'اختر حتى 7 مواعيد أسبوعياً لمدة تصل إلى 8 أسابيع' : 'Pick up to 7 weekly times for up to 8 weeks' }}</small>
      </span>
    </label>
    <label class="mc-tp-choice">
      <input type="radio" name="booking_style" value="multi">
      <span>
        <strong>{{ $isRtl ? 'عدة مواعيد' : 'Multiple slots' }}</strong>
        <small>{{ $isRtl ? 'اختر أكثر من حصة مرة واحدة' : 'Select several sessions at once' }}</small>
      </span>
    </label>
    <label class="mc-tp-choice">
      <input type="radio" name="booking_style" value="single">
      <span>
        <strong>{{ $isRtl ? 'حصة واحدة' : 'Single session' }}</strong>
      </span>
    </label>
  </div>

  <div id="glTpMonthly" class="mc-tp-fields">
    @foreach([0,1,2,3] as $i)
      <label class="mc-tp-field">
        {{ $i === 0 ? ($isRtl ? 'الموعد الأسبوعي 1' : 'Weekly slot 1') : ($isRtl ? 'الموعد الأسبوعي '.($i+1) : 'Weekly slot '.($i+1)) }}
        <select id="glTpW{{ $i }}" @if($i===0) required @endif>
          <option value="">{{ $i === 0 ? ($isRtl ? 'اختر…' : 'Choose…') : ($isRtl ? 'اختياري…' : 'Optional…') }}</option>
          @foreach($weeklyOpts as $opt)
            <option value="{{ $opt['day'] }}|{{ $opt['time'] }}">{{ $opt['label'] }}</option>
          @endforeach
        </select>
        <input type="hidden" name="weekly_slots[{{ $i }}][day_of_week]" id="glTpW{{ $i }}Day">
        <input type="hidden" name="weekly_slots[{{ $i }}][time]" id="glTpW{{ $i }}Time">
      </label>
    @endforeach
    <label class="mc-tp-field">{{ $isRtl ? 'عدد الأسابيع' : 'Weeks' }}
      <select name="weeks">
        <option value="4" selected>4</option>
        <option value="3">3</option>
        <option value="2">2</option>
        <option value="6">6</option>
        <option value="8">8</option>
      </select>
    </label>
    <button type="submit" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'تثبيت الجدول الشهري' : 'Lock monthly schedule' }}</button>
  </div>

  <div id="glTpMulti" class="mc-tp-book-pane" hidden>
    <div class="mc-tp-slots">
      @foreach($bookableSlots as $slot)
        @php
          $starts = is_array($slot) ? ($slot['starts_at'] ?? null) : ($slot->starts_at ?? null);
          $label = is_array($slot) ? ($slot['label'] ?? null) : ($slot->label ?? null);
          if ($starts instanceof \Carbon\Carbon) {
            $value = $starts->copy()->utc()->toIso8601String();
            $label = $label ?: $starts->copy()->timezone($viewerTz)->locale(app()->getLocale())->translatedFormat('D j M، g:i A');
          } else {
            continue;
          }
        @endphp
        <label class="mc-tp-slot">
          <input type="checkbox" name="scheduled_ats[]" value="{{ $value }}">
          <span>{{ $label }}</span>
        </label>
      @endforeach
    </div>
    <button type="submit" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'حجز المواعيد المحددة' : 'Book selected slots' }}</button>
  </div>

  <div id="glTpSingle" class="mc-tp-book-pane" hidden>
    <div class="mc-tp-slots">
      @foreach($bookableSlots as $slot)
        @php
          $starts = is_array($slot) ? ($slot['starts_at'] ?? null) : ($slot->starts_at ?? null);
          $label = is_array($slot) ? ($slot['label'] ?? null) : ($slot->label ?? null);
          if ($starts instanceof \Carbon\Carbon) {
            $value = $starts->copy()->utc()->toIso8601String();
            $label = $label ?: $starts->copy()->timezone($viewerTz)->locale(app()->getLocale())->translatedFormat('D j M، g:i A');
          } else {
            continue;
          }
        @endphp
        <button type="submit" name="scheduled_at" value="{{ $value }}" class="mc-tp-slot" formnovalidate>
          <span>{{ $label }}</span>
          <i class="fas fa-calendar-plus" aria-hidden="true"></i>
        </button>
      @endforeach
    </div>
  </div>
</form>
<script>
(function () {
  var form = document.getElementById('glTpBookForm');
  if (!form) return;
  var monthly = document.getElementById('glTpMonthly');
  var multi = document.getElementById('glTpMulti');
  var single = document.getElementById('glTpSingle');
  var w0 = document.getElementById('glTpW0');
  var weeklyCombos = [0, 1, 2, 3].map(function (i) {
    return {
      sel: document.getElementById('glTpW' + i),
      day: document.getElementById('glTpW' + i + 'Day'),
      time: document.getElementById('glTpW' + i + 'Time')
    };
  });
  function syncCombo(sel, dayEl, timeEl) {
    if (!sel || !dayEl || !timeEl) return;
    var v = sel.value || '';
    var p = v.split('|');
    dayEl.value = p[0] || '';
    timeEl.value = p[1] || '';
  }
  function sync() {
    var style = (form.querySelector('input[name="booking_style"]:checked') || {}).value || 'monthly';
    monthly.hidden = style !== 'monthly';
    multi.hidden = style !== 'multi';
    single.hidden = style !== 'single';
    if (w0) w0.required = style === 'monthly';
  }
  form.querySelectorAll('input[name="booking_style"]').forEach(function (el) {
    el.addEventListener('change', sync);
  });
  weeklyCombos.forEach(function (row) {
    if (row.sel) row.sel.addEventListener('change', function () { syncCombo(row.sel, row.day, row.time); });
  });
  form.addEventListener('submit', function () {
    weeklyCombos.forEach(function (row) { syncCombo(row.sel, row.day, row.time); });
  });
  sync();
})();
</script>
