@extends('layouts.admin')

@section('title', 'إنشاء مرحلة دراسية - حصتك')
@section('page_title', 'إنشاء مرحلة دراسية')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';

    $icons = [
        'fas fa-graduation-cap' => 'قبعة تخرج',
        'fas fa-school' => 'مبنى (اختياري)',
        'fas fa-book-open' => 'كتاب',
        'fas fa-user-graduate' => 'طالب',
        'fas fa-chalkboard' => 'سبورة',
        'fas fa-language' => 'لغة',
        'fas fa-layer-group' => 'مراحل',
        'fas fa-calendar-alt' => 'تقويم',
    ];

    $selectedIcon = old('icon', 'fas fa-graduation-cap');
    $selectedColor = old('color', '#0B3D91');
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">إدارة المحتوى · مطابقة المناهج</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">إنشاء مرحلة دراسية</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">
                المرحلة طبقة تنظيمية لتجميع المواد والكورسات وفلاتر المطابقة (مثل ابتدائي أو ثانوي). بعد الحفظ اربط المواد والكورسات والمدربين من صفحة التعديل.
            </p>
        </div>
        <a href="{{ route('admin.academic-years.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
            <i class="fas fa-arrow-right text-xs"></i>
            رجوع للقائمة
        </a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-danger/20 bg-danger/5 p-4 text-sm text-danger shadow-soft">
            <p class="mb-2 font-semibold">يرجى تصحيح ما يلي:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.academic-years.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" id="academic-year-create-form">
        @csrf

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">البيانات الأساسية</h3>
                        <p class="mt-0.5 text-xs text-muted">الاسم والرمز والوصف الداخلي</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                        <div class="sm:col-span-1">
                            <label class="{{ $labelClass }}" for="name">اسم المرحلة <span class="text-danger">*</span></label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                                   placeholder="مثال: الصف الأول الثانوي"
                                   class="{{ $fieldClass }}" autocomplete="off">
                            <p class="mt-1.5 text-xs text-muted">الاسم الظاهر في لوحة التحكم وعند ربط المحتوى.</p>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="{{ $labelClass }}" for="code">رمز السنة <span class="text-danger">*</span></label>
                            <input id="code" type="text" name="code" value="{{ old('code') }}" required maxlength="10" dir="ltr"
                                   placeholder="G10 أو SEC-1"
                                   class="{{ $fieldClass }} font-mono uppercase" autocomplete="off">
                            <p class="mt-1.5 text-xs text-muted">رمز إنجليزي مختصر وفريد (حتى 10 أحرف) لربط الكورسات والمواد.</p>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="{{ $labelClass }}" for="slug">رابط الصفحة العامة</label>
                            <input id="slug" type="text" name="slug" value="{{ old('slug') }}" maxlength="255" dir="ltr"
                                   placeholder="islamic-foundations-1"
                                   class="{{ $fieldClass }} font-mono" autocomplete="off">
                            <p class="mt-1.5 text-xs text-muted">يُستخدم في /school/{slug}. يُولَّد تلقائياً إن تُرك فارغاً.</p>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="{{ $labelClass }}" for="level_number">رقم المستوى</label>
                            <input id="level_number" type="number" name="level_number" value="{{ old('level_number') }}" min="1" max="20"
                                   class="{{ $fieldClass }}" placeholder="1–20">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $labelClass }}" for="tagline">شعار مختصر</label>
                            <input id="tagline" type="text" name="tagline" value="{{ old('tagline') }}" maxlength="255"
                                   class="{{ $fieldClass }}" placeholder="مثال: Building the Basics">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $labelClass }}" for="description">الوصف <span class="font-normal text-muted">(اختياري)</span></label>
                            <textarea id="description" name="description" rows="4" class="{{ $areaClass }}"
                                      placeholder="وصف مختصر للمرحلة وما تغطيه من مواد أو كورسات.">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">المظهر والترتيب</h3>
                        <p class="mt-0.5 text-xs text-muted">أيقونة ولون البطاقة في لوحة التحكم، وترتيب الظهور</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-4 sm:grid-cols-2 sm:p-5">
                        <div>
                            <label class="{{ $labelClass }}" for="icon">الأيقونة</label>
                            <select id="icon" name="icon" class="{{ $fieldClass }}">
                                @foreach($icons as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedIcon === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="color">لون البطاقة</label>
                            <div class="flex items-center gap-3">
                                <input id="color" type="color" name="color" value="{{ $selectedColor }}"
                                       class="h-11 w-14 cursor-pointer rounded-xl border border-line bg-surface p-1">
                                <input id="color_hex" type="text" value="{{ $selectedColor }}" dir="ltr" maxlength="7"
                                       class="{{ $fieldClass }} font-mono" aria-label="كود اللون">
                            </div>
                            <p class="mt-1.5 text-xs text-muted">يظهر في بطاقات السنة داخل لوحة التحكم فقط.</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="order">ترتيب الظهور</label>
                            <input id="order" type="number" name="order" value="{{ old('order', 0) }}" min="0" class="{{ $fieldClass }}">
                            <p class="mt-1.5 text-xs text-muted">الأصغر يظهر أولاً في القائمة (0 = الأول).</p>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="thumbnail">صورة مصغرة <span class="font-normal text-muted">(اختياري)</span></label>
                            <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/jpg"
                                   class="block w-full text-sm text-muted file:ml-4 file:rounded-xl file:border-0 file:bg-accent-soft file:px-4 file:py-2 file:text-sm file:font-medium file:text-accent hover:file:bg-accent hover:file:text-white">
                            <p class="mt-1.5 text-xs text-muted">JPEG أو PNG — للتعرّف السريع داخل الأدمن.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <input type="hidden" name="is_active" value="0">
                            <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-line bg-canvas px-4 sm:w-auto">
                                <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', '1') !== '0') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                                <span class="text-sm font-medium text-ink">سنة نشطة — متاحة لربط المواد والكورسات</span>
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <input type="hidden" name="is_public" value="0">
                            <label class="inline-flex h-11 w-full cursor-pointer items-center gap-2 rounded-xl border border-amber-100 bg-amber-50 px-4 sm:w-auto">
                                <input type="checkbox" name="is_public" value="1" @checked((string) old('is_public', '0') === '1') class="size-4 rounded border-line text-accent focus:ring-accent/20">
                                <span class="text-sm font-medium text-ink">نشر في صفحة المناهج العامة (`/curricula`)</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 border-t border-line px-4 py-4 sm:px-5">
                        <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                            <i class="fas fa-save text-xs"></i>
                            حفظ السنة الأكاديمية
                        </button>
                        <a href="{{ route('admin.academic-years.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-6 text-sm font-medium text-ink hover:bg-canvas">
                            إلغاء
                        </a>
                    </div>
                </article>
            </div>

            <aside class="space-y-5">
                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">معاينة البطاقة</h3>
                        <p class="mt-0.5 text-xs text-muted">كيف ستظهر تقريباً في القائمة</p>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div class="rounded-2xl border border-line bg-canvas p-4">
                            <div class="flex items-start gap-3">
                                <span id="preview-icon-wrap" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-soft" style="background: {{ $selectedColor }}">
                                    <i id="preview-icon" class="{{ $selectedIcon }} text-lg"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p id="preview-name" class="truncate text-base font-semibold text-ink">{{ old('name') ?: 'اسم السنة' }}</p>
                                    <p id="preview-code" class="mt-0.5 font-mono text-xs uppercase tracking-wide text-muted">{{ old('code') ?: 'CODE' }}</p>
                                    <p id="preview-desc" class="mt-2 line-clamp-3 text-xs text-muted">{{ old('description') ?: 'سيظهر الوصف هنا إن أضفته.' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-surface px-2.5 py-1 text-[11px] font-medium text-ink-soft border border-line">
                                    <i class="fas fa-layer-group text-[10px]"></i> مواد لاحقاً
                                </span>
                                <span id="preview-status" class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-[11px] font-medium text-success">
                                    نشطة
                                </span>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
                    <div class="border-b border-line px-4 py-4 sm:px-5">
                        <h3 class="text-base font-semibold text-ink">أمثلة مقترحة</h3>
                        <p class="mt-0.5 text-xs text-muted">اضغط لنسخ الاسم والرمز في النموذج</p>
                    </div>
                    <div class="grid grid-cols-1 gap-2 p-4 sm:p-5">
                        @php
                            $examples = [
                                ['name' => 'الصف الأول الإعدادي', 'code' => 'PREP-1'],
                                ['name' => 'الصف الثاني الإعدادي', 'code' => 'PREP-2'],
                                ['name' => 'الصف الثالث الإعدادي', 'code' => 'PREP-3'],
                                ['name' => 'الصف الأول الثانوي', 'code' => 'SEC-1'],
                                ['name' => 'الصف الثاني الثانوي', 'code' => 'SEC-2'],
                                ['name' => 'الصف الثالث الثانوي', 'code' => 'SEC-3'],
                                ['name' => 'المرحلة الابتدائية', 'code' => 'PRIM'],
                                ['name' => 'التحضيري', 'code' => 'KG'],
                            ];
                        @endphp
                        @foreach($examples as $ex)
                            <button type="button"
                                    class="ay-example btn-press flex w-full items-center justify-between gap-2 rounded-xl border border-line bg-canvas px-3 py-2.5 text-start transition hover:border-accent/40 hover:bg-accent-soft/40"
                                    data-name="{{ $ex['name'] }}"
                                    data-code="{{ $ex['code'] }}">
                                <span class="text-sm font-medium text-ink">{{ $ex['name'] }}</span>
                                <span class="font-mono text-[11px] text-muted">{{ $ex['code'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </article>

                <div class="rounded-2xl border border-line bg-canvas px-4 py-3 text-xs leading-relaxed text-muted">
                    <p class="font-medium text-ink-soft">بعد الحفظ</p>
                    <p class="mt-1">من صفحة التعديل: أضف مواد/مجموعات، اربط الكورسات، وعيّن المدربين للسنة.</p>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
(function () {
    const nameEl = document.getElementById('name');
    const codeEl = document.getElementById('code');
    const descEl = document.getElementById('description');
    const iconEl = document.getElementById('icon');
    const colorEl = document.getElementById('color');
    const colorHex = document.getElementById('color_hex');
    const activeEl = document.querySelector('input[name="is_active"][type="checkbox"]');

    const previewName = document.getElementById('preview-name');
    const previewCode = document.getElementById('preview-code');
    const previewDesc = document.getElementById('preview-desc');
    const previewIcon = document.getElementById('preview-icon');
    const previewIconWrap = document.getElementById('preview-icon-wrap');
    const previewStatus = document.getElementById('preview-status');

    let codeTouched = {{ old('code') ? 'true' : 'false' }};

    function syncPreview() {
        previewName.textContent = (nameEl.value || '').trim() || 'اسم السنة';
        previewCode.textContent = (codeEl.value || '').trim().toUpperCase() || 'CODE';
        previewDesc.textContent = (descEl.value || '').trim() || 'سيظهر الوصف هنا إن أضفته.';
        previewIcon.className = (iconEl.value || 'fas fa-graduation-cap') + ' text-lg';
        const color = colorEl.value || '#0B3D91';
        previewIconWrap.style.background = color;
        if (colorHex && document.activeElement !== colorHex) {
            colorHex.value = color;
        }
        if (activeEl && activeEl.checked) {
            previewStatus.textContent = 'نشطة';
            previewStatus.className = 'inline-flex items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-[11px] font-medium text-success';
        } else {
            previewStatus.textContent = 'موقوفة';
            previewStatus.className = 'inline-flex items-center gap-1 rounded-full bg-danger/10 px-2.5 py-1 text-[11px] font-medium text-danger';
        }
    }

    function suggestCodeFromName() {
        if (codeTouched || !nameEl.value.trim()) return;
        const map = {
            'أول': '1', 'الأول': '1', 'اول': '1',
            'ثاني': '2', 'الثاني': '2',
            'ثالث': '3', 'الثالث': '3',
        };
        let prefix = 'YR';
        const n = nameEl.value;
        if (n.includes('إعداد') || n.includes('اعداد')) prefix = 'PREP';
        else if (n.includes('ثانوي')) prefix = 'SEC';
        else if (n.includes('ابتدائ')) prefix = 'PRIM';
        else if (n.includes('تحضير') || n.includes('روضة')) prefix = 'KG';

        let num = '';
        for (const [k, v] of Object.entries(map)) {
            if (n.includes(k)) { num = v; break; }
        }
        codeEl.value = num ? (prefix + '-' + num) : prefix;
        syncPreview();
    }

    nameEl?.addEventListener('input', function () {
        suggestCodeFromName();
        syncPreview();
    });
    codeEl?.addEventListener('input', function () {
        codeTouched = true;
        codeEl.value = codeEl.value.toUpperCase().replace(/\s+/g, '-');
        syncPreview();
    });
    descEl?.addEventListener('input', syncPreview);
    iconEl?.addEventListener('change', syncPreview);
    colorEl?.addEventListener('input', syncPreview);
    activeEl?.addEventListener('change', syncPreview);

    colorHex?.addEventListener('input', function () {
        let v = colorHex.value.trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
            colorEl.value = v;
            syncPreview();
        }
    });

    document.querySelectorAll('.ay-example').forEach(function (btn) {
        btn.addEventListener('click', function () {
            nameEl.value = btn.dataset.name || '';
            codeEl.value = btn.dataset.code || '';
            codeTouched = true;
            syncPreview();
            nameEl.focus();
        });
    });

    syncPreview();
})();
</script>
@endsection
