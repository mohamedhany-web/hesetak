@extends('layouts.admin')

@section('title', 'أنواع المنهج - حصتك')
@section('page_title', 'أنواع المنهج')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">مطابقة المعلم · مرحلة + مادة + نوع منهج</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">أنواع المنهج</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">
                الإدارة تضيف الأنواع يدوياً → المعلم يختارها في التسويق الشخصي → الطالب يفلتر بها في دليل المعلمين.
                ليست ادّعاء فروع جغرافية.
            </p>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.academic-years.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink">
                <i class="fas fa-school text-xs"></i>
                {{ __('admin.academic_years') }}
            </a>
            <a href="{{ route('admin.academic-subjects.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink">
                <i class="fas fa-book text-xs"></i>
                {{ __('admin.skill_groups') }}
            </a>
            <a href="{{ route('admin.personal-branding.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink">
                <i class="fas fa-user-tie text-xs"></i>
                ملفات المعلمين
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2">
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">الإجمالي</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($stats['total']) }}</p>
        </article>
        <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
            <p class="text-xs text-muted">نشط للفلاتر</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ number_format($stats['active']) }}</p>
        </article>
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">إضافة نوع منهج</h3>
            <p class="mt-0.5 text-xs text-muted">مثال: saudi · منهج سعودي · Saudi curriculum</p>
        </div>
        <form method="POST" action="{{ route('admin.curriculum-types.store') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-6 md:items-end">
            @csrf
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="key">المفتاح</label>
                <input id="key" name="key" required dir="ltr" class="{{ $fieldClass }}" placeholder="saudi" value="{{ old('key') }}">
                @error('key')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="label_ar">الاسم عربي</label>
                <input id="label_ar" name="label_ar" required class="{{ $fieldClass }}" value="{{ old('label_ar') }}">
                @error('label_ar')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="label_en">الاسم إنجليزي</label>
                <input id="label_en" name="label_en" required dir="ltr" class="{{ $fieldClass }}" value="{{ old('label_en') }}">
                @error('label_en')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-muted" for="aliases">أسماء بديلة (فاصلة)</label>
                <input id="aliases" name="aliases" class="{{ $fieldClass }}" placeholder="سعودي، KSA" value="{{ old('aliases') }}">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-muted" for="sort_order">الترتيب</label>
                <input id="sort_order" type="number" min="0" name="sort_order" class="{{ $fieldClass }}" value="{{ old('sort_order', $types->count()) }}">
            </div>
            <div class="md:col-span-6 flex flex-wrap items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-ink">
                    <input type="checkbox" name="is_active" value="1" checked>
                    نشط في الفلاتر
                </label>
                <button type="submit" class="btn-press inline-flex h-10 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-plus text-xs"></i>
                    إضافة
                </button>
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">الأنواع الحالية</h3>
        </div>
        <div class="admin-table-wrap">
            <table class="w-full min-w-[820px] text-right text-sm">
                <thead class="bg-[#f7f8fa] text-[11px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">المفتاح</th>
                        <th class="px-3 py-3 font-medium">عربي</th>
                        <th class="px-3 py-3 font-medium">إنجليزي</th>
                        <th class="px-3 py-3 font-medium">بدائل</th>
                        <th class="px-3 py-3 font-medium">ترتيب</th>
                        <th class="px-3 py-3 font-medium">حالة</th>
                        <th class="px-5 py-3 font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($types as $type)
                        <tr class="align-top hover:bg-[#f7f8fa]">
                            <td class="px-5 py-3 font-mono text-xs text-ink" dir="ltr">{{ $type->key }}</td>
                            <td class="px-3 py-3 font-medium text-ink">{{ $type->label_ar }}</td>
                            <td class="px-3 py-3 text-muted" dir="ltr">{{ $type->label_en }}</td>
                            <td class="px-3 py-3 text-xs text-muted">{{ implode(' · ', $type->aliases ?? []) ?: '—' }}</td>
                            <td class="px-3 py-3 tabular-nums text-muted">{{ $type->sort_order }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-lg px-2 py-1 text-[11px] font-bold {{ $type->is_active ? 'bg-accent-soft text-accent' : 'bg-danger/10 text-danger' }}">
                                    {{ $type->is_active ? 'نشط' : 'موقوف' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <details class="group">
                                    <summary class="cursor-pointer list-none text-xs font-semibold text-accent">تعديل</summary>
                                    <form method="POST" action="{{ route('admin.curriculum-types.update', $type) }}" class="mt-3 grid max-w-xl grid-cols-1 gap-2 rounded-xl border border-line bg-canvas p-3">
                                        @csrf
                                        @method('PUT')
                                        <input name="key" value="{{ $type->key }}" dir="ltr" class="{{ $fieldClass }}" required>
                                        <input name="label_ar" value="{{ $type->label_ar }}" class="{{ $fieldClass }}" required>
                                        <input name="label_en" value="{{ $type->label_en }}" dir="ltr" class="{{ $fieldClass }}" required>
                                        <textarea name="aliases" rows="2" class="{{ $areaClass }}" placeholder="بدائل مفصولة بفاصلة">{{ implode(', ', $type->aliases ?? []) }}</textarea>
                                        <input type="number" name="sort_order" value="{{ $type->sort_order }}" class="{{ $fieldClass }}">
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="is_active" value="1" @checked($type->is_active)>
                                            نشط
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="submit" class="btn-press inline-flex h-9 items-center rounded-xl bg-accent px-4 text-xs font-medium text-white">حفظ</button>
                                        </div>
                                    </form>
                                </details>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.curriculum-types.toggle', $type) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-ink hover:text-accent">
                                            {{ $type->is_active ? 'إيقاف' : 'تفعيل' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.curriculum-types.destroy', $type) }}" onsubmit="return confirm('حذف نوع المنهج؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-danger">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-muted">لا أنواع بعد — أضف أول نوع أعلاه.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
