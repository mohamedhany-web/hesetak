@extends('layouts.admin')

@section('title', __('admin.about_page') . ' - حصتك')
@section('page_title', __('admin.about_page'))

@section('content')
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

    <article class="rounded-2xl border border-line bg-surface p-5 shadow-soft sm:p-6">
        <div class="mb-4 flex items-center gap-3">
            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-info-circle text-sm"></i></span>
            <div>
                <h3 class="text-base font-semibold text-ink">صفحة من نحن</h3>
                <p class="mt-0.5 text-xs text-muted">المحتوى المعروض للزوار على الموقع العام</p>
            </div>
        </div>
        <p class="mb-5 text-sm leading-6 text-muted">يمكنك معاينة صفحة «من نحن» كما يراها الزائر. سيتم تفعيل التحرير المباشر لاحقاً عند إضافة حقول المحتوى.</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('public.about') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-6 text-sm font-medium text-white">
                <i class="fas fa-external-link-alt text-xs"></i>
                فتح المعاينة
            </a>
        </div>
    </article>
</div>
@endsection
