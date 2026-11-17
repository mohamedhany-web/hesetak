@extends('layouts.admin')

@section('title', 'تعديل بطاقة أسعار')
@section('page_title', 'تعديل بطاقة /pricing')

@section('content')
@php
    $field = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $area = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $label = 'mb-1.5 block text-xs font-medium text-muted';
    $pkg = $package;
    $featuresText = $pkg ? implode("\n", $pkg->featureList()) : "مدة الحصة 60 دقيقة\nمتابعة الدروس\nمعلمون معتمدون\nحصص تفاعلية أونلاين\nتقرير بعد الحصة";
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">كتالوج /pricing · مادة #{{ $subject->id }}</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">تعديل بطاقة: {{ $subject->name }}</h2>
            <p class="mt-1 text-sm text-muted">
                المرحلة: <strong>{{ $year?->name ?? '—' }}</strong>
                · أي تعديل هنا يظهر مباشرة على بطاقة المادة في صفحة الأسعار.
            </p>
        </div>
        <a href="{{ route('admin.pricing-storefront.index', array_filter(['year' => $yearId, 'curriculum' => $curriculum])) }}"
           class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i> رجوع للكتالوج
        </a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc pe-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pricing-storefront.update', $subject) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="year" value="{{ $yearId }}">
        <input type="hidden" name="curriculum" value="{{ $curriculum }}">

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft space-y-5">
            <h3 class="text-sm font-semibold text-ink">محتوى البطاقة</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="name">عنوان البطاقة *</label>
                    <input id="name" name="name" required class="{{ $field }}"
                           value="{{ old('name', $pkg->name ?? ('باقة '.$subject->name)) }}">
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="tagline">سطر تعريفي قصير</label>
                    <input id="tagline" name="tagline" class="{{ $field }}"
                           value="{{ old('tagline', $pkg->tagline ?? '') }}"
                           placeholder="مثال: معلم معتمد لمادة {{ $subject->name }}">
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="description">الوصف</label>
                    <textarea id="description" name="description" rows="3" class="{{ $area }}">{{ old('description', $pkg->description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}" for="badge">شارة</label>
                    <input id="badge" name="badge" class="{{ $field }}" value="{{ old('badge', $pkg->badge ?? '') }}" placeholder="الأكثر طلباً">
                </div>
                <div>
                    <label class="{{ $label }}" for="cover_image">صورة الغلاف</label>
                    <input id="cover_image" type="file" name="cover_image" accept="image/*" class="{{ $field }}">
                    @if($pkg?->coverImageUrl())
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ $pkg->coverImageUrl() }}" alt="" class="h-14 w-20 rounded-lg object-cover border border-line">
                            <label class="inline-flex items-center gap-2 text-xs text-muted">
                                <input type="checkbox" name="remove_cover_image" value="1"> حذف الصورة الحالية
                            </label>
                        </div>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="features_text">المزايا (سطر لكل ميزة)</label>
                    <textarea id="features_text" name="features_text" rows="5" class="{{ $area }}">{{ old('features_text', $featuresText) }}</textarea>
                </div>
            </div>
        </article>

        @php
            $unitsVal = (int) old('units_count', $pkg->units_count ?? 8);
            $priceVal = (float) old('price', $pkg->price ?? 0);
            $unitVal = (float) old('unit_price', $unitsVal > 0 && $priceVal > 0 ? round($priceVal / $unitsVal, 2) : 0);
        @endphp
        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft space-y-5">
            <h3 class="text-sm font-semibold text-ink">السعر والحصص لهذه الباقة فقط</h3>
            <p class="text-xs text-muted">
                كل بطاقة لها سعر حصة مستقل. عدّل <strong>سعر الحصة</strong> هنا — مش من صفحة قواعد التسعير العامة.
                إجمالي الباقة = سعر الحصة × عدد الحصص. خيارات 2 / 8 تُحسب من سعر هذه الباقة.
            </p>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="{{ $label }}" for="unit_price">سعر الحصة * <span class="text-accent">(لهذه الباقة)</span></label>
                    <input id="unit_price" type="number" step="0.01" min="0" name="unit_price" required class="{{ $field }}"
                           value="{{ $unitVal }}" data-pricing-unit>
                </div>
                <div>
                    <label class="{{ $label }}" for="units_count">عدد الحصص *</label>
                    <input id="units_count" type="number" min="1" name="units_count" required class="{{ $field }}"
                           value="{{ $unitsVal }}" data-pricing-units>
                </div>
                <div>
                    <label class="{{ $label }}" for="price">إجمالي الباقة *</label>
                    <input id="price" type="number" step="0.01" min="0" name="price" required class="{{ $field }}"
                           value="{{ $priceVal }}" data-pricing-total>
                    <p class="mt-1 text-[11px] text-muted">يُحدَّث تلقائياً من سعر الحصة × العدد.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="session_minutes">مدة الحصة (دقيقة) *</label>
                    <input id="session_minutes" type="number" min="15" name="session_minutes" required class="{{ $field }}"
                           value="{{ old('session_minutes', $pkg->session_minutes ?? 60) }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="duration_days">صلاحية الرصيد (يوم)</label>
                    <input id="duration_days" type="number" min="1" name="duration_days" class="{{ $field }}"
                           value="{{ old('duration_days', $pkg->duration_days ?? 60) }}">
                </div>
            </div>
        </article>
        <script>
            (function () {
                var unit = document.querySelector('[data-pricing-unit]');
                var units = document.querySelector('[data-pricing-units]');
                var total = document.querySelector('[data-pricing-total]');
                if (!unit || !units || !total) return;
                function syncTotal() {
                    var u = parseFloat(unit.value || '0');
                    var n = parseInt(units.value || '1', 10);
                    if (!isFinite(u) || u < 0) u = 0;
                    if (!isFinite(n) || n < 1) n = 1;
                    total.value = (Math.round(u * n * 100) / 100).toFixed(2);
                }
                unit.addEventListener('input', syncTotal);
                units.addEventListener('input', syncTotal);
            })();
        </script>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft space-y-3">
            <h3 class="text-sm font-semibold text-ink">الظهور</h3>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $pkg->is_active ?? true))>
                الباقة مفعّلة (للشراء)
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="show_in_storefront" value="1" @checked(old('show_in_storefront', $pkg->show_in_storefront ?? true))>
                ظاهرة في الكتالوج /pricing
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="subject_visible" value="1" @checked(old('subject_visible', $subject->is_active ?? true))>
                إظهار المادة ضمن قائمة مواد المرحلة
            </label>
            @if($pkg)
                <p class="text-xs text-muted">مرتبطة بباقة موجودة #{{ $pkg->id }} — سيتم تحديثها.</p>
            @else
                <p class="text-xs text-amber-700">لا توجد باقة مربوطة حالياً — سيتم إنشاء باقة جديدة وربطها بهذه المادة تلقائياً.</p>
            @endif
        </article>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                <i class="fas fa-save text-xs"></i> حفظ البطاقة
            </button>
            <a href="{{ route('public.pricing', array_filter(['year' => $yearId, 'curriculum_type' => $curriculum, 'subject' => $subject->id])) }}"
               target="_blank" rel="noopener"
               class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm text-ink-soft">
                معاينة /pricing
            </a>
        </div>
    </form>
</div>
@endsection
