{{-- اختيار أكثر من منهج لنفس المادة + فلترة المراحل حسب المناهج المختارة --}}
@php
    $tracks = $curriculumTypes ?? \App\Support\HesetakMatchCatalog::curriculumTypes();
    $selectedCurricula = array_map('strval', old('curriculum_types', $selectedCurricula ?? []));
@endphp
<div class="sm:col-span-2">
    <label class="{{ $labelClass }}">أنواع المناهج (يمكن اختيار أكثر من منهج)</label>
    <div class="grid gap-2 rounded-xl border border-line bg-canvas/40 p-3 sm:grid-cols-2 lg:grid-cols-3" id="subject-curriculum-list">
        @foreach($tracks as $key => $meta)
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" name="curriculum_types[]" value="{{ $key }}" class="rounded border-line"
                       @checked(in_array((string) $key, $selectedCurricula, true))>
                <span>{{ is_array($meta) ? ($meta['label'] ?? $key) : $meta }}</span>
            </label>
        @endforeach
    </div>
    <p class="mt-1 text-xs text-muted">اختر منهجاً أو أكثر لتظهر المادة معها. بدون اختيار = تظهر مع كل المناهج.</p>
</div>

@push('scripts')
<script>
(function () {
  var curricula = document.getElementById('subject-curriculum-list');
  var list = document.getElementById('subject-year-list');
  if (!curricula || !list) return;
  function selectedTracks() {
    return Array.prototype.slice.call(curricula.querySelectorAll('input[type="checkbox"]:checked'))
      .map(function (i) { return (i.value || '').trim(); });
  }
  function filterYears() {
    var tracks = selectedTracks();
    list.querySelectorAll('[data-curriculum-type]').forEach(function (row) {
      var rowTrack = (row.getAttribute('data-curriculum-type') || '').trim();
      var show = tracks.length === 0 || !rowTrack || tracks.indexOf(rowTrack) !== -1;
      row.style.display = show ? '' : 'none';
      if (!show) {
        var input = row.querySelector('input[type="checkbox"]');
        if (input) input.checked = false;
      }
    });
  }
  curricula.addEventListener('change', filterYears);
  filterYears();
})();
</script>
@endpush
