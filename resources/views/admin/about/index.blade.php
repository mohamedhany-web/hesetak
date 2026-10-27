@extends('layouts.admin')

@section('title', __('admin.about_page') . ' - حصتك')
@section('page_title', __('admin.about_page'))

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $areaClass = 'w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink';
    $v = $values ?? [];
@endphp
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">محتوى الموقع · صفحة من نحن</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">{{ __('admin.about_page') }}</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.dashboard') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-arrow-right text-xs"></i>
                لوحة التحكم
            </a>
            <a href="{{ route('public.about') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-external-link-alt text-xs"></i>
                معاينة الصفحة
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.about.update') }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-6 space-y-4">
            <div class="mb-1 flex items-center gap-3">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-info-circle text-sm"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-ink">المحتوى الظاهر للزوار</h3>
                    <p class="mt-0.5 text-xs text-muted">العنوان والنص والأقسام — يُعرض مباشرة على /about</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="about_kicker">العنوان الفرعي (kicker)</label>
                    <input type="text" name="about_kicker" id="about_kicker" value="{{ old('about_kicker', $v['about_kicker'] ?? '') }}" class="{{ $fieldClass }}">
                    @error('about_kicker')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="about_title">العنوان الرئيسي *</label>
                    <input type="text" name="about_title" id="about_title" required value="{{ old('about_title', $v['about_title'] ?? '') }}" class="{{ $fieldClass }}">
                    @error('about_title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="{{ $labelClass }}" for="about_lead">المقدمة *</label>
                <textarea name="about_lead" id="about_lead" rows="3" required class="{{ $areaClass }}">{{ old('about_lead', $v['about_lead'] ?? '') }}</textarea>
                @error('about_lead')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="about_meta_title">عنوان SEO</label>
                    <input type="text" name="about_meta_title" id="about_meta_title" value="{{ old('about_meta_title', $v['about_meta_title'] ?? '') }}" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="about_meta_description">وصف SEO</label>
                    <input type="text" name="about_meta_description" id="about_meta_description" value="{{ old('about_meta_description', $v['about_meta_description'] ?? '') }}" class="{{ $fieldClass }}">
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-6 space-y-4">
            <h3 class="text-base font-semibold text-ink">القسم الأول</h3>
            <div>
                <label class="{{ $labelClass }}" for="about_section_1_title">عنوان القسم</label>
                <input type="text" name="about_section_1_title" id="about_section_1_title" value="{{ old('about_section_1_title', $v['about_section_1_title'] ?? '') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="about_section_1_body">نص القسم</label>
                <textarea name="about_section_1_body" id="about_section_1_body" rows="4" class="{{ $areaClass }}">{{ old('about_section_1_body', $v['about_section_1_body'] ?? '') }}</textarea>
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-6 space-y-4">
            <h3 class="text-base font-semibold text-ink">القسم الثاني</h3>
            <div>
                <label class="{{ $labelClass }}" for="about_section_2_title">عنوان القسم</label>
                <input type="text" name="about_section_2_title" id="about_section_2_title" value="{{ old('about_section_2_title', $v['about_section_2_title'] ?? '') }}" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="about_section_2_body">نص القسم</label>
                <textarea name="about_section_2_body" id="about_section_2_body" rows="4" class="{{ $areaClass }}">{{ old('about_section_2_body', $v['about_section_2_body'] ?? '') }}</textarea>
            </div>
        </article>

        <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-6 space-y-4">
            <div class="mb-1">
                <h3 class="text-base font-semibold text-ink">مميزات المنصة (Lighting cards)</h3>
                <p class="mt-0.5 text-xs text-muted">تظهر كبطاقات مضيئة تحت المقدمة في /about — اترك العنوان فارغاً لإخفاء البطاقة.</p>
            </div>
            @foreach([1, 2, 3, 4] as $fi)
              <div class="grid gap-4 sm:grid-cols-2 rounded-xl border border-line/70 p-4">
                <div>
                  <label class="{{ $labelClass }}" for="about_feature_{{ $fi }}_title">ميزة {{ $fi }} — العنوان</label>
                  <input type="text" name="about_feature_{{ $fi }}_title" id="about_feature_{{ $fi }}_title" value="{{ old('about_feature_'.$fi.'_title', $v['about_feature_'.$fi.'_title'] ?? '') }}" class="{{ $fieldClass }}">
                </div>
                <div>
                  <label class="{{ $labelClass }}" for="about_feature_{{ $fi }}_body">ميزة {{ $fi }} — النص</label>
                  <textarea name="about_feature_{{ $fi }}_body" id="about_feature_{{ $fi }}_body" rows="2" class="{{ $areaClass }}">{{ old('about_feature_'.$fi.'_body', $v['about_feature_'.$fi.'_body'] ?? '') }}</textarea>
                </div>
              </div>
            @endforeach
        </article>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                <i class="fas fa-save text-xs"></i>
                حفظ التعديلات
            </button>
            <a href="{{ route('public.about') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line bg-surface px-6 text-sm font-medium text-ink-soft">
                فتح المعاينة
            </a>
        </div>
    </form>
</div>
@endsection
