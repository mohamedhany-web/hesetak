{{-- حصة مجانية تكميلية لمشترك الباقة — لا تُخصم من الرصيد --}}
@php
  $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
  $instructorUser = $profile->user;
  $viewerTz = $viewerTz ?? \App\Support\AppTimezone::forUser(auth()->user());
  $freeSlots = $bookableSlots ?? collect();
@endphp

<div class="mc-tp-free" id="mc-tp-free" data-instructor-id="{{ $instructorUser->id }}" data-instructor-uuid="{{ $instructorUser->uuid }}">
  <div class="mc-tp-free__head">
    <span class="mc-package__badge">{{ $isRtl ? 'بعد الاشتراك' : 'After subscribe' }}</span>
    <h3 class="mc-tp-free__title">{{ $isRtl ? 'احجز حصة تعريفية مجانية مع هذا المعلم' : 'Book a free intro session with this teacher' }}</h3>
    <p>{{ $isRtl
      ? 'حصة تعريفية بدون خصم من رصيد باقتك — تتعرّف على أسلوب المعلم قبل تثبيت جدولك.'
      : 'An intro session that does not use your package credits — try the teacher before locking a schedule.' }}</p>
  </div>

  <div id="mc-tp-free-status" class="mc-tp-alert" hidden role="status"></div>

  @if($freeSlots->isEmpty())
    <p class="mc-tp-text mc-tp-text--muted">{{ $isRtl ? 'لا توجد مواعيد مفتوحة حالياً. أرسل طلباً وسنتواصل معك للتنسيق.' : 'No open slots right now. Send a request and we will coordinate.' }}</p>
    <button type="button" class="mc-btn mc-btn--md mc-btn--secondary" id="mc-tp-free-request">
      {{ $isRtl ? 'اطلب حصة تجريبية مجانية' : 'Request a free trial' }}
    </button>
  @else
    <form id="mc-tp-free-form" class="mc-tp-free__form">
      <div class="mc-tp-slots" role="listbox" aria-label="{{ $isRtl ? 'مواعيد الحصة التجريبية' : 'Trial slots' }}">
        @foreach($freeSlots as $slot)
          @php
            $starts = is_array($slot) ? ($slot['starts_at'] ?? null) : ($slot->starts_at ?? null);
            $label = is_array($slot) ? ($slot['label'] ?? null) : ($slot->label ?? null);
            if (! $starts instanceof \Carbon\Carbon) {
              continue;
            }
            $value = $starts->copy()->utc()->toIso8601String();
            $local = $starts->copy()->timezone($viewerTz)->locale(app()->getLocale());
            $hour = (int) $local->format('G');
            $period = $hour < 12
              ? ($isRtl ? 'صباحاً (AM)' : 'AM')
              : ($isRtl ? 'مساءً (PM)' : 'PM');
            $label = $label ?: ($local->translatedFormat('D j M، g:i').' '.$period);
          @endphp
          <label class="mc-tp-slot">
            <input type="radio" name="free_starts_at" value="{{ $value }}" required>
            <span>{{ $label }}</span>
          </label>
        @endforeach
      </div>
      <button type="submit" class="mc-btn mc-btn--md mc-btn--secondary">
        {{ $isRtl ? 'تأكيد الحصة التجريبية المجانية' : 'Confirm free trial' }}
      </button>
    </form>
  @endif

  <div class="mc-tp-free__propose" style="margin-top:1rem;padding-top:1rem;border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent)">
    <p class="mc-tp-text" style="margin-bottom:.5rem"><strong>{{ $isRtl ? 'أو اقترح موعداً بإضافة صباح/مساء' : 'Or suggest a time with AM/PM' }}</strong></p>
    <form id="mc-tp-free-propose" class="mc-tp-free__form" style="display:grid;gap:.65rem">
      <label class="mc-tp-field">{{ $isRtl ? 'التاريخ' : 'Date' }}
        <input type="date" name="propose_date" required min="{{ now()->toDateString() }}">
      </label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
        <label class="mc-tp-field">{{ $isRtl ? 'الساعة' : 'Hour' }}
          <select name="propose_hour" required>
            @for($h = 1; $h <= 12; $h++)
              <option value="{{ $h }}">{{ $h }}</option>
            @endfor
          </select>
        </label>
        <label class="mc-tp-field">{{ $isRtl ? 'الدقيقة' : 'Minute' }}
          <select name="propose_minute" required>
            <option value="00">00</option>
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="45">45</option>
          </select>
        </label>
      </div>
      <fieldset style="border:0;padding:0;margin:0">
        <legend class="mc-tp-text" style="margin-bottom:.35rem">{{ $isRtl ? 'الفترة' : 'Period' }}</legend>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
          <label class="mc-tp-slot"><input type="radio" name="propose_period" value="AM" required checked> <span>{{ $isRtl ? 'صباحاً AM' : 'AM' }}</span></label>
          <label class="mc-tp-slot"><input type="radio" name="propose_period" value="PM" required> <span>{{ $isRtl ? 'مساءً PM' : 'PM' }}</span></label>
        </div>
      </fieldset>
      <button type="submit" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'إرسال اقتراح الموعد للمعلم' : 'Send suggested time to teacher' }}</button>
    </form>
  </div>
