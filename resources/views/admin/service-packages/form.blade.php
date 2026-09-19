@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'باقة جديدة' : 'تعديل باقة').' - حصتك')
@section('page_title', $mode === 'create' ? 'باقة خدمات جديدة' : 'تعديل باقة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $action = $mode === 'create' ? route('admin.service-packages.store') : route('admin.service-packages.update', $package);
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">باقات الخدمات</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">{{ $mode === 'create' ? 'إنشاء باقة' : 'تعديل: '.$package->name }}</h2>
        </div>
        <a href="{{ route('admin.service-packages.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm">رجوع</a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <article class="rounded-2xl border border-line bg-surface p-4 sm:p-5 shadow-soft">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="name">اسم الباقة *</label>
                    <input id="name" name="name" value="{{ old('name', $package->name) }}" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="slug">الرابط</label>
                    <input id="slug" name="slug" value="{{ old('slug', $package->slug) }}" dir="ltr" class="{{ $fieldClass }} font-mono">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="badge">شارة</label>
                    <input id="badge" name="badge" value="{{ old('badge', $package->badge) }}" class="{{ $fieldClass }}" placeholder="الأكثر اختياراً">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="plan_type">الخطة التجارية</label>
                    <select id="plan_type" name="plan_type" class="{{ $fieldClass }}">
                        <option value="">— ليست خطة تجارية —</option>
                        @foreach(\App\Models\ServicePackage::planTypes() as $value => $label)
                            <option value="{{ $value }}" @selected(old('plan_type', $package->plan_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="term_months">مدة الاشتراك (شهور)</label>
                    <select id="term_months" name="term_months" class="{{ $fieldClass }}">
                        <option value="">—</option>
                        @foreach(\App\Models\ServicePackage::termMonthsOptions() as $months)
                            <option value="{{ $months }}" @selected((string) old('term_months', $package->term_months) === (string) $months)>{{ $months }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="weekly_group_sessions">حصص جماعية / أسبوع</label>
                    <input id="weekly_group_sessions" type="number" min="0" max="14" name="weekly_group_sessions" value="{{ old('weekly_group_sessions', $package->weekly_group_sessions ?? 0) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="weekly_private_sessions">حصص فردية / أسبوع</label>
                    <input id="weekly_private_sessions" type="number" min="0" max="14" name="weekly_private_sessions" value="{{ old('weekly_private_sessions', $package->weekly_private_sessions ?? 0) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="scope">النطاق *</label>
                    <select id="scope" name="scope" class="{{ $fieldClass }}">
                        @foreach(\App\Models\ServicePackage::scopes() as $value => $label)
                            <option value="{{ $value }}" @selected(old('scope', $package->scope) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-muted">للخطط التجارية يُضبط تلقائياً حسب نوع الخطة.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="academic_year_id">السنة الدراسية (اختياري)</label>
                    <select id="academic_year_id" name="academic_year_id" class="{{ $fieldClass }}">
                        <option value="">— كل السنوات / عامة —</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected((string) old('academic_year_id', $package->academic_year_id) === (string) $year->id)>
                                {{ $year->name }}@if($year->level_number) ({{ $year->level_number }})@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-muted">اربط الباقة بسنة من نظام المدرسة ليظهر للطالب داخل تلك السنة فقط.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="academic_subject_id">المادة الدراسية (اختياري)</label>
                    <select id="academic_subject_id" name="academic_subject_id" class="{{ $fieldClass }}">
                        <option value="">— كل المواد / عامة —</option>
                        @foreach($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                data-year="{{ $subject->academic_year_id }}"
                                @selected((string) old('academic_subject_id', $package->academic_subject_id) === (string) $subject->id)
                            >
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="tutoring_group_id">مجموعة مخصصة (اختياري)</label>
                    <select id="tutoring_group_id" name="tutoring_group_id" class="{{ $fieldClass }}">
                        <option value="">— عامة —</option>
                        @foreach($groups as $g)
                            <option
                                value="{{ $g->id }}"
                                data-year="{{ $g->academic_year_id }}"
                                data-subject="{{ $g->academic_subject_id }}"
                                @selected((string) old('tutoring_group_id', $package->tutoring_group_id) === (string) $g->id)
                            >{{ $g->title }} ({{ $g->type }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="units_count">عدد الحصص *</label>
                    <input id="units_count" type="number" min="1" name="units_count" value="{{ old('units_count', $package->units_count) }}" required class="{{ $fieldClass }}">
                    <p class="mt-1 text-[11px] text-muted">كل حصة = وحدة واحدة تُخصم عند اكتمال الحصة.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="session_minutes">مدة الحصة (دقيقة)</label>
                    <input id="session_minutes" type="number" min="15" max="480" name="session_minutes" value="{{ old('session_minutes', $package->session_minutes ?: 60) }}" class="{{ $fieldClass }}">
                    <p class="mt-1 text-[11px] text-muted">تظهر للطالب كإجمالي ساعات الباقة.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="duration_days">الصلاحية بالأيام</label>
                    <input id="duration_days" type="number" min="1" name="duration_days" value="{{ old('duration_days', $package->duration_days) }}" class="{{ $fieldClass }}">
                    <p class="mt-1 text-[11px] text-muted">اتركه فارغاً لباقة بلا تاريخ انتهاء.</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="price">السعر *</label>
                    <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $package->price) }}" required class="{{ $fieldClass }}" placeholder="0.00">
                    <p class="mt-1 text-[11px] text-muted">أدخل السعر بنفس عملة الباقة (يفضّل مطابقة عملة فواتيرك).</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="original_price">السعر قبل الخصم</label>
                    <input id="original_price" type="number" step="0.01" min="0" name="original_price" value="{{ old('original_price', $package->original_price) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="currency">العملة *</label>
                    @php
                        $currencyValue = strtoupper((string) old('currency', $package->currency ?: platform_currency()));
                        if (! in_array($currencyValue, ['SAR', 'USD', 'EGP'], true)) {
                            $currencyValue = platform_currency();
                        }
                    @endphp
                    <select id="currency" name="currency" required class="{{ $fieldClass }}">
                        <option value="SAR" @selected($currencyValue === 'SAR')>SAR — ريال سعودي</option>
                        <option value="USD" @selected($currencyValue === 'USD')>USD — دولار (قديم)</option>
                        <option value="EGP" @selected($currencyValue === 'EGP')>EGP — جنيه (قديم)</option>
                    </select>
                    <p class="mt-1 text-[11px] text-muted">عملة المنصة الافتراضية: {{ currency_label() }} ({{ platform_currency() }}).</p>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="sort_order">الترتيب</label>
                    <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $package->sort_order) }}" class="{{ $fieldClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="tagline">نبذة قصيرة</label>
                    <input id="tagline" name="tagline" value="{{ old('tagline', $package->tagline) }}" class="{{ $fieldClass }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="description">الوصف</label>
                    <textarea id="description" name="description" rows="3" class="{{ $areaClass }}">{{ old('description', $package->description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="features_text">المزايا (سطر لكل ميزة)</label>
                    <textarea id="features_text" name="features_text" rows="5" class="{{ $areaClass }}">{{ old('features_text', implode("\n", $package->featureList())) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}" for="gifts_text">الهدايا مع الاشتراك (سطر لكل هدية)</label>
                    <textarea id="gifts_text" name="gifts_text" rows="3" class="{{ $areaClass }}">{{ old('gifts_text', implode("\n", $package->giftList())) }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="includes_community" value="1" @checked(old('includes_community', $package->includes_community))> يشمل المجتمع الطلابي</label>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="includes_libraries" value="1" @checked(old('includes_libraries', $package->includes_libraries ?? true))> يشمل المكتبات التعليمية</label>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))> نشطة</label>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured))> مميزة</label>
            </div>
        </article>

        <button type="submit" class="btn-press inline-flex h-10 items-center rounded-xl bg-accent px-5 text-sm font-medium text-white">حفظ</button>
    </form>
</div>
@push('scripts')
<script>
(() => {
  const yearSelect = document.getElementById('academic_year_id');
  const subjectSelect = document.getElementById('academic_subject_id');
  const groupSelect = document.getElementById('tutoring_group_id');
  if (!yearSelect || !subjectSelect) return;

  const allSubjects = Array.from(subjectSelect.options).slice(1);

  const filterSubjects = () => {
    const yearId = yearSelect.value;
    const current = subjectSelect.value;
    subjectSelect.innerHTML = '<option value="">— كل المواد / عامة —</option>';
    allSubjects.forEach((opt) => {
      const optYear = opt.getAttribute('data-year') || '';
      if (!yearId || !optYear || optYear === yearId) {
        subjectSelect.appendChild(opt.cloneNode(true));
      }
    });
    if ([...subjectSelect.options].some((o) => o.value === current)) {
      subjectSelect.value = current;
    }
  };

  yearSelect.addEventListener('change', filterSubjects);
  filterSubjects();

  if (groupSelect) {
    groupSelect.addEventListener('change', () => {
      const selected = groupSelect.options[groupSelect.selectedIndex];
      if (!selected || !selected.value) return;
      const yearId = selected.getAttribute('data-year') || '';
      const subjectId = selected.getAttribute('data-subject') || '';
      if (yearId) yearSelect.value = yearId;
      filterSubjects();
      if (subjectId) subjectSelect.value = subjectId;
    });
  }
})();
</script>
@endpush
@endsection
