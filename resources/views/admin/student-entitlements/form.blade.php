@extends('layouts.admin')

@section('title', 'منح رصيد')
@section('page_title', 'منح رصيد حصص')

@section('content')
@php
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $area = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $selectedUserId = (int) ($selectedUserId ?? 0);
    $placementUrl = $placementUrl ?? null;
    $returnToPlacement = (bool) ($returnToPlacement ?? false);
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">الباقات والأسعار · أرصدة الطلاب</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">منح رصيد حصص</h2>
            <p class="mt-1 text-sm text-muted">امنح الطالب باقة/رصيداً يدوياً ثم يمكنك تسكينه مباشرة</p>
        </div>
        <a href="{{ route('admin.student-entitlements.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للأرصدة
        </a>
    </section>

    @if(session('error'))
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.student-entitlements.store') }}" class="space-y-5" id="entitlementGrantForm">
        @csrf
        @if($returnToPlacement)
            <input type="hidden" name="return_to_placement" value="1">
        @endif

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">1) الطالب</h3>
                <p class="mt-0.5 text-xs text-muted">ابحث بالاسم أو البريد أو الجوال ثم اختر الطالب</p>
            </div>
            <div class="p-4 sm:p-5">
                <label class="{{ $label }}" for="studentSearch">بحث عن طالب</label>
                <input type="search" id="studentSearch" autocomplete="off"
                       placeholder="بحث بالاسم أو البريد أو الجوال…"
                       class="{{ $field }} mb-2"
                       aria-label="بحث عن طالب">
                <label class="{{ $label }}" for="userSelect">الطالب *</label>
                <select name="user_id" id="userSelect" required class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" size="8">
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
                                @selected((string) old('user_id', $selectedUserId) === (string) $student->id)>
                            {{ $student->name }} — {{ $student->email }}@if($student->phone) · {{ $student->phone }}@endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-[11px] text-muted">اكتب في البحث لتقليص القائمة ثم اختر الطالب</p>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
            <div class="border-b border-line px-4 py-4 sm:px-5">
                <h3 class="text-base font-semibold text-ink">2) تفاصيل الرصيد</h3>
                <p class="mt-0.5 text-xs text-muted">حدد النطاق وعدد الحصص والصلاحية</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-4 sm:p-5 md:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="scopeSelect">النطاق *</label>
                    <select name="scope" id="scopeSelect" required class="{{ $field }}">
                        @foreach($scopes as $key => $scopeLabel)
                            <option value="{{ $key }}" @selected(old('scope', 'private_lessons') === $key)>{{ $scopeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}" for="unitsInput">عدد الحصص *</label>
                    <input type="number" name="units" id="unitsInput" min="1" max="500" value="{{ old('units', 4) }}" required class="{{ $field }}">
                </div>

                <div>
                    <label class="{{ $label }}" for="durationInput">الصلاحية بالأيام</label>
                    <input type="number" name="duration_days" id="durationInput" min="1" max="730" value="{{ old('duration_days', 60) }}" class="{{ $field }}">
                    <p class="mt-1 text-[11px] text-muted">اتركها 60 يوماً كافتراضي إن لم تحدد مدة أخرى</p>
                </div>

                <div>
                    <label class="{{ $label }}" for="groupSearch">تقييد بمجموعة (اختياري)</label>
                    <input type="search" id="groupSearch" autocomplete="off"
                           placeholder="بحث باسم المجموعة…"
                           class="{{ $field }} mb-2"
                           aria-label="بحث عن مجموعة">
                    <select name="tutoring_group_id" id="groupSelect" class="{{ $field }}">
                        <option value="">غير مقيد — صالح للمجموعات المتوافقة</option>
                        @foreach($groups as $group)
                            @php
                                $gSearch = mb_strtolower(trim($group->title.' '.$group->type), 'UTF-8');
                            @endphp
                            <option value="{{ $group->id }}"
                                    data-search="{{ e($gSearch) }}"
                                    data-type="{{ $group->type }}"
                                    @selected((string) old('tutoring_group_id') === (string) $group->id)>
                                {{ $group->title }} — {{ $group->typeLabel() }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-muted">إذا اخترت مجموعة، لن يُصرف الرصيد في مجموعة أخرى.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="notesInput">ملاحظات</label>
                    <textarea name="notes" id="notesInput" rows="3" class="{{ $area }}" placeholder="مثال: منحة إدارية / تعويض…">{{ old('notes') }}</textarea>
                </div>
            </div>
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-coins text-xs"></i>
                منح الرصيد
            </button>
            <a href="{{ route('admin.student-entitlements.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                إلغاء
            </a>
            @if($placementUrl)
                <a id="goPlacementBtn" href="{{ $placementUrl }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-accent/30 px-5 text-sm font-medium text-accent hover:bg-accent/5">
                    <i class="fas fa-user-check text-xs"></i>
                    الذهاب للتسكين
                </a>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  function bindSearch(input, select) {
    if (!input || !select) return;
    var options = Array.prototype.slice.call(select.querySelectorAll('option'));
    function applyFilter() {
      var q = (input.value || '').trim().toLowerCase();
      options.forEach(function (opt) {
        if (!opt.value) {
          opt.hidden = false;
          return;
        }
        if (opt.selected) {
          opt.hidden = false;
          return;
        }
        var hay = (opt.getAttribute('data-search') || opt.textContent || '').toLowerCase();
        opt.hidden = q.length > 0 && hay.indexOf(q) === -1;
      });
    }
    input.addEventListener('input', applyFilter);
    input.addEventListener('search', applyFilter);
    select.addEventListener('change', applyFilter);
  }

  var userSelect = document.getElementById('userSelect');
  var studentSearch = document.getElementById('studentSearch');
  var groupSelect = document.getElementById('groupSelect');
  var groupSearch = document.getElementById('groupSearch');
  var scopeSelect = document.getElementById('scopeSelect');
  var placementBtn = document.getElementById('goPlacementBtn');
  var placementBase = @json($placementUrl);

  bindSearch(studentSearch, userSelect);
  bindSearch(groupSearch, groupSelect);

  function syncPlacementLink() {
    if (!placementBtn || !placementBase) return;
    var uid = userSelect && userSelect.value ? userSelect.value : '';
    var scope = scopeSelect ? scopeSelect.value : '';
    var mode = (scope === 'private_lessons' || scope === 'global') ? 'private' : 'group';
    var url = placementBase + '?mode=' + encodeURIComponent(mode);
    if (uid) url += '&student_id=' + encodeURIComponent(uid);
    placementBtn.href = url;
  }

  if (userSelect) userSelect.addEventListener('change', syncPlacementLink);
  if (scopeSelect) scopeSelect.addEventListener('change', syncPlacementLink);
  syncPlacementLink();
})();
</script>
@endpush
