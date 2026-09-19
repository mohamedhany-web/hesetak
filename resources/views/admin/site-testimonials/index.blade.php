@extends('layouts.admin')

@section('title', 'آراء الموقع - حصتك')
@section('page_title', 'آراء الموقع')

@section('content')
@php
    $kpis = [
        ['label' => 'إجمالي الآراء', 'value' => $stats['total'], 'icon' => 'fa-quote-right', 'tone' => 'accent', 'note' => 'كل الآراء المسجّلة'],
        ['label' => 'نشطة', 'value' => $stats['active'], 'icon' => 'fa-eye', 'tone' => 'accent', 'note' => 'تظهر للزوار'],
        ['label' => 'مميزة', 'value' => $stats['featured'], 'icon' => 'fa-star', 'tone' => 'metal', 'note' => 'بطاقة مميزة'],
        ['label' => 'صور', 'value' => $stats['images'], 'icon' => 'fa-image', 'tone' => 'muted', 'note' => 'شهادات بصورة'],
    ];
    $toneClass = [
        'accent' => 'bg-accent-soft text-accent',
        'metal' => 'bg-metal/15 text-metal',
        'muted' => 'bg-canvas-muted text-muted',
    ];
    $fieldClass = 'h-11 w-full rounded-xl border border-line bg-surface px-4 text-sm text-ink transition placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
    $labelClass = 'mb-1.5 block text-xs font-medium text-muted';
@endphp

<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-muted">محتوى الموقع · تظهر في الرئيسية وصفحة الآراء</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink md:text-[28px]">آراء وتجارب المعلمين</h2>
        </div>
        <div class="admin-hero-actions flex flex-wrap gap-2">
            @if(Route::has('public.testimonials'))
                <a href="{{ route('public.testimonials') }}" target="_blank" rel="noopener" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                    <i class="fas fa-external-link-alt text-xs"></i>
                    معاينة الموقع
                </a>
            @endif
            <a href="{{ route('admin.site-testimonials.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-accent px-4 text-sm font-medium text-white">
                <i class="fas fa-plus text-xs"></i>
                رأي جديد
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm font-medium text-ink shadow-soft" role="status">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-accent"><i class="fas fa-check text-sm"></i></span>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <section class="admin-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <article class="rounded-2xl border border-line bg-surface p-4 shadow-soft">
                <div class="inline-flex size-9 items-center justify-center rounded-xl {{ $toneClass[$kpi['tone']] }}">
                    <i class="fas {{ $kpi['icon'] }} text-sm"></i>
                </div>
                <p class="mt-3 text-xs text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format($kpi['value']) }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="border-b border-line px-4 py-4 sm:px-5">
            <h3 class="text-base font-semibold text-ink">البحث والفلترة</h3>
            <p class="mt-0.5 text-xs text-muted">ابحث بالنص أو الاسم، أو صفِّ حسب حالة الظهور</p>
        </div>
        <form method="GET" action="{{ route('admin.site-testimonials.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:p-5 md:grid-cols-3 md:items-end">
            <div>
                <label class="{{ $labelClass }}" for="search">البحث</label>
                <input id="search" type="search" name="search" value="{{ request('search') }}"
                       placeholder="النص أو اسم صاحب الرأي..." class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="status">الحالة</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected(request('status') === 'active')>نشط</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>معطل</option>
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl bg-accent px-5 text-sm font-medium text-white">
                    <i class="fas fa-filter text-xs"></i>
                    تصفية
                </button>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('admin.site-testimonials.index') }}" class="btn-press inline-flex h-11 items-center gap-2 rounded-xl border border-line px-5 text-sm font-medium text-ink hover:bg-canvas">
                        <i class="fas fa-times text-xs"></i>
                        مسح
                    </a>
                @endif
            </div>
        </form>
    </article>

    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4 sm:px-5">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-ink">قائمة الآراء</h3>
                <p class="mt-0.5 text-xs text-muted">{{ number_format($rows->total()) }} رأي</p>
            </div>
            <a href="{{ route('admin.site-testimonials.create') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink-soft transition hover:border-accent/30 hover:text-accent">
                <i class="fas fa-plus text-xs"></i>
                إضافة
            </a>
        </div>

        @if($rows->count() > 0)
            <div class="admin-table-wrap">
                <table class="w-full min-w-[900px] text-right text-sm">
                    <thead class="bg-canvas text-[11px] uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">الرأي</th>
                            <th class="px-3 py-3 font-medium">النوع</th>
                            <th class="px-3 py-3 font-medium">مميز</th>
                            <th class="px-3 py-3 font-medium">الترتيب</th>
                            <th class="px-3 py-3 font-medium">الحالة</th>
                            <th class="px-5 py-3 font-medium">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($rows as $row)
                            <tr class="transition hover:bg-canvas">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($row->isImageType() && $row->publicImageUrl())
                                            <img src="{{ $row->publicImageUrl() }}" alt="" class="size-11 shrink-0 rounded-xl border border-line object-cover">
                                        @else
                                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent">
                                                {{ mb_substr($row->author_name ?: 'ر', 0, 1, 'UTF-8') }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink">{{ $row->author_name ?: 'بدون اسم' }}</p>
                                            @if($row->role_label)
                                                <p class="mt-0.5 truncate text-xs text-muted">{{ $row->role_label }}</p>
                                            @elseif(! $row->isImageType())
                                                <p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($row->body ?? ''), 70) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if($row->content_type === \App\Models\SiteTestimonial::CONTENT_IMAGE)
                                        <span class="rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">صورة</span>
                                    @else
                                        <span class="rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">نص</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if($row->is_featured)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-metal/15 px-2.5 py-1 text-xs font-medium text-metal">
                                            <i class="fas fa-star text-[10px]"></i> مميز
                                        </span>
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 tabular-nums text-muted">{{ $row->sort_order }}</td>
                                <td class="px-3 py-3">
                                    @if($row->is_active)
                                        <span class="rounded-lg bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">نشط</span>
                                    @else
                                        <span class="rounded-lg bg-canvas-muted px-2.5 py-1 text-xs font-medium text-muted">معطل</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.site-testimonials.edit', $row) }}"
                                           class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-accent-soft text-accent transition hover:bg-accent hover:text-white"
                                           title="تعديل">
                                            <i class="fas fa-pen text-xs"></i>
                                        </a>
                                        <form action="{{ route('admin.site-testimonials.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذا الرأي؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn-press inline-flex size-8 items-center justify-center rounded-lg bg-danger/10 text-danger transition hover:bg-danger hover:text-white"
                                                    title="حذف">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="border-t border-line px-4 py-4 sm:px-5">{{ $rows->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="px-4 py-16 text-center sm:px-5">
                <div class="mx-auto mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
                    <i class="fas fa-quote-right"></i>
                </div>
                <p class="text-sm font-medium text-ink">لا توجد آراء</p>
                <p class="mt-1 text-xs text-muted">
                    @if(request()->anyFilled(['search', 'status']))
                        لا توجد نتائج مطابقة للفلتر الحالي.
                    @else
                        <a href="{{ route('admin.site-testimonials.create') }}" class="text-accent hover:underline">أضف أول رأي</a> ليظهر على الموقع.
                    @endif
                </p>
            </div>
        @endif
    </article>
</div>
@endsection
