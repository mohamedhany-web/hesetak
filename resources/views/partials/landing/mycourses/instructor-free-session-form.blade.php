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
    <h4>{{ $isRtl ? 'احجز حصة تجريبية مجانية مع هذا المعلم' : 'Book a free trial with this teacher' }}</h4>
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
            $label = $label ?: $starts->copy()->timezone($viewerTz)->locale(app()->getLocale())->translatedFormat('D j M، g:i A');
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
})();
</script>
