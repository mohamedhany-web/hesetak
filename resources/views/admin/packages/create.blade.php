@extends('layouts.admin')

@section('title', 'إضافة باقة برامج - ' . config('app.name'))
@section('page_title', 'باقة برامج جديدة')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
    $coursesJson = $courses->map(fn ($c) => ['id' => $c->id, 'price' => (float) $c->price])->values()->toJson();
@endphp
<div class="space-y-5" x-data="packageForm({{ $coursesJson }}, {{ (float) old('price', 0) }})">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted">الباقات والأسعار · برامج مسجّلة</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">إنشاء باقة برامج</h2>
            <p class="mt-1 text-sm text-muted">اجمع عدة برامج بسعر موحّد بالريال السعودي مع مسار تعليمي اختياري.</p>
        </div>
        <a href="{{ route('admin.packages.index') }}" class="btn-press inline-flex h-9 items-center rounded-xl border border-line px-4 text-sm text-ink-soft">رجوع</a>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-soft">
            <ul class="list-disc pr-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-accent/20 bg-accent-soft/40 px-4 py-3 text-sm text-ink">
        <strong>حاسبة التوفير:</strong>
        مجموع أسعار البرامج المختارة =
        <span class="font-bold tabular-nums text-accent" x-text="coursesTotal.toFixed(2) + ' {{ currency_label() }}'"></span>
        · سعر الباقة =
        <span class="font-bold tabular-nums" x-text="packagePrice.toFixed(2) + ' {{ currency_label() }}'"></span>
        · التوفير =
        <span class="font-bold tabular-nums text-emerald-700" x-text="Math.max(0, coursesTotal - packagePrice).toFixed(2) + ' {{ currency_label() }}'"></span>
    </div>

    <form action="{{ route('admin.packages.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <h3 class="text-sm font-semibold text-ink">المعلومات الأساسية</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="name">اسم الباقة *</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="{{ $fieldClass }}" placeholder="مثال: باقة Business English الشاملة">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="slug">الرابط (Slug)</label>
                    <input id="slug" name="slug" value="{{ old('slug') }}" class="{{ $fieldClass }}" dir="ltr" placeholder="يُنشأ تلقائياً">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="track">المسار التعليمي</label>
                    <select id="track" name="track" class="{{ $fieldClass }}">
                        <option value="">بدون تحديد</option>
                        @foreach(\App\Models\Package::trackLabels() as $key => $label)
                            <option value="{{ $key }}" @selected(old('track') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="price">السعر النهائي *</label>
                    <input type="number" step="0.01" min="0" id="price" name="price" x-model.number="packagePrice" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="original_price">السعر الأصلي (قبل الخصم)</label>
                    <input type="number" step="0.01" min="0" id="original_price" name="original_price" value="{{ old('original_price') }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="currency">العملة</label>
                    <input id="currency" name="currency" value="{{ old('currency', platform_currency()) }}" class="{{ $fieldClass }}" dir="ltr">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="order">ترتيب العرض</label>
                    <input type="number" min="0" id="order" name="order" value="{{ old('order', 0) }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="duration_days">مدة الصلاحية (أيام)</label>
                    <input type="number" min="0" id="duration_days" name="duration_days" value="{{ old('duration_days') }}" class="{{ $fieldClass }}" placeholder="فارغ = دائم">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="starts_at">تاريخ البداية</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="ends_at">تاريخ الانتهاء</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" class="{{ $fieldClass }}">
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft space-y-4">
            <div>
                <label class="{{ $labelClass }}" for="description">الوصف (صفحة التفاصيل)</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="card_summary">نص البطاقة (صفحة الأسعار)</label>
                <textarea id="card_summary" name="card_summary" rows="3" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="نص مختصر يظهر في بطاقة الباقة">{{ old('card_summary') }}</textarea>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="thumbnail">صورة الباقة</label>
                <input type="file" id="thumbnail" name="thumbnail" accept="image/*" class="{{ $fieldClass }}">
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <label class="{{ $labelClass }}">المميزات</label>
            <div id="features-container" class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" name="features[]" class="h-11 flex-1 rounded-xl border border-line px-4 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="مثال: وصول لجميع برامج المسار">
                    <button type="button" onclick="removeFeature(this)" class="hidden inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-rose-600"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <button type="button" onclick="addFeature()" class="btn-press mt-3 inline-flex h-9 items-center gap-2 rounded-xl border border-line px-3 text-sm text-ink-soft hover:bg-accent-soft hover:text-accent">
                <i class="fas fa-plus text-xs"></i> إضافة ميزة
            </button>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <label class="{{ $labelClass }}">البرامج في الباقة *</label>
            <div class="mt-2 max-h-72 space-y-1 overflow-y-auto rounded-xl border border-line p-3">
                @forelse($courses as $course)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-[#f8faf9]">
                        <input type="checkbox" name="courses[]" value="{{ $course->id }}"
                               data-price="{{ (float) $course->price }}"
                               @checked(in_array($course->id, old('courses', [])))
                               @change="recalc()"
                               class="rounded border-line text-accent focus:ring-accent/20">
                        <span class="flex-1 text-sm text-ink">{{ $course->title }}</span>
                        <span class="text-xs tabular-nums text-muted">
                            @if((float) $course->price > 0)
                                {{ number_format((float) $course->price, 2) }} {{ currency_label() }}
                            @else
                                مجاني
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="py-6 text-center text-sm text-muted">لا توجد برامج نشطة</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft">
            <div class="flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-line text-accent"> نشط</label>
                <label class="inline-flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="rounded border-line text-accent"> مميز</label>
                <label class="inline-flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="is_popular" value="1" @checked(old('is_popular')) class="rounded border-line text-accent"> الأكثر شعبية</label>
            </div>
        </article>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white hover:bg-[#0d4f4a]">
                <i class="fas fa-save text-xs"></i> إنشاء الباقة
            </button>
            <a href="{{ route('admin.packages.index') }}" class="inline-flex h-11 items-center rounded-xl border border-line px-5 text-sm text-ink-soft">إلغاء</a>
        </div>
    </form>
</div>

<script>
function packageForm(courses, initialPrice) {
    return {
        packagePrice: initialPrice || 0,
        coursesTotal: 0,
        recalc() {
            let total = 0;
            document.querySelectorAll('input[name="courses[]"]:checked').forEach((el) => {
                total += parseFloat(el.dataset.price || '0') || 0;
            });
            this.coursesTotal = total;
        },
        init() {
            this.$nextTick(() => this.recalc());
        }
    };
}
function addFeature() {
    const container = document.getElementById('features-container');
    const row = document.createElement('div');
    row.className = 'flex gap-2';
    row.innerHTML = `<input type="text" name="features[]" class="h-11 flex-1 rounded-xl border border-line px-4 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20" placeholder="ميزة إضافية">
        <button type="button" onclick="removeFeature(this)" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line text-rose-600"><i class="fas fa-times"></i></button>`;
    container.appendChild(row);
    container.querySelectorAll('button').forEach(btn => btn.classList.remove('hidden'));
}
function removeFeature(button) {
    const container = document.getElementById('features-container');
    button.parentElement.remove();
    if (container.querySelectorAll('input').length === 1) {
        container.querySelectorAll('button').forEach(btn => btn.classList.add('hidden'));
    }
}
</script>
@endsection