</div>

<script>
(function () {
  var root = document.getElementById('mc-tp-free');
  if (!root) return;
  var instructorId = parseInt(root.getAttribute('data-instructor-id') || '0', 10);
  var instructorUuid = root.getAttribute('data-instructor-uuid') || '';
  var statusEl = document.getElementById('mc-tp-free-status');
  var bookUrl = @json(route('public.free-trial.book'));
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var csrf = csrfMeta ? (csrfMeta.getAttribute('content') || '') : '';
  var isRtl = @json($isRtl);

  function showStatus(msg, ok) {
    if (!statusEl) return;
    statusEl.hidden = false;
    statusEl.textContent = msg;
    statusEl.classList.toggle('is-ok', !!ok);
    statusEl.classList.toggle('is-err', !ok);
  }

  function postFreeSession(payload) {
    return fetch(bookUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    }).then(async function (res) {
      var data = {};
      try { data = await res.json(); } catch (e) {}
      if (!res.ok) {
        throw new Error(data.message || (isRtl ? 'تعذّر إتمام الحجز.' : 'Could not complete booking.'));
      }
      return data;
    });
  }

  var basePayload = {
    goal: 'free_session',
    instructor_uuid: instructorUuid,
    instructor_id: instructorId || undefined,
    name: @json(auth()->user()->name ?? ''),
    email: @json(auth()->user()->email ?? ''),
    timezone: @json($viewerTz)
  };

  var form = document.getElementById('mc-tp-free-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var picked = form.querySelector('input[name="free_starts_at"]:checked');
      if (!picked) {
        showStatus(isRtl ? 'اختر موعداً أولاً.' : 'Pick a slot first.', false);
        return;
      }
      var btn = form.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
      postFreeSession(Object.assign({}, basePayload, { starts_at: picked.value, as_request: false }))
        .then(function (data) {
          showStatus(data.message || (isRtl ? 'تم الحجز بنجاح.' : 'Booked successfully.'), true);
          form.hidden = true;
        })
        .catch(function (err) {
          showStatus(err.message || (isRtl ? 'حدث خطأ.' : 'Something went wrong.'), false);
        })
        .finally(function () {
          if (btn) btn.disabled = false;
        });
    });
  }

  var reqBtn = document.getElementById('mc-tp-free-request');
  if (reqBtn) {
    reqBtn.addEventListener('click', function () {
      reqBtn.disabled = true;
      postFreeSession(Object.assign({}, basePayload, { as_request: true }))
        .then(function (data) {
          showStatus(data.message || (isRtl ? 'تم إرسال الطلب.' : 'Request sent.'), true);
          reqBtn.hidden = true;
        })
        .catch(function (err) {
          showStatus(err.message || (isRtl ? 'حدث خطأ.' : 'Something went wrong.'), false);
        })
        .finally(function () {
          reqBtn.disabled = false;
        });
    });
  }

  var propose = document.getElementById('mc-tp-free-propose');
  if (propose) {
    propose.addEventListener('submit', function (e) {
      e.preventDefault();
      var date = propose.querySelector('[name="propose_date"]').value;
      var hour = parseInt(propose.querySelector('[name="propose_hour"]').value, 10);
      var minute = propose.querySelector('[name="propose_minute"]').value;
      var period = (propose.querySelector('[name="propose_period"]:checked') || {}).value || 'AM';
      if (!date || !hour) {
        showStatus(isRtl ? 'أكمل التاريخ والوقت.' : 'Complete date and time.', false);
        return;
      }
      var h24 = hour % 12;
      if (period === 'PM') h24 += 12;
      if (period === 'AM' && hour === 12) h24 = 0;
      if (period === 'PM' && hour === 12) h24 = 12;
      var localIso = date + 'T' + String(h24).padStart(2, '0') + ':' + minute + ':00';
      var btn = propose.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
      postFreeSession(Object.assign({}, basePayload, {
        starts_at: localIso,
        as_request: true,
        notes: (isRtl ? 'موعد مقترح من الطالب: ' : 'Student suggested time: ') + date + ' ' + hour + ':' + minute + ' ' + period
      }))
        .then(function (data) {
          showStatus(data.message || (isRtl ? 'تم إرسال اقتراح الموعد.' : 'Suggested time sent.'), true);
          propose.hidden = true;
        })
        .catch(function (err) {
          showStatus(err.message || (isRtl ? 'حدث خطأ.' : 'Something went wrong.'), false);
        })
        .finally(function () {
          if (btn) btn.disabled = false;
        });
    });
  }
})();
</script>
