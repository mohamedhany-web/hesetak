@extends('layouts.admin')

@section('title', 'تسجيل طالب في برنامج - ' . config('app.name'))
@section('page_title', 'تسجيل طالب جديد')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">التسجيلات · البرامج المسجّلة</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">تسجيل طالب في برنامج</h2>
            <p class="mt-1 text-sm text-muted">اختر الطالب والبرنامج، وفعّل الوصول فوراً أو اتركه قيد الانتظار.</p>
        </div>
        <a href="{{ route('admin.online-enrollments.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-soft">
            <ul class="list-disc space-y-1 pr-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.online-enrollments.store') }}" class="space-y-5"
          x-data="{
              status: '{{ old('status', 'active') }}',
              studentLabel: '',
              studentPhone: '',
              showStudent: false,
              pickStudent() {
                  const sel = document.getElementById('user_id');
                  const opt = sel.options[sel.selectedIndex];
                  if (!sel.value) { this.showStudent = false; return; }
                  this.studentLabel = opt.text.split(' — ')[0] || opt.text;
                  this.studentPhone = opt.dataset.phone || '';
                  this.showStudent = true;
              }
          }"
          x-init="$nextTick(() => pickStudent())">
        @csrf

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="text-sm font-semibold text-ink">بيانات التسجيل</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="user_id">الطالب *</label>
                    <input type="text" id="studentSearchInput" placeholder="تصفية بالقائمة: اسم أو هاتف..."
                           class="mb-2 h-9 w-full rounded-xl border border-line px-3 text-xs text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <select name="user_id" id="user_id" required class="{{ $fieldClass }}" @change="pickStudent()">
                        <option value="">اختر الطالب</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}"
                                    data-phone="{{ $student->phone }}"
                                    data-email="{{ $student->email }}"
                                    @selected((string) old('user_id', request('student_id')) === (string) $student->id)>
                                {{ $student->name }} — {{ $student->phone ?: ($student->email ?: 'بدون هاتف') }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="advanced_course_id">البرنامج *</label>
                    <input type="text" id="courseSearchInput" placeholder="تصفية بالقائمة: اسم البرنامج..."
                           class="mb-2 h-9 w-full rounded-xl border border-line px-3 text-xs text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <select name="advanced_course_id" id="advanced_course_id" required class="{{ $fieldClass }}">
                        <option value="">اختر البرنامج</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('advanced_course_id') == $course->id)>
                                {{ $course->title }}@if($course->academicYear?->name) — {{ $course->academicYear->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('advanced_course_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="status">حالة التسجيل *</label>
                    <select name="status" id="status" required class="{{ $fieldClass }}" x-model="status">
                        <option value="pending">في الانتظار</option>
                        <option value="active">نشط (وصول فوري + بريد تفعيل)</option>
                    </select>
                    <p class="mt-1 text-xs text-muted">عند «نشط» يُفتح البرنامج للطالب وتُحسب نسبة المدرب إن وُجدت اتفاقية.</p>
                </div>

                <div x-show="status === 'active'" x-cloak>
                    <label class="{{ $labelClass }}" for="final_price">مبلغ التفعيل (ر.س) — اختياري</label>
                    <input type="number" name="final_price" id="final_price" value="{{ old('final_price') }}" min="0" step="0.01"
                           class="{{ $fieldClass }}" placeholder="فارغ = سعر البرنامج">
                    <p class="mt-1 text-xs text-muted">لحساب نسبة المدرب من مبلغ التفعيل الفعلي.</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="{{ $labelClass }}" for="notes">ملاحظات إدارية</label>
                <textarea name="notes" id="notes" rows="3" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="اختياري">{{ old('notes') }}</textarea>
            </div>
        </article>

        <div x-show="showStudent" x-cloak class="rounded-2xl border border-accent/20 bg-accent-soft/40 px-4 py-3 text-sm text-ink">
            <strong>الطالب المختار:</strong>
            <span x-text="studentLabel"></span>
            <span class="text-muted" x-show="studentPhone"> · <span x-text="studentPhone" dir="ltr"></span></span>
        </div>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="text-sm font-semibold text-ink">بحث سريع برقم الهاتف</h3>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input type="text" id="quickPhoneSearch" placeholder="رقم هاتف الطالب..." class="{{ $fieldClass }} sm:flex-1">
                <button type="button" onclick="searchByPhone()" class="btn-press inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line px-4 text-sm font-medium text-ink hover:bg-accent-soft hover:text-accent">
                    <i class="fas fa-search text-xs"></i> بحث
                </button>
            </div>
            <div id="phoneSearchResult" class="mt-3 hidden"></div>
        </article>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-save text-xs"></i> حفظ التسجيل
            </button>
            <a href="{{ route('admin.online-enrollments.index') }}" class="inline-flex h-11 items-center rounded-xl border border-line px-5 text-sm text-ink-soft">إلغاء</a>
        </div>
    </form>
</div>

<script>
function searchByPhone() {
    const phone = document.getElementById('quickPhoneSearch').value.trim();
    const resultDiv = document.getElementById('phoneSearchResult');
    if (!phone) { alert('يرجى إدخال رقم الهاتف'); return; }
    resultDiv.innerHTML = '<p class="text-sm text-muted">جاري البحث...</p>';
    resultDiv.classList.remove('hidden');
    fetch(`{{ route('admin.online-enrollments.search-by-phone') }}?phone=${encodeURIComponent(phone)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (ok && d.success) {
            const sel = document.getElementById('user_id');
            let opt = Array.from(sel.options).find(o => o.value == d.student.id);
            if (!opt) {
                opt = new Option(`${d.student.name} — ${d.student.phone || ''}`, d.student.id);
                opt.dataset.phone = d.student.phone || '';
                sel.add(opt);
            }
            sel.value = d.student.id;
            sel.dispatchEvent(new Event('change'));
            resultDiv.innerHTML = '<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">تم العثور على الطالب واختياره.</div>';
        } else {
            resultDiv.innerHTML = `<div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">${d.error || 'لم يُعثر على طالب'}</div>`;
        }
    })
    .catch(() => {
        resultDiv.innerHTML = '<div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">تعذّر البحث</div>';
    });
}
document.getElementById('quickPhoneSearch')?.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); searchByPhone(); }
});
document.addEventListener('DOMContentLoaded', function () {
    const bindFilter = (inputId, selectId) => {
        const input = document.getElementById(inputId);
        const select = document.getElementById(selectId);
        if (!input || !select) return;
        input.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            Array.from(select.options).forEach((option, index) => {
                if (index === 0) return;
                option.hidden = q && !option.text.toLowerCase().includes(q);
            });
        });
    };
    bindFilter('studentSearchInput', 'user_id');
    bindFilter('courseSearchInput', 'advanced_course_id');
});
</script>
@endsection
